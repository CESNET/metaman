<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignOrganization;
use App\Http\Requests\JoinFederation;
use App\Http\Requests\StoreEntity;
use App\Jobs\GitAddEntity;
use App\Jobs\GitAddMember;
use App\Jobs\GitAddToCategory;
use App\Jobs\GitAddToEdugain;
use App\Jobs\GitAddToHfd;
use App\Jobs\GitAddToRs;
use App\Jobs\GitDeleteEntity;
use App\Jobs\GitDeleteFromCategory;
use App\Jobs\GitDeleteFromEdugain;
use App\Jobs\GitDeleteFromFederation;
use App\Jobs\GitDeleteFromHfd;
use App\Jobs\GitDeleteFromRs;
use App\Jobs\GitRestoreToCategory;
use App\Jobs\GitRestoreToEdugain;
use App\Jobs\GitUpdateEntity;
use App\Ldap\CesnetOrganization;
use App\Ldap\EduidczOrganization;
use App\Mail\AskRs;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\User;
use App\Notifications\EntityAddedToHfd;
use App\Notifications\EntityAddedToRs;
use App\Notifications\EntityDeletedFromFederation;
use App\Notifications\EntityDeletedFromHfd;
use App\Notifications\EntityDeletedFromRs;
use App\Notifications\EntityDestroyed;
use App\Notifications\EntityEdugainStatusChanged;
use App\Notifications\EntityOperatorsChanged;
use App\Notifications\EntityRequested;
use App\Notifications\EntityStateChanged;
use App\Notifications\EntityUpdated;
use App\Notifications\FederationMemberChanged;
use App\Notifications\IdpCategoryChanged;
use App\Notifications\YourEntityRightsChanged;
use App\Traits\GitTrait;
use App\Traits\ValidatorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class EntityController extends Controller
{
    use ValidatorTrait, GitTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', Entity::class);

        return view('entities.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Entity::class);

        return view('entities.create', [
            'federations' => Federation::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEntity $request)
    {
        $this->authorize('create', Entity::class);

        $validated = $request->validated();

        $metadata = $this->getMetadata($request);
        if (! $metadata) {
            return redirect()
                ->route('entities.create')
                ->with('status', __('entities.metadata_couldnt_be_read'))
                ->with('color', 'red');
        }

        $result = json_decode($this->validateMetadata($metadata), true);
        $new_entity = json_decode($this->parseMetadata($metadata), true);

        if (array_key_exists('result', $new_entity) && ! is_null($new_entity['result'])) {
            return redirect()
                ->back()
                ->with('status', __('entities.no_metadata').' '.$result['error'])
                ->with('color', 'red');
        }

        $existing = Entity::whereEntityid($new_entity['entityid'])->first();
        if ($existing) {
            return redirect()
                ->route('entities.show', $existing)
                ->with('status', __('entities.existing_already'))
                ->with('color', 'yellow');
        }

        switch ($result['code']) {
            case '0':
                $federation = Federation::findOrFail($validated['federation']);
                $entity = DB::transaction(function () use ($new_entity, $federation) {
                    if ($new_entity['type'] === 'idp') {
                        $new_entity = array_merge($new_entity, ['hfd' => true]);
                    }
                    $entity = Entity::create($new_entity);
                    $entity->operators()->attach(Auth::id());
                    $entity->federations()->attach($federation, [
                        'explanation' => request('explanation'),
                        'requested_by' => Auth::id(),
                    ]);

                    return $entity;
                });

                $admins = User::activeAdmins()->select('id', 'email')->get();
                $admins = $admins->merge($federation->operators);
                Notification::send($admins, new EntityRequested($entity, $federation));

                return redirect('entities')
                    ->with('status', __('entities.entity_requested', ['name' => $entity->entityid]).' '.$result['message']);

                break;

            case '1':
                return redirect()
                    ->back()
                    ->with('status', "{$result['error']} {$result['message']}")
                    ->with('color', 'red');

                break;

            default:
                return back()
                    ->with('status', __('entities.unknown_error_while_registration'))
                    ->with('color', 'red');
                break;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Entity  $entity
     * @return \Illuminate\Http\Response
     */
    public function show(Entity $entity)
    {
        $this->authorize('view', $entity);

        if (! app()->environment('testing')) {
            if ($entity->type->value === 'idp' && ! $entity->hfd) {
                $eduidczOrganization = EduidczOrganization::whereEntityIDofIdP($entity->entityid)->first();
                $cesnetOrganization = CesnetOrganization::find($eduidczOrganization?->getFirstAttribute('oPointer'));
                $cesnetOrganizations = is_null($cesnetOrganization) ? CesnetOrganization::select('o')->get() : null;
            }
        }

        return view('entities.show', [
            'entity' => $entity,
            'categories' => Category::orderBy('name')->get(),
            'eduidczOrganization' => $eduidczOrganization ?? null,
            'cesnetOrganization' => $cesnetOrganization ?? null,
            'cesnetOrganizations' => $cesnetOrganizations ?? null,
        ]);
    }

    public function operators(Entity $entity)
    {
        $this->authorize('view', $entity);

        $operators = $entity->operators()->paginate(10, ['*'], 'operatorsPage');
        $ops = $entity->operators->pluck('id');
        $users = User::orderBy('name')
            ->whereNotIn('id', $ops)
            ->search(request('search'))
            ->paginate(10, ['*'], 'usersPage');

        return view('entities.operators', [
            'entity' => $entity,
            'operators' => $operators,
            'users' => $users,
        ]);
    }

    public function federations(Entity $entity)
    {
        $this->authorize('view', $entity);

        $federations = $entity->federations;
        $requested = $entity->federationsRequested;
        $collection = $federations->concat($requested);
        $joinable = Federation::orderBy('name')
            ->whereNotIn('id', $collection->pluck('id'))
            ->get();

        return view('entities.federations', [
            'entity' => $entity,
            'federations' => $federations,
            'joinable' => $joinable,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Entity  $entity
     * @return \Illuminate\Http\Response
     */
    public function edit(Entity $entity)
    {
        $this->authorize('update', $entity);

        return view('entities.edit', [
            'entity' => $entity,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Entity  $entity
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Entity $entity)
    {
        switch (request('action')) {
            case 'update':
                $this->authorize('update', $entity);

                $validated = $request->validate([
                    'metadata' => 'nullable|string',
                    'file' => 'required_without:metadata|file',
                ]);

                $metadata = $this->getMetadata($request);
                if (! $metadata) {
                    return redirect()
                        ->back()
                        ->with('status', __('entities.metadata_couldnt_be_read'))
                        ->with('color', 'red');
                }

                $result = json_decode($this->validateMetadata($metadata), true);
                $updated_entity = json_decode($this->parseMetadata($metadata), true);

                if (array_key_exists('result', $updated_entity) && ! is_null($updated_entity['result'])) {
                    return redirect()
                        ->back()
                        ->with('status', __('entities.no_metadata'))
                        ->with('color', 'red');
                }

                if ($entity->entityid !== $updated_entity['entityid']) {
                    return redirect()
                        ->back()
                        ->with('status', __('entities.different_entityid'))
                        ->with('color', 'red');
                }

                switch ($result['code']) {
                    case '0':

                        $entity->update([
                            'name_en' => $updated_entity['name_en'],
                            'name_cs' => $updated_entity['name_cs'],
                            'description_en' => $updated_entity['description_en'],
                            'description_cs' => $updated_entity['description_cs'],
                            'cocov1' => $updated_entity['cocov1'],
                            'sirtfi' => $updated_entity['sirtfi'],
                            'metadata' => $updated_entity['metadata'],
                        ]);

                        if ($entity->type->value === 'idp') {
                            $entity->update(['rs' => $updated_entity['rs']]);
                        }

                        if (! $entity->wasChanged()) {
                            return redirect()
                                ->back()
                                ->with('status', __('entities.not_changed'));
                        }

                        Bus::chain([
                            new GitUpdateEntity($entity, Auth::user()),
                            function () use ($entity) {
                                $admins = User::activeAdmins()->select('id', 'email')->get();
                                Notification::send($entity->operators, new EntityUpdated($entity));
                                Notification::send($admins, new EntityUpdated($entity));
                            },
                        ])->dispatch();

                        return redirect()
                            ->route('entities.show', $entity)
                            ->with('status', __('entities.entity_updated'));

                        break;

                    case '1':
                        return redirect()
                            ->back()
                            ->with('status', "{$result['error']} {$result['message']}")
                            ->with('color', 'red');

                        break;

                    default:
                        return redirect()
                            ->back()
                            ->with('status', __('entities.unknown_error_while_registration'))
                            ->with('color', 'red');

                        break;
                }

                break;

            case 'state':
                $this->authorize('delete', $entity);

                if ($entity->trashed()) {
                    $entity->restore();

                    Bus::chain([
                        new GitAddEntity($entity, Auth::user()),
                        new GitAddToHfd($entity, Auth::user()),
                        new GitRestoreToEdugain($entity, Auth::user()),
                        new GitRestoreToCategory($entity, Auth::user()),
                        function () use ($entity) {
                            $admins = User::activeAdmins()->select('id', 'email')->get();
                            Notification::send($entity->operators, new EntityStateChanged($entity));
                            Notification::send($admins, new EntityStateChanged($entity));
                            if ($entity->hfd) {
                                Notification::send($entity->operators, new EntityAddedToHfd($entity));
                                Notification::send(User::activeAdmins()->select('id', 'email')->get(), new EntityAddedToHfd($entity));
                            }
                        },
                    ])->dispatch();

                    foreach ($entity->federations as $federation) {
                        Bus::chain([
                            new GitAddMember($federation, $entity, Auth::user()),
                            function () use ($federation, $entity) {
                                $admins = User::activeAdmins()->select('id', 'email')->get();
                                Notification::send($federation->operators, new FederationMemberChanged($federation, $entity, 'added'));
                                Notification::send($admins, new FederationMemberChanged($federation, $entity, 'added'));
                            },
                        ])->dispatch();
                    }
                } else {
                    $entity->delete();

                    Bus::chain([
                        new GitDeleteEntity($entity, Auth::user()),
                        new GitDeleteFromHfd($entity, Auth::user()),
                        new GitDeleteFromEdugain($entity, Auth::user()),
                        new GitDeleteFromCategory($entity->category ?? null, $entity, Auth::user()),
                        function () use ($entity) {
                            $admins = User::activeAdmins()->select('id', 'email')->get();
                            Notification::send($entity->operators, new EntityStateChanged($entity));
                            Notification::send($admins, new EntityStateChanged($entity));
                            if ($entity->hfd) {
                                Notification::send($entity->operators, new EntityDeletedFromHfd($entity));
                                Notification::send(User::activeAdmins()->select('id', 'email')->get(), new EntityDeletedFromHfd($entity));
                            }
                        },
                    ])->dispatch();
                }

                $state = $entity->trashed() ? 'deleted' : 'restored';
                $color = $entity->trashed() ? 'red' : 'green';

                $locale = app()->getLocale();

                return redirect()
                    ->route('entities.show', $entity)
                    ->with('status', __("entities.$state", ['name' => $entity->{"name_$locale"} ?? $entity->entityid]))
                    ->with('color', $color);

                break;

            case 'add_operators':
                $this->authorize('update', $entity);

                if (! request('operators')) {
                    return to_route('entities.operators', $entity)
                        ->with('status', __('entities.add_empty_operators'))
                        ->with('color', 'red');
                }

                $old_operators = $entity->operators;
                $new_operators = User::whereIn('id', request('operators'))->get();
                $entity->operators()->attach(request('operators'));

                $admins = User::activeAdmins()->select('id', 'email')->get();
                Notification::send($new_operators, new YourEntityRightsChanged($entity, 'added'));
                Notification::send($old_operators, new EntityOperatorsChanged($entity, $new_operators, 'added'));
                Notification::send($admins, new EntityOperatorsChanged($entity, $new_operators, 'added'));

                return redirect()
                    ->route('entities.show', $entity)
                    ->with('status', __('entities.operators_added'));

                break;

            case 'delete_operators':
                $this->authorize('update', $entity);

                if (! request('operators')) {
                    return to_route('entities.operators', $entity)
                        ->with('status', __('entities.delete_empty_operators'))
                        ->with('color', 'red');
                }

                $old_operators = User::whereIn('id', request('operators'))->get();
                $entity->operators()->detach(request('operators'));
                $new_operators = $entity->operators;

                $admins = User::activeAdmins()->select('id', 'email')->get();
                Notification::send($old_operators, new YourEntityRightsChanged($entity, 'deleted'));
                Notification::send($new_operators, new EntityOperatorsChanged($entity, $old_operators, 'deleted'));
                Notification::send($admins, new EntityOperatorsChanged($entity, $old_operators, 'deleted'));

                return redirect()
                    ->route('entities.show', $entity)
                    ->with('status', __('entities.operators_deleted'));

                break;

            case 'edugain':
                $this->authorize('update', $entity);

                $entity->edugain = $entity->edugain ? false : true;
                $entity->update();

                $status = $entity->edugain ? 'edugain' : 'no_edugain';
                $color = $entity->edugain ? 'green' : 'red';

                if ($entity->edugain) {
                    Bus::chain([
                        new GitAddToEdugain($entity, Auth::user()),
                        function () use ($entity) {
                            $admins = User::activeAdmins()->select('id', 'email')->get();
                            Notification::send($entity->operators, new EntityEdugainStatusChanged($entity));
                            Notification::send($admins, new EntityEdugainStatusChanged($entity));
                        },
                    ])->dispatch();
                } else {
                    Bus::chain([
                        new GitDeleteFromEdugain($entity, Auth::user()),
                        function () use ($entity) {
                            $admins = User::activeAdmins()->select('id', 'email')->get();
                            Notification::send($entity->operators, new EntityEdugainStatusChanged($entity));
                            Notification::send($admins, new EntityEdugainStatusChanged($entity));
                        },
                    ])->dispatch();
                }

                return redirect()
                    ->back()
                    ->with('status', __("entities.$status"))
                    ->with('color', $color);

                break;

            case 'rs':
                $this->authorize('do-everything');

                if ($entity->type->value !== 'sp') {
                    return redirect()
                        ->back()
                        ->with('status', __('categories.rs_controlled_for_sps_only'));
                }

                $entity->rs = $entity->rs ? false : true;
                $entity->update();

                $status = $entity->rs ? 'rs' : 'no_rs';
                $color = $entity->rs ? 'green' : 'red';

                if ($entity->rs) {
                    GitAddToRs::dispatch($entity, Auth::user());
                    Notification::send($entity->operators, new EntityAddedToRs($entity));
                    Notification::send(User::activeAdmins()->select('id', 'email')->get(), new EntityAddedToRs($entity));
                } else {
                    GitDeleteFromRs::dispatch($entity, Auth::user());
                    Notification::send($entity->operators, new EntityDeletedFromRs($entity));
                    Notification::send(User::activeAdmins()->select('id', 'email')->get(), new EntityDeletedFromRs($entity));
                }

                return redirect()
                    ->back()
                    ->with('status', __("entities.$status"))
                    ->with('color', $color);

                break;

            case 'category':
                $this->authorize('do-everything');

                if (empty(request('category'))) {
                    return redirect()
                        ->back()
                        ->with('status', __('categories.no_category_selected'))
                        ->with('color', 'red');
                }

                $old_category = $entity->category ?? null;
                $category = Category::findOrFail(request('category'));
                $entity->category()->associate($category);
                $entity->save();

                Bus::chain([
                    new GitDeleteFromCategory($old_category, $entity, Auth::user()),
                    new GitAddToCategory($category, $entity, Auth::user()),
                    function () use ($entity, $category) {
                        $admins = User::activeAdmins()->select('id', 'email')->get();
                        Notification::send($admins, new IdpCategoryChanged($entity, $category));
                    },
                ])->dispatch();

                if (! $entity->wasChanged()) {
                    return redirect()
                        ->back();
                }

                return redirect()
                    ->route('entities.show', $entity)
                    ->with('status', __('entities.category_updated'));

                break;

            case 'hfd':
                $this->authorize('do-everything');

                if ($entity->type->value !== 'idp') {
                    return redirect()
                        ->back()
                        ->with('status', __('categories.hfd_controlled_for_idps_only'));
                }

                $entity->hfd = $entity->hfd ? false : true;
                $entity->update();

                $status = $entity->hfd ? 'hfd' : 'no_hfd';
                $color = $entity->hfd ? 'red' : 'green';

                if ($entity->hfd) {
                    GitAddToHfd::dispatch($entity, Auth::user());
                    Notification::send($entity->operators, new EntityAddedToHfd($entity));
                    Notification::send(User::activeAdmins()->select('id', 'email')->get(), new EntityAddedToHfd($entity));
                } else {
                    GitDeleteFromHfd::dispatch($entity, Auth::user());
                    Notification::send($entity->operators, new EntityDeletedFromHfd($entity));
                    Notification::send(User::activeAdmins()->select('id', 'email')->get(), new EntityDeletedFromHfd($entity));
                }

                return redirect()
                    ->route('entities.show', $entity)
                    ->with('status', __("entities.$status"))
                    ->with('color', $color);

                break;

            default:
                return redirect()->back();
        }
    }

    public function join(JoinFederation $request, Entity $entity)
    {
        $this->authorize('update', $entity);

        if (empty(request('federation'))) {
            return back()
                ->with('status', __('entities.join_empty_federations'))
                ->with('color', 'red');
        }

        $entity
            ->federations()
            ->attach($request->input('federation'), [
                'requested_by' => Auth::id(),
                'explanation' => $request->input('explanation'),
            ]);

        return redirect()
            ->back()
            ->with('status', __('entities.join_requested', [
                'name' => Federation::findOrFail($request->input('federation'))->name,
            ]));
    }

    public function leave(Request $request, Entity $entity)
    {
        $this->authorize('update', $entity);

        if (empty(request('federations'))) {
            return back()
                ->with('status', __('entities.leave_empty_federations'))
                ->with('color', 'red');
        }

        $entity
            ->federations()
            ->detach($request->input('federations'));

        foreach (request('federations') as $f) {
            $federation = Federation::find($f);
            GitDeleteFromFederation::dispatch($entity, $federation, Auth::user());
            Notification::send($entity->operators, new EntityDeletedFromFederation($entity, $federation));
            Notification::send(User::activeAdmins()->select('id', 'email')->get(), new EntityDeletedFromFederation($entity, $federation));
        }

        return redirect()
            ->back()
            ->with('status', __('entities.federations_left'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Entity  $entity
     * @return \Illuminate\Http\Response
     */
    public function destroy(Entity $entity)
    {
        $this->authorize('forceDelete', $entity);

        $locale = app()->getLocale();
        $name = $entity->{"name_$locale"} ?? $entity->entityid;

        if (! app()->environment('testing')) {
            if ($entity->type->value === 'idp' && ! $entity->hfd) {
                $eduidczOrganization = EduidczOrganization::whereEntityIDofIdP($entity->entityid)->first();
                $eduidczOrganization->delete();
            }
        }

        $entity->forceDelete();

        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::send($admins, new EntityDestroyed($name));

        return redirect('entities')
            ->with('status', __('entities.destroyed', ['name' => $name]));
    }

    public function unknown()
    {
        $this->authorize('do-everything');

        $this->initializeGit();

        $xmlfiles = [];
        $tagfiles = [];
        foreach (Storage::files() as $file) {
            if (preg_match('/\.xml$/', $file)) {
                $xmlfiles[] = $file;
            }

            if (preg_match('/\.tag$/', $file)) {
                if (preg_match('/^'.config('git.edugain_tag').'$/', $file)) {
                    continue;
                }

                $federation = Federation::whereTagfile($file)->first();
                if ($federation === null && Storage::exists(preg_replace('/\.tag/', '.cfg', $file))) {
                    return redirect()
                        ->route('entities.index')
                        ->with('status', __('entities.missing_federations'))
                        ->with('color', 'red');
                } else {
                    $tagfiles[] = $file;
                }
            }
        }

        $tagfiles[] = config('git.edugain_tag');

        $entities = Entity::select('file')->get()->pluck('file')->toArray();

        $unknown = [];
        foreach ($xmlfiles as $xmlfile) {
            if (in_array($xmlfile, $entities)) {
                continue;
            }

            $metadata = Storage::get($xmlfile);
            $metadata = $this->parseMetadata($metadata);
            $entity = json_decode($metadata, true);

            $unknown[$xmlfile]['type'] = $entity['type'];
            $unknown[$xmlfile]['entityid'] = $entity['entityid'];
            $unknown[$xmlfile]['file'] = $xmlfile;
            $unknown[$xmlfile]['name_en'] = $entity['name_en'];
            $unknown[$xmlfile]['name_cs'] = $entity['name_cs'];
            $unknown[$xmlfile]['description_en'] = $entity['description_en'];
            $unknown[$xmlfile]['description_cs'] = $entity['description_cs'];

            foreach ($tagfiles as $tagfile) {
                $content = Storage::get($tagfile);
                $pattern = preg_quote($entity['entityid'], '/');
                $pattern = "/^$pattern\$/m";

                if (preg_match_all($pattern, $content)) {
                    if (strcmp($tagfile, config('git.edugain_tag')) === 0) {
                        $unknown[$xmlfile]['federations'][] = 'eduGAIN';

                        continue;
                    }

                    $federation = Federation::whereTagfile($tagfile)->first();
                    $unknown[$xmlfile]['federations'][] = $federation->name ?? null;
                }
            }
        }

        if (empty($unknown)) {
            return redirect()
                ->route('entities.index')
                ->with('status', __('entities.nothing_to_import'));
        }

        return view('entities.import', [
            'entities' => $unknown,
        ]);
    }

    public function import(Request $request)
    {
        $this->authorize('do-everything');

        if (empty(request('entities'))) {
            return back()
                ->with('status', __('entities.empty_import'))
                ->with('color', 'red');
        }

        // ADD SELECTED ENTITIES FROM XML FILES TO DATABASE
        $imported = 0;
        foreach (request('entities') as $xmlfile) {
            $xml_entity = $this->parseMetadata(Storage::get($xmlfile));
            $new_entity = json_decode($xml_entity, true);

            DB::transaction(function () use ($new_entity) {
                $entity = Entity::create($new_entity);

                $entity->approved = true;
                $entity->update();
            });

            $imported++;
        }

        // FIX FEDERATIONS MEMBERSHIP
        foreach (request('entities') as $xmlfile) {
            $entity = Entity::whereFile($xmlfile)->first();

            foreach (Federation::select('id', 'tagfile')->get() as $federation) {
                $members = Storage::get($federation->tagfile);
                $pattern = preg_quote($entity->entityid, '/');
                $pattern = "/^$pattern\$/m";

                if (! preg_match_all($pattern, $members)) {
                    continue;
                }

                $entity->federations()->attach($federation, [
                    'requested_by' => Auth::id(),
                    'approved_by' => Auth::id(),
                    'approved' => true,
                    'explanation' => 'Imported from Git repository.',
                ]);
            }
        }

        // FIX HIDE FROM DISCOVERY MEMBERSHIP
        $hfd = array_filter(preg_split("/\r\n|\r|\n/", Storage::get(config('git.hfd'))));
        foreach ($hfd as $entityid) {
            Entity::whereEntityid($entityid)->update(['hfd' => true]);
        }

        // FIX EDUGAIN MEMBERSHIP
        $edugain = array_filter(preg_split("/\r\n|\r|\n/", Storage::get(config('git.edugain_tag'))));
        foreach ($edugain as $entityid) {
            Entity::whereEntityid($entityid)->update(['edugain' => true]);
        }

        // FIX RESEARCH AND EDUCATION MEMBERSHIP
        $rs = array_filter(preg_split("/\r\n|\r|\n/", Storage::get(config('git.ec_rs'))));
        foreach ($rs as $entityid) {
            Entity::whereEntityid($entityid)->update(['rs' => true]);
        }

        return redirect('entities')
            ->with('status', trans_choice('entities.imported', $imported));
    }

    public function refresh()
    {
        $this->authorize('do-everything');

        $this->initializeGit();

        $xmlfiles = [];
        foreach (Storage::files() as $file) {
            if (preg_match('/\.xml/', $file)) {
                $xmlfiles[] = $file;
            }
        }

        $entities = Entity::select('file')->get()->pluck('file')->toArray();

        $refreshed = 0;
        foreach ($xmlfiles as $xmlfile) {
            if (! in_array($xmlfile, $entities)) {
                continue;
            }

            $metadata = Storage::get($xmlfile);
            $refreshed_entity = json_decode($this->parseMetadata($metadata), true);

            if ($refreshed_entity['type'] === 'sp') {
                unset($refreshed_entity['rs']);
            }

            $entity = Entity::whereFile($xmlfile)->first();
            $entity->update($refreshed_entity);

            $edugain = Storage::get(config('git.edugain_tag'));
            $pattern = preg_quote($refreshed_entity['entityid'], '/');
            $pattern = "/^$pattern\$/m";

            if (preg_match_all($pattern, $edugain)) {
                $entity->update(['edugain' => true]);
            } else {
                $entity->update(['edugain' => false]);
            }

            if ($entity->type->value === 'idp') {
                $hfd = Storage::get(config('git.hfd'));
                $pattern = preg_quote($refreshed_entity['entityid'], '/');
                $pattern = "/^$pattern\$/m";

                if (preg_match_all($pattern, $hfd)) {
                    $entity->update(['hfd' => true]);
                } else {
                    $entity->update(['hfd' => false]);
                }
            }

            if ($entity->type->value === 'sp') {
                $rs = Storage::get(config('git.ec_rs'));
                $pattern = preg_quote($refreshed_entity['entityid'], '/');
                $pattern = "/^$pattern\$/m";

                if (preg_match_all($pattern, $rs)) {
                    $entity->update(['rs' => true]);
                } else {
                    $entity->update(['rs' => false]);
                }
            }

            if ($entity->wasChanged()) {
                $refreshed++;
            }
        }

        return redirect('entities')
            ->with('status', trans_choice('entities.refreshed', $refreshed));
    }

    public function rs(Entity $entity)
    {
        $this->authorize('update', $entity);

        abort_unless($entity->federations()->where('xml_name', config('git.rs_federation'))->count(), 403, __('entities.rs_only_for_eduidcz_members'));

        Mail::to(config('mail.admin.address'))
            ->send(new AskRs($entity));

        return redirect()
            ->back()
            ->with('status', __('entities.rs_asked'));
    }

    public function metadata(Entity $entity)
    {
        $this->authorize('view', $entity);

        return Storage::download($entity->file);
    }

    public function showmetadata(Entity $entity)
    {
        $this->authorize('view', $entity);

        return response()->file(Storage::path($entity->file));
    }

    public function organization(Entity $entity, AssignOrganization $request)
    {
        $this->authorize('do-everything');

        abort_if($entity->type->value !== 'idp', 500);

        try {
            $organization = CesnetOrganization::select('dn')->whereDc($request->organization)->firstOrFail();
        } catch (\LdapRecord\Models\ModelNotFoundException) {
            abort(500);
        }

        $dom = new \DOMDocument();
        $dom->loadXML($entity->metadata);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNameSpace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $xpath->registerNameSpace('shibmd', 'urn:mace:shibboleth:metadata:1.0');
        $scope = $xpath->query('/md:EntityDescriptor/md:IDPSSODescriptor/md:Extensions/shibmd:Scope')->item(0)->nodeValue;

        $eduidczOrganization = EduidczOrganization::create([
            'dc' => now()->timestamp,
            'oPointer' => $organization->getDn(),
            'entityIDofIdP' => $entity->entityid,
            'eduIDczScope' => $scope,
        ]);

        abort_if(is_null($eduidczOrganization), 500);

        return to_route('entities.show', $entity)
            ->with('status', __('entities.organization_assigned'));
    }
}
