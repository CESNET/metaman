<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntity;
use App\Jobs\GitAddToHfd;
use App\Jobs\GitDeleteFromHfd;
use App\Ldap\CesnetOrganization;
use App\Ldap\EduidczOrganization;
use App\Mail\NewIdentityProvider;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\User;
use App\Notifications\EntityAddedToHfd;
use App\Notifications\EntityDeletedFromHfd;
use App\Notifications\EntityDestroyed;
use App\Notifications\EntityRequested;
use App\Notifications\EntityUpdated;
use App\Traits\DumpFromGit\EntitiesHelp\DeleteFromEntity;
use App\Traits\DumpFromGit\EntitiesHelp\UpdateEntity;
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
    use DeleteFromEntity,UpdateEntity;
    use GitTrait, ValidatorTrait;

    public function __construct()
    {

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
                    $new_entity = array_merge($new_entity, ['xml_file' => $this->deleteTags($new_entity['metadata'])]);

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

    /**
     * Show the form for editing the specified resource.
     *
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

                        $xml_file = $this->deleteTags($updated_entity['metadata']);

                        DB::transaction(function () use ($entity, $updated_entity, $xml_file) {
                            $entity->update([
                                'name_en' => $updated_entity['name_en'],
                                'name_cs' => $updated_entity['name_cs'],
                                'description_en' => $updated_entity['description_en'],
                                'description_cs' => $updated_entity['description_cs'],
                                'cocov1' => $updated_entity['cocov1'],
                                'sirtfi' => $updated_entity['sirtfi'],
                                'metadata' => $updated_entity['metadata'],
                                'xml_file' => $xml_file,
                            ]);

                            if ($entity->type->value === 'idp') {
                                $entity->update(['rs' => $updated_entity['rs']]);
                            }
                        });

                        if (! $entity->wasChanged()) {
                            return redirect()
                                ->back()
                                ->with('status', __('entities.not_changed'));
                        }

                        // TODO entityUpdated (functional)
                        /*                        Bus::chain([
                                                    new GitUpdateEntity($entity, Auth::user()),
                                                    function () use ($entity) {
                                                        $admins = User::activeAdmins()->select('id', 'email')->get();
                                                        Notification::send($entity->operators, new EntityUpdated($entity));
                                                        Notification::send($admins, new EntityUpdated($entity));
                                                    },
                                                ])->dispatch();*/

                        return redirect()
                            ->route('entities.show', $entity)
                            ->with('status', __('entities.entity_updated')." {$result['message']}");

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

            case 'hfd':
                $this->authorize('do-everything');

                if ($entity->type->value !== 'idp') {
                    return redirect()
                        ->back()
                        ->with('status', __('categories.hfd_controlled_for_idps_only'));
                }

                $entity = DB::transaction(function () use ($entity) {
                    $entity->hfd = $entity->hfd ? false : true;
                    $entity->update();

                    return $entity;
                });

                $status = $entity->hfd ? 'hfd' : 'no_hfd';
                $color = $entity->hfd ? 'red' : 'green';

                //TODO change HfD status (not working)
                /*                if ($entity->hfd) {
                                    GitAddToHfd::dispatch($entity, Auth::user());
                                    Notification::send($entity->operators, new EntityAddedToHfd($entity));
                                    Notification::send(User::activeAdmins()->select('id', 'email')->get(), new EntityAddedToHfd($entity));
                                } else {
                                    GitDeleteFromHfd::dispatch($entity, Auth::user());
                                    Mail::to(config('mail.ra.address'))->send(new NewIdentityProvider($entity));
                                    Notification::send($entity->operators, new EntityDeletedFromHfd($entity));
                                    Notification::send(User::activeAdmins()->select('id', 'email')->get(), new EntityDeletedFromHfd($entity));
                                }*/

                return redirect()
                    ->route('entities.show', $entity)
                    ->with('status', __("entities.$status"))
                    ->with('color', $color);

                break;

            default:
                return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
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
                if (! is_null($eduidczOrganization)) {
                    $eduidczOrganization->delete();
                }
            }
        }

        $entity->forceDelete();

        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::sendNow($admins, new EntityDestroyed($name));

        return redirect('entities')
            ->with('status', __('entities.destroyed', ['name' => $name]));
    }
}
