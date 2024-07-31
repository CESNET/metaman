<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFederation;
use App\Http\Requests\UpdateFederation;
use App\Jobs\GitDeleteMembers;
use App\Models\Entity;
use App\Models\Federation;
use App\Models\User;
use App\Notifications\FederationApproved;
use App\Notifications\FederationDestroyed;
use App\Notifications\FederationMembersChanged;
use App\Notifications\FederationOperatorsChanged;
use App\Notifications\FederationRejected;
use App\Notifications\FederationRequested;
use App\Notifications\FederationStateChanged;
use App\Notifications\FederationUpdated;
use App\Notifications\YourFederationRightsChanged;
use App\Services\NotificationService;
use App\Traits\GitTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class FederationController extends Controller
{
    use GitTrait;

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
                'xml_id' => $id,
                'xml_name' => "urn:mace:cesnet.cz:$id",
                'filters' => $id,
            ]));

            $federation->operators()->attach(Auth::id());

            return $federation;
        });

        $admins = User::activeAdmins()->select('id', 'email')->get();
        Notification::sendNow($admins, new FederationRequested($federation));

        return redirect('federations')
            ->with('status', __('federations.requested', ['name' => $federation->name]));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Federation $federation)
    {
        $this->authorize('view', $federation);

        return view('federations.show', [
            'federation' => $federation,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
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
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFederation $request, Federation $federation)
    {
        switch (request('action')) {
            case 'reject':
                $this->authorize('update', $federation);

                $name = $federation->name;
                NotificationService::sendModelNotification($federation, new FederationRejected($name));
                $federation->forceDelete();

                return redirect('federations')
                    ->with('status', __('federations.rejected', ['name' => $name]));

                break;

            case 'approve':
                $this->authorize('do-everything');

                $federation->approved = true;
                $federation->update();
                NotificationService::sendModelNotification($federation, new FederationApproved($federation));

                //GitAddFederation::dispatch($federation, 'approve', Auth::user());

                return redirect()
                    ->route('federations.show', $federation)
                    ->with('status', __('federations.approved', ['name' => $federation->name]));

            case 'update':

                $this->authorize('update', $federation);

                $validated = $request->validated();

                $id = $federation->name;
                if (isset($validated['name'])) {
                    $id = generateFederationID($validated['name']);
                }
                $additionalFilters = $request->input('sp_and_ip_feed', 0);
                $filters = $id;

                if ($additionalFilters) {
                    $filters .= ', '.$id.'+idp';
                    $filters .= ', '.$id.'+sp';
                }
                $validated['filters'] = $filters;
                $validated['additional_filters'] = $additionalFilters;

                $federation->update($validated);

                if (! $federation->wasChanged()) {
                    return redirect()
                        ->route('federations.show', $federation);
                }
                NotificationService::sendModelNotification($federation, new FederationUpdated($federation));
                //  GitUpdateFederation::dispatch($federation, Auth::user());

                return redirect()
                    ->route('federations.show', $federation)
                    ->with('status', __('federations.updated'));

                break;

            case 'state':
                $this->authorize('delete', $federation);

                $federation->trashed() ? $federation->restore() : $federation->delete();

                $state = $federation->trashed() ? 'deleted' : 'restored';
                $color = $federation->trashed() ? 'red' : 'green';

                NotificationService::sendModelNotification($federation, new FederationStateChanged($federation));

                /*                if ($federation->trashed()) {
                                    GitDeleteFederation::dispatch($federation, Auth::user());
                                } else {

                                    GitAddFederation::dispatch($federation, 'state', Auth::user());
                                }*/

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
                Notification::sendNow($new_operators, new YourFederationRightsChanged($federation, 'added'));
                NotificationService::sendOperatorNotification($old_operators, new FederationOperatorsChanged($federation, $new_operators, 'added'));

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
                Notification::sendNow($old_operators, new YourFederationRightsChanged($federation, 'deleted'));
                NotificationService::sendOperatorNotification($new_operators, new FederationOperatorsChanged($federation, $old_operators, 'added'));

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
                NotificationService::sendModelNotification($federation, new FederationMembersChanged($federation, $new_entities, 'added'));

                //TODO add members to federation
                //  GitAddMembers::dispatch($federation, $new_entities, Auth::user());

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
                //                GitDeleteMembers::dispatch($federation, $old_entities, Auth::user());
                NotificationService::sendModelNotification($federation, new FederationMembersChanged($federation, $old_entities, 'deleted'));

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
}
