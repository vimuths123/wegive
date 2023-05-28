<?php

namespace App\Processors;

use App\Actions\Intercom;
use App\Models\Transaction;
use App\Models\Organization;
use Illuminate\Support\Facades\Http;

class Tilled
{
    private $base = 'https://api.tilled.com/v1/';
    private $token;

    // Payment Intent Statuses: ['canceled', 'processing', 'requires_action', 'requires_capture', 'requires_confirmation', 'requires_payment_method', 'succeeded']

    public function __construct($token = null)
    {
        if ($token === null) {
            $this->token = config('services.tilled.api');
        }

        if (in_array(config('app.env'), ['local', 'dev', 'testing', 'sandbox', 'staging'])) {
            $this->base = 'https://sandbox-api.tilled.com/v1/';
        }
    }

    public function get($url)
    {
        return Http::withHeaders(['tilled-api-key' => config('services.tilled.api'), 'tilled-account' => config('services.tilled.parent_token')])->get($this->base . $url);
    }

    public function post($url, $parameters = [], $options = [])
    {
        return Http::withHeaders(['tilled-api-key' => config('services.tilled.api'), 'tilled-account' => config('services.tilled.parent_token')])->withOptions($options)->post($this->base . $url, $parameters);
    }

    public function put($url, $parameters = [])
    {
        return Http::withHeaders(['tilled-api-key' => config('services.tilled.api'), 'tilled-account' => config('services.tilled.parent_token')])->put($this->base . $url, $parameters);
    }

    public function postForSpecificMerchant($url, $parameters = [], $merchantId = null)
    {
        return Http::withHeaders(['tilled-api-key' => config('services.tilled.api'), 'tilled-account' => $merchantId])->post($this->base . $url, $parameters);
    }


    public function getForSpecificMerchant($url, $parameters = [], $merchantId = null)
    {
        return Http::withHeaders(['tilled-api-key' => config('services.tilled.api'), 'tilled-account' => $merchantId])->get($this->base . $url, $parameters);
    }

    public function handleTilledPayment($sourceToken, $amount, $destinationToken, $transactionId, $user, $fee = 0)
    {

        abort_unless($amount > 0, 400, 'Amount must be positive.');
        abort_unless($amount < 10000000, 'Amount must be reasonable.');

        if (empty($user->tl_token)) {
            $customer = $this->createCustomer([
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'metadata' => ['user_id' => (string) $user->id],
                'phone' => $user->phone,
            ]);

            if ($customer->failed()) {
                return response()->json($customer->json(), 400);
            }

            $user->tl_token = $customer['id'];
            $user->save();

            $this->attachPaymentMethodToCustomer($sourceToken, $user->tl_token);
        }


        return $this->createPaymentIntent($sourceToken, $amount, $destinationToken, $transactionId, $fee);
    }





    public function createPaymentIntent($sourceToken, $amount, $destinationToken, $transactionId, $fee)
    {
        return $this->postForSpecificMerchant('payment-intents', [
            'amount' => $amount,
            'currency' => 'usd',
            'payment_method_types' => ['card', 'ach_debit'],
            'capture_method' => 'automatic',
            'confirm' => true,
            'payment_method_id' => $sourceToken,
            'platform_fee_amount' => $fee,
            'statement_descriptor_suffix' => 'string',
            'occurrence_type' => 'consumer_ad_hoc',
            'metadata' => ['transaction_id' => implode(",", $transactionId)],
        ], $destinationToken);
    }



    public function refundPaymentIntent($merchantToken, $paymentIntentToken)
    {
        return $this->postForSpecificMerchant('refunds', [
            'payment_intent_id' => $paymentIntentToken,
            'reason' => 'requested_by_customer',
            'refund_platform_fee' => true
        ], $merchantToken);
    }

    public function createCustomer(array $data)
    {
        return $this->post('customers', $data);
    }

    public function attachPaymentMethodToCustomer($paymentMethodToken, $customerToken, $abort = true)
    {
        $response = $this->put("payment-methods/$paymentMethodToken/attach", ['customer_id' => $customerToken]);

        $details = $response->json();

        if ($response->failed()) {
            if ($abort) {
                abort(400, $details['message'] . " Please Try Again");
            } else {
                return $response;
            }
        }


        if ($details['card'] && ($details['card']['checks']['cvc_check'] === 'fail' || $details['card']['checks']['address_postal_code_check'] === 'fail')) {
            $this->detachPaymentMethodToCustomer($paymentMethodToken, $customerToken);

            abort_unless($details['card']['checks']['cvc_check'] === 'pass', 500, 'The CVC is incorrect');
            abort_unless($details['card']['checks']['address_postal_code_check'] === 'pass', 500, 'The Zip Code is incorrect');
        }

        return $response;
    }


    public function detachPaymentMethodToCustomer($paymentMethodToken, $customerToken)
    {
        return $this->put("payment-methods/$paymentMethodToken/detach", ['customer_id' => $customerToken]);
    }

    public function checkTransactionStatus()
    {
        $transactions = Transaction::where('status', '!=', Transaction::STATUS_SUCCESS)->where('status', '!=', Transaction::STATUS_FAILED)->whereNotNull('correlation_id')->get();

        foreach ($transactions as $transaction) {
            $id = $transaction->correlation_id;
            dump("payment-intents/{$id}");
            $merchantId = config('services.tilled.our_token');
            if ($transaction->destination instanceof Organization) {
                $merchantId = $transaction->destination->tl_token;
            }
            $response = $this->getForSpecificMerchant("payment-intents/{$id}", [], $merchantId);
            $details = $response->json();

            if ($response->successful()) {

                if ($details['status'] === 'succeeded') {
                    dump('Success');
                    $transaction->status = Transaction::STATUS_SUCCESS;
                } else if ($details['status'] === 'processing') {
                    dump('Processing');

                    $transaction->status = Transaction::STATUS_PROCESSING;
                } else {
                    dump('Failed');



                    $transaction->status = Transaction::STATUS_FAILED;
                }
                $transaction->save();
            }
        }
    }
}
