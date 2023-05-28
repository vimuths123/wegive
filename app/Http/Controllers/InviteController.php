<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Login;
use App\Models\Invite;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Mail;

class InviteController extends Controller
{

    public function acceptInvite($token)
    {
        $invite = Invite::where('token', $token)->firstOrFail();

        abort_unless($invite->email === auth()->user()->email, 401, 'Unauthenticated');

        $invite->accept();

        $invite->delete();

        return new UserResource(auth()->user());
    }

    public function createInvite(Request $request)
    {
        // validate the incoming request data
        do {
            //generate a random string using Laravel's str_random helper
            $token = Str::random();
        } //check if the token already exists and if it does, try again
        while (Invite::where('token', $token)->first());
        //create a new invite record

        if (User::where('email', $request->get('email'))->first() && $request->get('action') === 1) {
            abort(500, 'User is not able to be invited');
        }


        $invite = Invite::create([
            'email' => $request->get('email'),
            'token' => $token,
            'inviter_id' => $request->inviter_id ?? auth()->user()->currentLogin->id,
            'inviter_type' => $request->inviter_type ?? auth()->user()->currentLogin->getMorphClass(),
            'action' => $request->get('action') ?? Invite::INVITE_DONOR
        ]);


        $params = ['inviter' => auth()->user()->currentLogin->name, 'redirectUrl' => "{$request->get('redirect_url')}?invite_token={$invite->token}", 'logo' => null];
        $email = $request->get('email');
        Mail::send('emails.invite', $params, function ($message) use ($email) {
            $message->to($email)
                ->subject('You have been invited to use WeGive!');
        });
        return;
    }
}
