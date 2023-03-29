<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Federation;

class FederationEntityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

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
}
