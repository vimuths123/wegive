<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated');
        abort_unless($user->type === 'admin', 403, 'Unauthorized');

        return $next($request);
    }
}
