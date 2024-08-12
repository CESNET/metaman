<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGroup;
use App\Models\Group;
use App\Models\User;
use App\Notifications\GroupDeleted;
use App\Notifications\GroupUpdated;
use App\Traits\GitTrait;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class GroupController extends Controller
{
    use GitTrait;

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
     * Display the specified resource.
     *
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
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

        Notification::send(User::activeAdmins()->select('id', 'email')->get(), new GroupUpdated($group));

        return redirect()
            ->route('groups.show', $group)
            ->with('status', __('groups.updated', ['name' => $old_group]));
    }

    /**
     * Remove the specified resource from storage.
     *
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

        Notification::send(User::activeAdmins()->select('id', 'email')->get(), new GroupDeleted($name));

        return redirect('groups')
            ->with('status', __('groups.deleted', ['name' => $name]));
    }
}
