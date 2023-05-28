<?php

namespace App\Policies;

use App\Models\Checkout;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CheckoutPolicy
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
     * @param \App\Models\User $user
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

    /**
     * Determine whether the user can view the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Checkout $checkout
     * @return bool
     */
    public function view(User $user, Checkout $checkout)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Checkout $checkout
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    public function update(User $user, Checkout $checkout)
    {
        return $user->logins()->where('loginable_type', $checkout->recipient->getMorphClass())->where('loginable_id', $checkout->recipient->id)->first();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Checkout $checkout
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     */
    public function delete(User $user, Checkout $checkout)
    {
        return $user->logins()->where('loginable_type', $checkout->recipient->getMorphClass())->where('loginable_id', $checkout->recipient->id)->first();
    }
}
