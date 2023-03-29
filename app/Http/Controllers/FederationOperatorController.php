<?php

namespace App\Http\Controllers;

use App\Models\Federation;
use App\Models\User;

class FederationOperatorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Federation $federation)
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
}
