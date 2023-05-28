<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;



class Intercom
{

    private $base = 'https://api.intercom.io/';

    private $token = null;

    public function __construct()
    {

        $this->token = config('services.intercom.token');

        // if (in_array(config('app.env'), ['local', 'dev', 'testing', 'sandbox', 'staging'])) {
        //     return true;
        // }
    }


    public function get($url)
    {
        return Http::withHeaders(['Authorization' => "Bearer {$this->token}", 'Content-Type' => 'application/json', 'Accept' => 'application/json'])->get($this->base . $url);
    }

    public function post($url, $parameters = [])
    {
        return Http::withHeaders(['Authorization' => "Bearer {$this->token}", 'Content-Type' => 'application/json', 'Accept' => 'application/json'])->post($this->base . $url, $parameters);
    }

    public function put($url, $parameters = [])
    {
        return Http::withHeaders(['Authorization' => "Bearer {$this->token}", 'Content-Type' => 'application/json', 'Accept' => 'application/json'])->put($this->base . $url, $parameters);
    }

    public function trackEvent($parameters = [])
    {

        return $this->post('events', $parameters);
    }
}
