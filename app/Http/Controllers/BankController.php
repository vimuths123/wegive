<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Processors\Tilled;
use Illuminate\Http\Request;
use App\Models\ScheduledDonation;
use App\Http\Resources\BankResource;
use Illuminate\Support\Facades\Http;

class BankController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return BankResource::collection($user->banks);
    }

    public function store(Request $request)
    {

        $requestData = $request->all();

        $vgsCardData = [
            "data" => [
                [
                    "value" => $requestData['bankAccountNumber'],
                    "classifiers" => [
                        "ach", "number"
                    ],
                    "format" => "UUID",
                    "storage" => "PERSISTENT"
                ],
                [
                    "value" => $requestData['bankRoutingNumber'],
                    "classifiers" => [
                        "ach", "routing"
                    ],
                    "format" => "UUID",
                    "storage" => "PERSISTENT"
                ]
            ]
        ];

        $vgsRequest =  Http::withBasicAuth(config('services.vgs.username'), config('services.vgs.password'))->post(config('services.vgs.endpoint') . '/aliases', $vgsCardData);


        if ($vgsRequest->failed())  return response()->json($vgsRequest->json(), 400);


        $vgsTokenData = $vgsRequest->json();

        $accountToken = $vgsTokenData['data'][0]['aliases'][0]['alias'];
        $routingToken = $vgsTokenData['data'][1]['aliases'][0]['alias'];

        $tilled = new Tilled();

        $tilledRequestBody = [
            "metadata" => [],
            "type" => "ach_debit",
            "nick_name" => "string",
            "billing_details" => [
                "address" => [
                    "street" => "test",
                    "street2" => "test",
                    "city" => "test",
                    "state" =>  "CA",
                    "zip" =>  $requestData['bankZip'],
                    "country" => "US"
                ],
                "email" => $request->user('sanctum')->email ?? $requestData['bankholderEmail'],
                "name" => $requestData['bankholderName'],
                "phone" => "test"
            ],
            "ach_debit" => [
                "account_type" => "checking",
                "account_number" => $accountToken,
                "routing_number" => $routingToken,
                "account_holder_name" => $requestData['bankholderName']
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

        $bank = new Bank();
        $bank->last_four = $tilledTokenData['ach_debit']['last2'];
        $bank->tl_token = $tilledTokenData['id'];
        $bank->vgs_routing_number_token = $routingToken;
        $bank->vgs_account_number_token = $accountToken;
        $bank->name = $requestData['bankholderName'];
        $bank->save();

        $tilled = new Tilled();

        $user = $request->user('sanctum');

        if (!$user) {
            return $bank;
        }

        if (empty($user->tl_token)) {
            $customer = $tilled->createCustomer([
                'email' => $user->email ?? null,
                'first_name' => $user->first_name ?? null,
                'last_name' => $user->last_name ?? null,
                // 'metadata' => [], // not currently used
                'phone' => $user->phone ?? null,
            ]);

            if ($customer->failed()) {
                return response()->json($customer->json(), 400);
            }

            $user->tl_token = $customer['id'];
            $user->save();
        }

        $response = $tilled->attachPaymentMethodToCustomer($bank->tl_token, $user->tl_token);


        if ($response->failed()) {
            return response()->json($response->json(), 400);
        }
        $user->banks()->save($bank);

        $user->preferredPayment()->associate($bank);
        $user->save();
        return $bank;
    }

    public function show(Bank $bank)
    {
        return $bank;
    }

    public function destroy(Request $request, Bank $bank)
    {
        $scheduledDonations = ScheduledDonation::where([['payment_method_type', 'bank'], ['payment_method_id', $bank->id]])->get();

        abort_if($scheduledDonations->first(), 400, 'Unable to delete bank as it is in use by a recurring donation');

        $user = $request->user('sanctum');

        abort_unless($bank->owner()->is($user), 400, 'You are not the owner of this bank');

        $tilled = new Tilled();
        $response = $tilled->detachPaymentMethodToCustomer($bank->tl_token, $user->tl_token);

        if ($response->successful()) {


            if ($user->preferredPayment()->is($bank)) {
                $user->preferredPayment()->associate(null);
            }

            $bank->delete();

            return $user->accounts;
        }

        abort(400, $response->json()['message'] ?? 'Unable to detach payment method');
    }
}
