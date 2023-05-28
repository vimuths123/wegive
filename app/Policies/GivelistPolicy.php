<?php

namespace App\Policies;

use App\Models\Givelist;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GivelistPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->admin) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Givelist  $givelist
     * @return mixed
     */
    public function view(User $user, Givelist $givelist)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Givelist  $givelist
     * @return mixed
     */
    public function update(User $user, Givelist $givelist)
    {
        return $givelist->creator()->is($user->currentLogin);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Givelist  $givelist
     * @return mixed
     */
    public function delete(User $user, Givelist $givelist)
    {
        return $givelist->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Givelist  $givelist
     * @return mixed
     */
    public function restore(User $user, Givelist $givelist)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Givelist  $givelist
     * @return mixed
     */
    public function forceDelete(User $user, Givelist $givelist)
    {
        //
    }
}
