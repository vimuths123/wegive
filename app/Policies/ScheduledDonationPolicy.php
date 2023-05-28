<?php

namespace App\Policies;

use App\Models\ScheduledDonation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScheduledDonationPolicy
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
     * @param  \App\Models\ScheduledDonation  $scheduledDonation
     * @return mixed
     */
    public function view(User $user, ScheduledDonation $scheduledDonation)
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
     * @param  \App\Models\ScheduledDonation  $scheduledDonation
     * @return mixed
     */
    public function update(User $user, ScheduledDonation $scheduledDonation)
    {

        return $scheduledDonation->source()->is($user);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ScheduledDonation  $scheduledDonation
     * @return mixed
     */
    public function delete(User $user, ScheduledDonation $scheduledDonation)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ScheduledDonation  $scheduledDonation
     * @return mixed
     */
    public function restore(User $user, ScheduledDonation $scheduledDonation)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ScheduledDonation  $scheduledDonation
     * @return mixed
     */
    public function forceDelete(User $user, ScheduledDonation $scheduledDonation)
    {
        //
    }
}
