<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Enable the use of ?authenticate_as={ID} to auto log as a user (Only works locally)
        if (app()->environment('local') && request()->has('authenticate_as')) {
            if (is_null($user = User::find(request()->authenticate_as))) {
                abort(403, 'User unknown');
            }

            Auth::guard('sanctum')->setUser($user);
        }
    }
}
