<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroup;
use App\Http\Requests\UpdateGroup;
use App\Jobs\GitAddGroup;
use App\Jobs\GitDeleteGroup;
use App\Jobs\GitUpdateGroup;
use App\Models\Group;
use App\Models\User;
use App\Notifications\GroupCreated;
use App\Notifications\GroupDeleted;
use App\Notifications\GroupUpdated;
use App\Traits\GitTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class GroupController extends Controller
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
        $this->authorize('do-everything');

        return view('groups.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('do-everything');

        return view('groups.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreGroup $request)
    {
        $this->authorize('do-everything');

        $validated = $request->validated();
        $group = Group::create(array_merge(
            $validated,
            ['tagfile' => generateFederationID($validated['name']).'.tag'],
        ));

        GitAddGroup::dispatch($group, Auth::user());
        Notification::send(User::activeAdmins()->select('id', 'email')->get(), new GroupCreated($group));

        return redirect('groups')
            ->with('status', __('groups.added', ['name' => $group->name]));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function show(Group $group)
    {
        $this->authorize('do-everything');

        return view('groups.show', [
            'group' => $group,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function edit(Group $group)
    {
        $this->authorize('do-everything');

        return view('groups.edit', [
            'group' => $group,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateGroup $request, Group $group)
    {
        $this->authorize('do-everything');

        $old_group = $group->tagfile;
        $group->update($request->validated());

        if (! $group->wasChanged()) {
            return redirect()
                ->route('groups.show', $group);
        }

        GitUpdateGroup::dispatch($old_group, $group, Auth::user());
        Notification::send(User::activeAdmins()->select('id', 'email')->get(), new GroupUpdated($group));

        return redirect()
            ->route('groups.show', $group)
            ->with('status', __('groups.updated', ['name' => $old_group]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group)
    {
        $this->authorize('do-everything');

        if ($group->entities->count() !== 0) {
            return redirect()
                ->route('groups.show', $group)
                ->with('status', __('groups.delete_empty'))
                ->with('color', 'red');
        }

        $name = $group->tagfile;
        $group->delete();

        GitDeleteGroup::dispatch($name, Auth::user());
        Notification::send(User::activeAdmins()->select('id', 'email')->get(), new GroupDeleted($name));

        return redirect('groups')
            ->with('status', __('groups.deleted', ['name' => $name]));
    }
}
