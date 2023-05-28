<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Hash;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;

class PasswordResetController extends Controller
{
    /**
     * Create token password reset
     *
     * @param  [string] email
     * @return [string] message
     */
    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'We cant find a user with that e-mail address.'
            ], 200);
        }

        $new_token = Str::random(60);

        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => $new_token
             ]
        );

        if ($user && $passwordReset) {
            $user->notify(
                new PasswordResetRequest($new_token, $request->redirect_url, $user->email)
            );
        }
        return response()->json([
            'success' => true,
            'message' => 'We have e-mailed your password reset link!'
        ]);
    }

    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function find($token)
    {
        $passwordReset = PasswordReset::where('token', $token)
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'success' => false,
                'message' => 'This password reset token is invalid.'
            ], 200);
        }

        if (Carbon::parse($passwordReset->created_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'success' => false,
                'message' => 'This password reset token is invalid.'
            ], 200);
        }

        return response()->json($passwordReset);
    }

    /**
    * Reset password
    *
    * @param  [string] email
    * @param  [string] password
    * @param  [string] password_confirmation
    * @param  [string] token
    * @return [string] message
    * @return [json] user object
    */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|confirmed',
            'token' => 'required|string'
        ]);



        $passwordReset = PasswordReset::where([
            ['token', $request->token],
            ['email', $request->email]
        ])->first();

        if (!$passwordReset) {
            return response()->json([
                'success' => false,
                'message' => 'This password reset token is invalid.'
            ], 404);
        }

        $user = User::where('email', $passwordReset->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'We cant find a user with that e-mail address.'
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        $passwordReset->delete();

        $user->notify(new PasswordResetSuccess($passwordReset));

        return response()->json(['success' => true,
                'message' => 'Your password has been successfully changed.'
            ]);
    }
}
