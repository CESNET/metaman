<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Group;
use App\Traits\DumpFromGit\EntitiesHelp\UpdateEntity;
use DOMException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;

class EntityGroupController extends Controller
{
    use UpdateEntity;

    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizationException
     */
    public function index(Entity $entity): Factory|Application|View
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
     *
     * @throws AuthorizationException
     * @throws DOMException
     */
    public function store(Entity $entity): RedirectResponse
    {
        $this->authorize('do-everything');

        if (empty(request('group'))) {
            return back()
                ->with('status', __('entities.join_empty_group'))
                ->with('color', 'red');
        }
        $entity->groups()->attach(request('group'));
        $groupLink = Group::find(request('group'))->pluck('xml_value')->toArray();

        $xml_val = $this->updateXmlGroups($entity->xml_file, $groupLink);
        $entity->update(['xml_file' => $xml_val]);

        return redirect()
            ->back()
            ->with('status', __('entities.join_group'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws AuthorizationException
     */
    public function destroy(Entity $entity): RedirectResponse
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

        $groupLink = Group::find(request('groups'))->pluck('xml_value')->toArray();
        $xml_val = $this->deleteXmlGroups($entity->xml_file, $groupLink);
        $entity->update(['xml_file' => $xml_val]);

        return redirect()
            ->back()
            ->with('status', __('entities.leave_group'));

    }
}
