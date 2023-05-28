<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Donor;
use App\Models\Invite;
use App\Models\Login;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Twilio\Rest\Client;

/**
 * @group Authentication
 *
 * Authentication methods
 */
class AuthController extends Controller
{
    public const SOCIAL_PROVIDER_REGEX = 'facebook|google|twitter|apple';

    public function __construct()
    {
        $this->middleware('guest')->except('revokeToken');
        $this->middleware('auth:sanctum')->only('revokeToken');
    }

    /**
     * Issue a token (login)
     *
     * This endpoint lets you login by issuing a token.
     *
     * @unauthenticated
     * @bodyParam email string required The email address of the user. Example: john@example.com
     * @bodyParam password string required The password of the user. Example: hunter2
     * @bodyParam device_name string required The name of the device you are logging in with. Example: Johns iPhone
     * @response scenario=success status=200 { "token" : "7|5Ov08EjzGcmBPCoVDjCIKwxxNTW59zHaAL9XKqmA" }
     * @response scenario=failure status=422 {
     * "message": "The given data was invalid.",
     * "errors": {
     * "email": ["The email field is required."],
     * "password": ["The password field is required."],
     * "device_name": ["The device name field is required."]
     * }
     * }
     */
    public function issueToken(Request $request)
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->status == 0) {
            $user->accessTokens()->delete();

            abort(400, 'Your Account is suspended, please contact Admin.');
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return (new UserResource($user))->additional(['meta' => [
            'token' => $user->createToken($request->device_name)->plainTextToken,
        ]]);
    }

    /**
     * Revoke a token (logout)
     *
     * This endpoint lets you logout by revoking the token you are authenticated with.
     *
     * @response 204
     */
    public function revokeToken(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response(null, 204);
    }

    /**
     * Create Account
     *
     * This endpoint lets you register a new account.
     *
     * @unauthenticated
     */
    public function createUser(CreateUserRequest $request)
    {
        // Create the User model
        $input = $request->only(['first_name', 'last_name', 'email', 'phone']);
        if (isset($input['phone'])) {
            $client = new Client(config('services.twilio.account'), config('services.twilio.token'));
            try {
                $client->lookups->v1->phoneNumbers($input['phone'])->fetch(["countryCode" => "US"]);
            }
            catch (\Exception $e) {
                return response()->json([
                    'message' => "{$input['phone']} is an invalid phone number"
                ], 500);
            }
            $input['phone'] = preg_replace('/[^0-9]/', '', $input['phone']);
        }
        $input['password'] = Hash::make($request->password);

        $user = User::create($input);

        $organizationId = intval($request->headers->get('organization'));

        // If an organizationId is passed (not required), we create a donor profile for this user+organization
        if ($organizationId && !is_null(Organization::find($organizationId))) {
            $newDonor = Donor::create([
                'first_name'      => $input['first_name'],
                'last_name'       => $input['last_name'],
                'email_1'         => $input['email'],
                'mobile_phone'    => $input['phone'],
                'organization_id' => $organizationId
            ]);

            $login = new Login();
            $login->loginable()->associate($newDonor);
            $user->logins()->save($login);
            $user->currentLogin()->associate($newDonor)->save();
        }

        // Clear used invite
        $invite = Invite::where('token', $request->invite_token)->first();

        if ($invite) {
            $invite->accept($user);
            $invite->delete();
        }

        return (new UserResource($user))->additional(['meta' => [
            'token' => $user->createToken($request->device_name)->plainTextToken,
        ]]);
    }

    public function socialRedirect(Request $request, $provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }
}
