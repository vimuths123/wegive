<?php

namespace App\Processors;

use Illuminate\Support\Facades\Http;

class Payfac
{
    private $base = 'https://console.payfactory.cc/api/';
    private $token;

    public function __construct($token = null)
    {
        if ($token === null) {
            $this->token = config('services.payfac.api');
        }

        if (in_array(config('app.env'), ['local', 'dev', 'testing', 'sandbox', 'staging'])) {
            $this->base = 'https://console.payfactory-dev.cc/api/payment/';
        }
    }

    public function get($url)
    {
        return Http::withToken($this->token, 'Basic')->get($this->base . $url);
    }

    public function post($url, $parameters = [])
    {
        return Http::withToken($this->token, 'Basic')->post($this->base . $url, $parameters);
    }

    public function chargeCard($token, $amount, $merchantId = 'givelistId')
    {
        $auth = $this->auth($token, $amount, $merchantId);
        $charge = $this->capture($auth['ID'], $amount);

        return $charge;
    }

    public function chargeBank($token, $amount, $merchantId = 'givelistId')
    {
        $charge = $this->ach($token, $amount, $merchantId);

        return $charge;
    }

    public function ach($token, $amount, $merchantId)
    {
        $response = $this->post('ach', [
            'merchant_id' => $merchantId,
            'data' => [
                'accountToken' => $token,
                'transactionAmount' => $amount,
                'currencyCode' => 'USD',
            ],
        ]);

        return $response['data'];
    }

    public function auth($token, $amount, $merchantId)
    {
        $response = $this->post('auth', [
            'merchant_id' => $merchantId,
            'data' => [
                'cardToken' => $token,
                'transactionAmount' => $amount,
                'currencyCode' => 'USD',
            ],
        ]);

        return $response['data'];
    }

    public function capture($transactionId, $amount)
    {
        $response = $this->post('capture', [
            'id' => $transactionId,
            'data' => [
                'transactionAmount' => $amount,
            ],
        ]);


        return $response['data'];
    }
}
