<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\User;
use App\Notifications\EntityOperatorsChanged;
use App\Notifications\YourEntityRightsChanged;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Notification;

class EntityOperatorController extends Controller
{
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Entity $entity)
    {
        $this->authorize('update', $entity);

        if (! request('operators')) {
            return to_route('entities.operators.index', $entity)
                ->with('status', __('entities.add_empty_operators'))
                ->with('color', 'red');
        }

        $old_operators = $entity->operators;
        $new_operators = User::whereIn('id', request('operators'))->get();
        $entity->operators()->attach(request('operators'));

        Notification::sendNow($new_operators, new YourEntityRightsChanged($entity, 'added'));
        NotificationService::sendOperatorNotification($old_operators, new EntityOperatorsChanged($entity, $new_operators, 'added'));

        return redirect()
            ->route('entities.operators.index', $entity)
            ->with('status', __('entities.operators_added'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Entity $entity)
    {
        $this->authorize('update', $entity);

        if (! request('operators')) {
            return to_route('entities.operators.index', $entity)
                ->with('status', __('entities.delete_empty_operators'))
                ->with('color', 'red');
        }

        $old_operators = User::whereIn('id', request('operators'))->get();
        $entity->operators()->detach(request('operators'));

        Notification::sendNow($old_operators, new YourEntityRightsChanged($entity, 'deleted'));
        NotificationService::sendOperatorNotification($old_operators, new EntityOperatorsChanged($entity, $old_operators, 'deleted'));

        return redirect()
            ->route('entities.operators.index', $entity)
            ->with('status', __('entities.operators_deleted'));

    }
}
