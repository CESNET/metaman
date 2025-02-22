<?php

namespace App\Http\Controllers;

use App\Models\Federation;
use App\Models\User;
use App\Notifications\FederationOperatorsChanged;
use App\Notifications\YourFederationRightsChanged;
use App\Services\NotificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

class FederationOperatorController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(Federation $federation): Factory|View|Application
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

    /**
     * Store a newly created resource in storage.
     *
     * @throws AuthorizationException
     */
    public function store(Federation $federation): RedirectResponse
    {
        $this->authorize('update', $federation);

        if (! request('operators')) {
            return to_route('federations.operators.index', $federation)
                ->with('status', __('federations.add_empty_operators'))
                ->with('color', 'red');
        }

        $old_operators = $federation->operators;
        $new_operators = User::whereIn('id', request('operators'))->get();
        $federation->operators()->attach(request('operators'));

        Notification::send($new_operators, new YourFederationRightsChanged($federation, 'added'));
        NotificationService::sendOperatorNotification($old_operators, new FederationOperatorsChanged($federation, $new_operators, 'added'));

        return redirect()
            ->route('federations.operators.index', $federation)
            ->with('status', __('federations.operators_added'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws AuthorizationException
     */
    public function destroy(Federation $federation): RedirectResponse
    {

        $this->authorize('update', $federation);

        if (! request('operators')) {
            return to_route('federations.operators.index', $federation)
                ->with('status', __('federations.delete_empty_operators'))
                ->with('color', 'red');
        }

        $old_operators = User::whereIn('id', request('operators'))->get();
        $federation->operators()->toggle(request('operators'));
        $new_operators = $federation->operators;

        Notification::send($old_operators, new YourFederationRightsChanged($federation, 'deleted'));
        NotificationService::sendOperatorNotification($new_operators, new FederationOperatorsChanged($federation, $old_operators, 'added'));

        return redirect()
            ->route('federations.operators.index', $federation)
            ->with('status', __('federations.operators_deleted'));
    }
}
