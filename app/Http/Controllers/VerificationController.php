<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetCurrentLogin;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserSimpleResource;
use App\Models\User;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function sendVerificationCode(Request $request)
    {
        if (app()->environment('local')) return;

        switch ($request->type) {
            case 'email':
                $user = User::where('email', $request->email)->firstOrFail();

                $user->sendEmailAuthCode();
                break;

            case 'phone':
                $user = User::where('email', $request->email)->firstOrFail();

                $user->sendPhoneAuthCode();
                break;
            default:
                abort(500, 'Error');
        }

        return null;
    }

    public function validateEmail(Request $request)
    {

        $user = User::where('email', $request->all()['email'])->first();

        abort_unless($user && $user->email, 404, "User does not exist");

        $pmCount = count($user->cards) + count($user->banks);

        return [
            'id'                 => $user->id,
            'needs_verification' => !!$pmCount,
            'phone'              => $user->phone,
            'email'              => $user->email,
            'first_name'         => $user->first_name,
            'last_name'          => $user->last_name
        ];
    }

    public function validatePhone(Request $request)
    {
        $request->validate([
            'phone' => 'required',
        ]);

        $users = User::where('phone', $request->all()['phone'])->get();

        abort_unless(count($users), 404, "Not a match");

        return UserSimpleResource::collection($users);
    }

    public function signInWithCode(Request $request)
    {

        if (app()->environment('local')) {
            $user = User::where('email', $request->email)->firstOrFail();
        } else {
            $user = User::where('email', $request->email)->where('verification_code', $request->verification_code)->firstOrFail();
        }

        $user->verification_code = null;
        $user->verification_code_set_at = null;
        $user->save();

        if ($request->headers->get('app') === 'dashboard') {
            SetCurrentLogin::checkDashboardCurrentLogin($user);
        } else {
            SetCurrentLogin::checkCurrentLogin($user, $request->headers->get('organization'));
        }

        return (new UserResource($user))->additional(['meta' => [
            'token' => $user->createToken($request->device_name)->plainTextToken,
        ]]);
    }
}
