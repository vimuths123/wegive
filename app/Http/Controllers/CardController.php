<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Processors\Tilled;
use Illuminate\Http\Request;
use App\Models\ScheduledDonation;
use App\Http\Resources\CardResource;
use Illuminate\Support\Facades\Http;

class CardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return CardResource::collection($user->cards);
    }

    public function store(Request $request)
    {

        $requestData = $request->all();



        $numberToken = $requestData['cardNumber'];
        $cscToken = $requestData['cardCvv'];

        $expirationValue = $requestData['cardExpiration'];

        $expArray =  explode('/', $expirationValue);





        $tilled = new Tilled();

        $tilledRequestBody = [
            "metadata" => [],
            "type" => "card",
            "nick_name" => "string",
            "billing_details" => [
                "address" => [
                    "street" => "test",
                    "street2" => "test",
                    "city" => "test",
                    "state" =>  null,
                    "zip" =>  $requestData['cardZip'],
                    "country" => null
                ],
                "email" => $request->user('sanctum') ? $request->user('sanctum')->email : $requestData['cardholderEmail'],
                "name" => $requestData['cardholderName'],
                "phone" => "test"
            ],
            "card" => [
                "cvc" =>   $cscToken,
                "exp_month" => (float) $expArray[0],
                "exp_year" =>  (float) ('20' . $expArray[1]),
                "number" => $numberToken
            ],

        ];




        $proxyUsername = config('services.vgs.username');
        $proxyPassword = config('services.vgs.password');

        $proxyVault = config('services.vgs.vault');
        $proxyEnvironment = config('services.vgs.environment');


        $tilledTokenRequest = $tilled->post(
            'payment-methods',
            $tilledRequestBody,
            [
                'proxy' => "https://{$proxyUsername}:{$proxyPassword}@{$proxyVault}.{$proxyEnvironment}.verygoodproxy.com:8443",
                'ssl_cert' => storage_path("vgs-{$proxyEnvironment}.pem"),
                'verify' => false
            ]

        );




        if ($tilledTokenRequest->failed()) return response()->json($tilledTokenRequest->json(), 400);

        $tilledTokenData = $tilledTokenRequest->json();

        $card = new Card();
        $card->issuer = $tilledTokenData['card']['brand'];
        $card->last_four = $tilledTokenData['card']['last4'];
        $card->expiration = $tilledTokenData['card']['exp_month'] . '/' . $tilledTokenData['card']['exp_year'];
        $card->name = $requestData['cardholderName'];
        $card->tl_token = $tilledTokenData['id'];
        $card->vgs_number_token = $numberToken;
        $card->vgs_security_code_token = $cscToken;


        $card->zip_code = $requestData['cardZip'];
        $card->save();



        $tilled = new Tilled();

        $user = $request->user('sanctum');

        if (!$user) {
            return new CardResource($card);
        }


        if (empty($user->tl_token)) {
            $customer = $tilled->createCustomer([
                'email' =>  $user->email ?? null,
                'first_name' =>  $user->first_name ?? null,
                'last_name' =>  $user->last_name ?? null,
                // 'metadata' => [],
                'phone' =>  $user->phone ?? null,
            ]);

            if ($customer->failed()) {

                return response()->json($customer->json(), 400);
            }

            $user->tl_token = $customer['id'];
            $user->save();
        }

        $response = $tilled->attachPaymentMethodToCustomer($card->tl_token,  $user->tl_token);

        if ($response->failed()) {

            return response()->json($response->json(), 400);
        }

        $user->cards()->save($card);

        $user->preferredPayment()->associate($card);
        $user->save();

        return new CardResource($card);
    }

    public function update(Request $request, Card $card)
    {

        abort_unless($card->owner()->is(auth()->user()), 401, 'Unauthorized');
        $tilled = new Tilled();

        $existingTilledCard = $tilled->get("payment-methods/{$card->tl_token}");

        $billingDetails = $existingTilledCard->json()['billing_details'];


        $tilled->detachPaymentMethodToCustomer($card->tl_token, auth()->user()->currentLogin->tl_token);



        $requestData = $request->all();



        $numberToken = $card->vgs_number_token;
        $cscToken = $requestData['cardCvv'];

        $expirationValue = $requestData['cardExpiration'];

        $expArray =  explode('/', $expirationValue);






        $tilledRequestBody = [
            "metadata" => [],
            "type" => "card",
            "nick_name" => "string",
            "billing_details" => [
                "address" => [
                    "street" => "test",
                    "street2" => "test",
                    "city" => "test",
                    "state" =>  null,
                    "zip" =>  $billingDetails['address']['zip'],
                    "country" => null
                ],
                "email" => $request->user('sanctum') ? $request->user('sanctum')->email : $billingDetails['email'],
                "name" => $billingDetails['name'],
                "phone" => "test"
            ],
            "card" => [
                "cvc" =>   $cscToken,
                "exp_month" => (float) $expArray[0],
                "exp_year" =>  (float) ('20' . $expArray[1]),
                "number" => $numberToken
            ],

        ];




        $proxyUsername = config('services.vgs.username');
        $proxyPassword = config('services.vgs.password');

        $proxyVault = config('services.vgs.vault');
        $proxyEnvironment = config('services.vgs.environment');


        $tilledTokenRequest = $tilled->post(
            'payment-methods',
            $tilledRequestBody,
            [
                'proxy' => "https://{$proxyUsername}:{$proxyPassword}@{$proxyVault}.{$proxyEnvironment}.verygoodproxy.com:8443",
                'ssl_cert' => storage_path("vgs-{$proxyEnvironment}.pem"),
                'verify' => false
            ]

        );




        if ($tilledTokenRequest->failed()) return response()->json($tilledTokenRequest->json(), 400);

        $tilledTokenData = $tilledTokenRequest->json();

        $card->issuer = $tilledTokenData['card']['brand'];
        $card->last_four = $tilledTokenData['card']['last4'];
        $card->expiration = $tilledTokenData['card']['exp_month'] . '/' . $tilledTokenData['card']['exp_year'];
        $card->name = $billingDetails['name'];
        $card->tl_token = $tilledTokenData['id'];
        $card->vgs_number_token = $numberToken;
        $card->vgs_security_code_token = $cscToken;


        $card->zip_code = $billingDetails['address']['zip'];
        $card->save();



        $tilled = new Tilled();

        $user = $request->user('sanctum');

        if (!$user) {
            return new CardResource($card);
        }


        if (empty($user->tl_token)) {
            $customer = $tilled->createCustomer([
                'email' =>  $user->email ?? null,
                'first_name' =>  $user->first_name ?? null,
                'last_name' =>  $user->last_name ?? null,
                // 'metadata' => [],
                'phone' =>  $user->phone ?? null,
            ]);

            if ($customer->failed()) {

                return response()->json($customer->json(), 400);
            }

            $user->tl_token = $customer['id'];
            $user->save();
        }

        $response = $tilled->attachPaymentMethodToCustomer($card->tl_token,  $user->tl_token);

        if ($response->failed()) {

            return response()->json($response->json(), 400);
        }

        $user->cards()->save($card);

        $user->preferredPayment()->associate($card);
        $user->save();

        return new CardResource($card);
    }

    public function show(Card $card)
    {
        return new CardResource($card);
    }

    public function destroy(Request $request, Card $card)
    {
        $currentLogin = $request->user('sanctum');

        $scheduledDonations = ScheduledDonation::where([['payment_method_type', 'card'], ['payment_method_id', $card->id]])->get();

        abort_if($scheduledDonations->first(), 400, 'Unable to delete credit card as it is in use by a recurring donation');

        abort_unless($card->owner()->is($currentLogin), 400, 'You are not the owner of this credit card');


        $tilled = new Tilled();
        $response = $tilled->detachPaymentMethodToCustomer($card->tl_token, $currentLogin->tl_token);



        if ($response->successful()) {

            if ($currentLogin->preferredPayment($request->headers->get('organization'))->is($card)) {
                $currentLogin->preferredPayment($request->headers->get('organization'))->associate(null);
            }

            $card->delete();

            return $currentLogin->accounts;
        }

        abort(400, $response->json()['message'] ?? 'Unable to detach payment method');
    }
}
