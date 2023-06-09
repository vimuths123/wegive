<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Donor;
use Illuminate\Auth\Access\HandlesAuthorization;

class DonorPolicy
{
    use HandlesAuthorization;

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
     * @param  \App\Models\Donor  $donor
     * @return mixed
     */
    public function view(User $user, Donor $donor)
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
     * @param  \App\Models\Donor  $donor
     * @return mixed
     */
    public function update(User $user, Donor $donor)
    {
        return true;
        // return $user->logins()->where('loginable_type', 'donor')->where('loginable_id', $donor->id)->first();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Donor  $donor
     * @return mixed
     */
    public function delete(User $user, Donor $donor)
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Donor  $donor
     * @return mixed
     */
    public function restore(User $user, Donor $donor)
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Donor  $donor
     * @return mixed
     */
    public function forceDelete(User $user, Donor $donor)
    {
        return true;
    }
}
