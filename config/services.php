<?php

return [

    'contact_email' => env('CONTACT_EMAIL'),

    'company' => [
        'name' => env('COMPANY_NAME', 'LJS Realty and Brokerage'),
        'phone' => env('COMPANY_PHONE', '+63 921 345 6789'),
        'email' => env('COMPANY_EMAIL', 'ljsrealty@gmail.com'),
        'address' => env('COMPANY_ADDRESS', 'Unit 6 Labangon Town Center, Katipunan St, Cebu City, Philippines, 6000'),
        'city' => env('COMPANY_CITY', 'Cebu City, Philippines'),
        'facebook_url' => env('COMPANY_FACEBOOK_URL', 'https://www.facebook.com/LJSRealtyAndBrokerage'),
        'messenger_url' => env('COMPANY_MESSENGER_URL', ''),
        'instagram_url' => env('COMPANY_INSTAGRAM_URL', ''),
    ],

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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
