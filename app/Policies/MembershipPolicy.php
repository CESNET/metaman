<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class MembershipPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Membership $membership)
    {
        if ($user->admin) {
            return true;
        }

        if (in_array(Auth::id(), $membership->federation->load('operators')->operators->pluck('id')->toArray())) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Membership $membership)
    {
        if ($user->admin) {
            return true;
        }

        if (in_array(Auth::id(), $membership->federation->load('operators')->operators->pluck('id')->toArray())) {
            return true;
        }
    }
}
