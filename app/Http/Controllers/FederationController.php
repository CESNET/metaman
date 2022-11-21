<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFederation;
use App\Http\Requests\UpdateFederation;
use App\Jobs\GitAddFederation;
use App\Jobs\GitAddMembers;
use App\Jobs\GitDeleteFederation;
use App\Jobs\GitDeleteMembers;
use App\Jobs\GitUpdateFederation;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\Membership;
use App\Models\User;
use App\Notifications\FederationCancelled;
use App\Notifications\FederationDestroyed;
use App\Notifications\FederationOperatorsChanged;
use App\Notifications\FederationRejected;
use App\Notifications\FederationRequested;
use App\Notifications\FederationStatusChanged;
use App\Notifications\YourFederationRightsChanged;
use App\Traits\GitTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class FederationController extends Controller
{
    use GitTrait;

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
        $this->authorize('viewAny', Federation::class);

        return view('federations.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Federation::class);

        return view('federations.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFederation $request)
    {
        $this->authorize('create', Federation::class);

        $validated = $request->validated();
        $id = generateFederationID($validated['name']);
        $federation = DB::transaction(function () use ($validated, $id) {
            $federation = Federation::create(array_merge($validated, [
                'tagfile' => "$id.tag",
                'cfgfile' => "$id.cfg",
                'xml_id' => $id,
                'xml_name' => "urn:mace:cesnet.cz:$id",
                'filters' => $id,
            ]));

            $federation->operators()->attach(Auth::id());

            return $federation;
        });

        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::send($admins, new FederationRequested($federation));

        return redirect('federations')
            ->with('status', __('federations.requested', ['name' => $federation->name]));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Federation  $federation
     * @return \Illuminate\Http\Response
     */
    public function show(Federation $federation)
    {
        $this->authorize('view', $federation);

        return view('federations.show', [
            'federation' => $federation,
        ]);
    }

    public function operators(Federation $federation)
    {
        $this->authorize('view', $federation);

        $operators = $federation->operators()->paginate(10, ['*'], 'operatorsPage');
        $ops = $federation->operators->pluck('id');
        $users = User::orderBy('name')
            ->whereNotIn('id', $ops)
            ->search(request('search'))
            ->paginate(10, ['*'], 'usersPage');

        return view('federations.operators', [
            'federation' => $federation,
            'operators' => $operators,
            'users' => $users,
        ]);
    }

    public function entities(Federation $federation)
    {
        $this->authorize('view', $federation);

        $locale = app()->getLocale();

        $members = $federation->entities()->orderBy("name_$locale")->paginate(10, ['*'], 'membersPage');
        $ids = $federation->entities->pluck('id');
        $entities = Entity::orderBy("name_$locale")
            ->whereNotIn('id', $ids)
            ->search(request('search'))
            ->paginate(10, ['*'], 'usersPage');

        return view('federations.entities', [
            'federation' => $federation,
            'members' => $members,
            'entities' => $entities,
        ]);
    }

    public function requests(Federation $federation)
    {
        $this->authorize('update', $federation);

        $joins = Membership::with('entity:id,entityid,name_en,name_cs', 'requester:id,name')
            ->where('federation_id', $federation->id)
            ->whereApproved(false)
            ->get();

        return view('federations.requests', [
            'federation' => $federation,
            'joins' => $joins,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Federation  $federation
     * @return \Illuminate\Http\Response
     */
    public function edit(Federation $federation)
    {
        $this->authorize('update', $federation);

        return view('federations.edit', [
            'federation' => $federation,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Federation  $federation
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFederation $request, Federation $federation)
    {
        switch (request('action')) {
            case 'reject':
                $this->authorize('update', $federation);

                $name = $federation->name;
                $operators = $federation->operators;
                $admins = User::activeAdmins()->select('id', 'email')->get();
                $federation->forceDelete();

                Notification::send($operators, new FederationRejected($name));
                Notification::send($admins, new FederationCancelled($name));

                return redirect('federations')
                    ->with('status', __('federations.rejected', ['name' => $name]));

                break;

            case 'approve':
                $this->authorize('do-everything');

                $federation->approved = true;
                $federation->active = true;
                $federation->update();

                GitAddFederation::dispatch($federation, 'approve', Auth::user());

                return redirect()
                    ->route('federations.show', $federation)
                    ->with('status', __('federations.approved', ['name' => $federation->name]));

            case 'update':
                $this->authorize('update', $federation);

                $validated = $request->validated();
                $federation->update($validated);

                if (! $federation->wasChanged()) {
                    return redirect()
                        ->route('federations.show', $federation);
                }

                GitUpdateFederation::dispatch($federation, Auth::user());

                return redirect()
                    ->route('federations.show', $federation)
                    ->with('status', __('federations.updated'));

                break;

            case 'status':
                $this->authorize('update', $federation);

                $federation->active = $federation->active ? false : true;
                $federation->update();

                $status = $federation->active ? 'active' : 'inactive';
                $color = $federation->active ? 'green' : 'red';

                $admins = User::activeAdmins()->select('id', 'email')->get();
                Notification::send($federation->operators, new FederationStatusChanged($federation));
                Notification::send($admins, new FederationStatusChanged($federation));

                return redirect()
                    ->route('federations.show', $federation)
                    ->with('status', __("federations.$status", ['name' => $federation->name]))
                    ->with('color', $color);

                break;

            case 'state':
                $this->authorize('delete', $federation);

                $federation->trashed() ? $federation->restore() : $federation->delete();

                $state = $federation->trashed() ? 'deleted' : 'restored';
                $color = $federation->trashed() ? 'red' : 'green';

                if ($federation->trashed()) {
                    GitDeleteFederation::dispatch($federation, Auth::user());
                } else {
                    GitAddFederation::dispatch($federation, 'state', Auth::user());
                }

                return redirect()
                    ->route('federations.show', $federation)
                    ->with('status', __("federations.$state", ['name' => $federation->name]))
                    ->with('color', $color);

                break;

            case 'add_operators':
                $this->authorize('update', $federation);

                if (! request('operators')) {
                    return to_route('federations.operators', $federation)
                        ->with('status', __('federations.add_empty_operators'))
                        ->with('color', 'red');
                }

                $old_operators = $federation->operators;
                $new_operators = User::whereIn('id', request('operators'))->get();
                $federation->operators()->attach(request('operators'));

                $admins = User::activeAdmins()->select('id', 'email')->get();
                Notification::send($new_operators, new YourFederationRightsChanged($federation, 'added'));
                Notification::send($old_operators, new FederationOperatorsChanged($federation, $new_operators, 'added'));
                Notification::send($admins, new FederationOperatorsChanged($federation, $new_operators, 'added'));

                return redirect()
                    ->route('federations.operators', $federation)
                    ->with('status', __('federations.operators_added'));

                break;

            case 'delete_operators':
                $this->authorize('update', $federation);

                if (! request('operators')) {
                    return to_route('federations.operators', $federation)
                        ->with('status', __('federations.delete_empty_operators'))
                        ->with('color', 'red');
                }

                $old_operators = User::whereIn('id', request('operators'))->get();
                $federation->operators()->toggle(request('operators'));
                $new_operators = $federation->operators;

                $admins = User::activeAdmins()->select('id', 'email')->get();
                Notification::send($old_operators, new YourFederationRightsChanged($federation, 'deleted'));
                Notification::send($new_operators, new FederationOperatorsChanged($federation, $old_operators, 'deleted'));
                Notification::send($admins, new FederationOperatorsChanged($federation, $old_operators, 'deleted'));

                return redirect()
                    ->route('federations.operators', $federation)
                    ->with('status', __('federations.operators_deleted'));

                break;

            case 'add_entities':
                $this->authorize('update', $federation);

                if (! request('entities')) {
                    return to_route('federations.entities', $federation)
                        ->with('status', __('federations.add_empty_entities'))
                        ->with('color', 'red');
                }

                $explanation = "Operator's decision";
                $federation->entities()->attach(request('entities'), [
                    'requested_by' => Auth::id(),
                    'approved_by' => Auth::id(),
                    'approved' => true,
                    'explanation' => $explanation,
                ]);

                $new_entities = Entity::whereIn('id', request('entities'))->get();
                GitAddMembers::dispatch($federation, $new_entities, Auth::user());

                return redirect()
                    ->route('federations.entities', $federation)
                    ->with('status', __('federations.entities_added'));

                break;

            case 'delete_entities':
                $this->authorize('update', $federation);

                if (! request('entities')) {
                    return to_route('federations.entities', $federation)
                        ->with('status', __('federations.delete_empty_entities'))
                        ->with('color', 'red');
                }

                $federation->entities()->detach(request('entities'));

                $old_entities = Entity::whereIn('id', request('entities'))->get();
                GitDeleteMembers::dispatch($federation, $old_entities, Auth::user());

                return redirect()
                    ->route('federations.entities', $federation)
                    ->with('status', __('federations.entities_deleted'));

                break;

            default:
                return redirect()->route('federations.show', $federation);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Federation  $federation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Federation $federation)
    {
        $this->authorize('delete', $federation);

        $name = $federation->name;
        $federation->forceDelete();

        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::send($admins, new FederationDestroyed($name));

        return redirect('federations')
            ->with('status', __('federations.destroyed', ['name' => $federation->name]));
    }

    public function unknown()
    {
        $this->authorize('do-everything');

        $this->initializeGit();

        $cfgfiles = [];
        foreach (Storage::files() as $file) {
            if (preg_match('/^'.config('git.edugain_cfg').'$/', $file)) {
                continue;
            }

            if (preg_match('/\.cfg$/', $file)) {
                $cfgfiles[] = $file;
            }
        }

        $federations = Federation::select('cfgfile')->get()->pluck('cfgfile')->toArray();

        $unknown = [];
        foreach ($cfgfiles as $cfgfile) {
            if (in_array($cfgfile, $federations)) {
                continue;
            }

            $content = Storage::get($cfgfile);
            preg_match('/\[(.*)\]/', $content, $xml_id);
            preg_match('/filters\s*=\s*(.*)/', $content, $filters);
            preg_match('/name\s*=\s*(.*)/', $content, $name);

            $unknown[$cfgfile]['cfgfile'] = $cfgfile;
            $unknown[$cfgfile]['xml_id'] = $xml_id[1];
            $unknown[$cfgfile]['filters'] = $filters[1];
            $unknown[$cfgfile]['name'] = $name[1];
        }

        if (empty($unknown)) {
            return redirect()
                ->route('federations.index')
                ->with('status', __('federations.nothing_to_import'));
        }

        return view('federations.import', [
            'federations' => $unknown,
        ]);
    }

    public function import(Request $request)
    {
        $this->authorize('do-everything');

        if (empty(request('federations'))) {
            return back()
                ->with('status', __('federations.empty_import'))
                ->with('color', 'red');
        }

        $imported = 0;
        $names = request('names');
        $descriptions = request('descriptions');
        foreach (request('federations') as $cfgfile) {
            $content = Storage::get($cfgfile);
            preg_match('/\[(.*)\]/', $content, $xml_id);
            preg_match('/filters\s*=\s*(.*)/', $content, $filters);
            preg_match('/name\s*=\s*(.*)/', $content, $xml_name);

            if (empty($names[$cfgfile])) {
                $names[$cfgfile] = preg_replace('/\.cfg$/', '', $cfgfile);
            }

            if (empty($descriptions[$cfgfile])) {
                $descriptions[$cfgfile] = preg_replace('/\.cfg$/', '', $cfgfile);
            }

            DB::transaction(function () use ($cfgfile, $names, $descriptions, $xml_id, $xml_name, $filters) {
                $federation = Federation::create([
                    'name' => $names[$cfgfile],
                    'description' => $descriptions[$cfgfile],
                    'tagfile' => preg_replace('/\.cfg$/', '.tag', $cfgfile),
                    'cfgfile' => $cfgfile,
                    'xml_id' => $xml_id[1],
                    'xml_name' => $xml_name[1],
                    'filters' => $filters[1],
                    'explanation' => 'Imported from Git repository.',
                ]);

                $federation->approved = true;
                $federation->active = false;
                $federation->update();
            });

            // FIXME: Notification

            $imported++;
        }

        return redirect('federations')
            ->with('status', trans_choice('federations.imported', $imported));
    }

    public function refresh()
    {
        $this->authorize('do-everything');

        $this->initializeGit();

        $cfgfiles = [];
        foreach (Storage::files() as $file) {
            if (preg_match('/\.cfg/', $file)) {
                $cfgfiles[] = $file;
            }
        }

        $federations = Federation::select('cfgfile')->get()->pluck('cfgfile')->toArray();

        $refreshed = 0;
        foreach ($cfgfiles as $cfgfile) {
            if (! in_array($cfgfile, $federations)) {
                continue;
            }

            $content = Storage::get($cfgfile);
            preg_match('/\[(.*)\]/', $content, $xml_id);
            preg_match('/filters\s*=\s*(.*)/', $content, $filters);
            preg_match('/name\s*=\s*(.*)/', $content, $xml_name);

            $federation = Federation::whereCfgfile($cfgfile)->first();
            $federation->update([
                'xml_id' => $xml_id[1],
                'xml_name' => $xml_name[1],
                'filters' => $filters[1],
            ]);

            if ($federation->wasChanged()) {
                $refreshed++;
            }
        }

        return redirect('federations')
            ->with('status', trans_choice('federations.refreshed', $refreshed));
    }
}
