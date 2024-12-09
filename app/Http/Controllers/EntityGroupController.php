<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Group;
use Illuminate\Http\Request;

class EntityGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Entity $entity)
    {
        $this->authorize('do-everything');

        $groups = $entity->groups() ? $entity->groups()->get() : collect();
        $joinable = Group::orderBy('name')
            ->whereNotIn('id', $groups->pluck('id'))
            ->get();

        return view('entities.groups', [
            'entity' => $entity,
            'groups' => $groups,
            'joinable' => $joinable,
        ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Entity $entity)
    {
        $this->authorize('do-everything');

        if (empty(request('group'))) {
            return back()
                ->with('status', __('entities.join_empty_group'))
                ->with('color', 'red');
        }

        $entity->groups()->attach(request('group'));

        return redirect()
            ->back()
            ->with('status', __('entities.join_group'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Entity $entity)
    {
        $this->authorize('do-everything');

        if (empty(request('groups'))) {
            return back()
                ->with('status', __('entities.leave_empty_group'))
                ->with('color', 'red');
        }
        $entity
            ->groups()
            ->detach(request('groups'));

        return redirect()
            ->back()
            ->with('status', __('entities.leave_group'));

    }
}
