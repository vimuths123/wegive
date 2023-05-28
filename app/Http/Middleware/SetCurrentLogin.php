<?php

namespace App\Http\Middleware;

use App\Models\Donor;
use App\Models\Login;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class SetCurrentLogin
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (is_null($user = $request->user())) {
            return $next($request);
        }

        if ($request->headers->get('app') === 'dashboard') {
            self::checkDashboardCurrentLogin($user);
        } else {
            self::checkCurrentLogin($user, $request->headers->get('organization'));
        }

        return $next($request);
    }

    /**
     * @param \App\Models\User $user
     * @return void
     */
    static function checkDashboardCurrentLogin(User $user)
    {
        $currentLogin = $user->currentLogin;

        if ($currentLogin instanceof Organization) {
            // When a currentLogin is already set to an organization, we confirm that it is still valid before keep using it
            $isStillValidLogin = $user->logins()->where(['loginable_type' => $currentLogin->getMorphClass(), 'loginable_id' => $currentLogin->id])->exists();

            if ($isStillValidLogin === false) {
                $user->currentLogin()->associate(null)->save();
            }
        } else {
            // Otherwise, automatically apply the latest login model possible
            $availableLogins = $user->logins()->where('loginable_type', 'organization')->orderByDesc('last_login_at')->get();

            $login = $availableLogins->first();

            if (is_null($login)) {
                // No logins are available to be used, we clear any existing currentLogin
                $user->currentLogin()->associate(null)->save();
            } else {
                // We apply the login to the user
                $login->update(['last_login_at' => now()]);
                $user->currentLogin()->associate($login->loginable)->save();
            }
        }
    }

    /**
     * @param \App\Models\User $user
     * @param $organizationId
     * @return void
     */
    static function checkCurrentLogin(User $user, $organizationId)
    {
        $currentLogin = $user->currentLogin;

        $organizationId = intval($organizationId);

        if ($organizationId === 0 || is_null(Organization::find($organizationId))) {
            abort(400, 'Invalid Organization selection');
        }

        // Collect all the potential logins for this user + organization
        $availableLogins = $user->logins()->whereHasMorph('loginable', 'donor', function ($query) use ($organizationId) {
            $query->where('organization_id', $organizationId);
        })->orderByDesc('last_login_at')->get();

        if ($currentLogin) {
            // Check if the current login is still valid and  If so, skip the rest of the logic
            foreach ($availableLogins as $availableLogin) {
                if ($availableLogin->loginable->is($currentLogin)) {
                    return ;
                }
            }
        }

        // At this point, there is no currentLogin OR the currentLogin is now invalid and needs to be changed
        if (count($availableLogins)) {
            // Just fallback on the latest login
            $login = $availableLogins->first();

            $login->update(['last_login_at' => now()]);
            $user->currentLogin()->associate($login->loginable)->save();
        } else {
            // Create a new login + donor completely and use it for login
            $newDonor = Donor::create([
                'first_name'      => $user->first_name,
                'last_name'       => $user->last_name,
                'email_1'         => $user->email,
                'mobile_phone'    => $user->phone,
                'organization_id' => $organizationId
            ]);

            $login = new Login();
            $login->loginable()->associate($newDonor);
            $user->logins()->save($login);
            $user->currentLogin()->associate($newDonor)->save();
        }
    }
}
