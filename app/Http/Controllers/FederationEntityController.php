<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Federation;
use App\Notifications\FederationMembersChanged;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class FederationEntityController extends Controller
{
    public function index(Federation $federation)
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Federation $federation)
    {
        $this->authorize('update', $federation);

        if (! request('entities')) {
            return to_route('federations.entities.index', $federation)
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

        return redirect()
            ->route('federations.entities.index', $federation)
            ->with('status', __('federations.entities_added'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Federation $federation)
    {
        $this->authorize('update', $federation);

        if (! request('entities')) {
            return to_route('federations.entities.index', $federation)
                ->with('status', __('federations.delete_empty_entities'))
                ->with('color', 'red');
        }

        $federation->entities()->detach(request('entities'));

        $old_entities = Entity::whereIn('id', request('entities'))->get();
        NotificationService::sendModelNotification($federation, new FederationMembersChanged($federation, $old_entities, 'deleted'));

        return redirect()
            ->route('federations.entities.index', $federation)
            ->with('status', __('federations.entities_deleted'));
    }
}
