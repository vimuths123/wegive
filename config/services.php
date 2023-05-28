<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/api/v2/auth/login/facebook/callback',
    ],

    'twitter' => [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/api/v2/auth/login/twitter/callback',
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/api/v2/auth/login/google/callback',
    ],

    'payfac' => [
        'api' => env('PAYFAC_SK'),
        'our_token' => env('PAYFAC_OUR_TOKEN'),
    ],

    'tilled' => [
        'api' => env('TILLED_SK'),
        'our_token' => env('TILLED_OUR_TOKEN'),
        'parent_token' => env('TILLED_PARENT_TOKEN'),
        'givelist_bank' => env('TILLED_GIVELIST_BANK'),
    ],
    'plaid' => [
        'client_id' => env('PLAID_CLIENT_ID'),
        'secret' => env('PLAID_SECRET')

    ],
    'twilio' => [
        'account' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'number' => env('TWILIO_FROM')

    ],
    'humanitas' => [
        'token' => env('HUMANITAS_TOKEN'),

    ],
    'zerobounce' => [
        'key' => env('ZEROBOUNCE_KEY'),

    ],
    'intercom' => [
        'token' => env('INTERCOM_TOKEN'),

    ],
    'vgs' => [
        'endpoint' => env('VGS_ENDPOINT'),
        'username' => env('VGS_USERNAME'),
        'password' => env('VGS_PASSWORD'),
        'vault' => env('VGS_VAULT'),
        'environment' => env('VGS_ENVIRONMENT'),
    ],
    'netlify' => [
        'access_token' => env('NETLIFY_ACCESS_TOKEN'),
    ]

];
