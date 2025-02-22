<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizationException
     */
    public function index(): Factory|View|Application
    {
        $this->authorize('do-everything');

        return view('groups.index');
    }

    /**
     * Display the specified resource.
     *
     * @throws AuthorizationException
     */
    public function show(Group $group): Factory|View|Application
    {
        $this->authorize('do-everything');

        return view('groups.show', compact('group'));
    }
}
