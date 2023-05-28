<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddressPolicy
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
        return in_array($user->email, [
            'charlie@givelistapp.com',
            'jonathan@givelistapp.com',
            'charleswminderhout@gmail.com',
            'jonathanbeck@gmail.com',
        ]);
    }

    public function view(User $user)
    {
        return in_array($user->email, [
            'charlie@givelistapp.com',
            'jonathan@givelistapp.com',
            'charleswminderhout@gmail.com',
            'jonathanbeck@gmail.com',
        ]);
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Address $address)
    {
        $donor = $address->addressable;

        return $user->logins()->where('loginable_id', $donor->id)->where('loginable_type', $donor->getMorphClass());
    }

    public function delete(User $user, Address $address)
    {
        $donor = $address->addressable;

        return $user->logins()->where('loginable_id', $donor->id)->where('loginable_type', $donor->getMorphClass());
    }

    public function restore(User $user, Address $address)
    {
        return false;
    }

    public function forceDelete(User $user, Address $address)
    {
        return false;
    }
}
