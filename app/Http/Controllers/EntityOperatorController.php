<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\User;

class EntityOperatorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Entity $entity)
    {
        $this->authorize('view', $entity);

        $operators = $entity->operators()->paginate(10, ['*'], 'operatorsPage');
        $ops = $entity->operators->pluck('id');
        $users = User::orderBy('name')
            ->whereNotIn('id', $ops)
            ->search(request('search'))
            ->paginate(10, ['*'], 'usersPage');

        return view('entities.operators', [
            'entity' => $entity,
            'operators' => $operators,
            'users' => $users,
        ]);
    }
}
