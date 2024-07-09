<?php

namespace App\Policies;

use App\Models\Federation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class FederationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->active;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, Federation $federation)
    {
        return $user->active;
    }

    /**
     * Determine whether the user can create models.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->active;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, Federation $federation)
    {
        if ($user->admin) {
            return true;
        }

        if (in_array(Auth::id(), $federation->operators->pluck('id')->toArray())) {
            return $user->active;
        }
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, Federation $federation)
    {
        if ($user->admin) {
            return true;
        }

        if (in_array(Auth::id(), $federation->operators->pluck('id')->toArray())) {
            return $user->active;
        }
    }
}
