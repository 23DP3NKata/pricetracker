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

    'coingecko' => [
        'base_url' => env('COINGECKO_BASE_URL', 'https://api.coingecko.com/api/v3'),
        'vs_currency' => env('COINGECKO_VS_CURRENCY', 'usd'),
        'api_key' => env('COINGECKO_API_KEY'),
        'timeout' => (int) env('COINGECKO_TIMEOUT', 15),
        'default_assets' => [
            ['symbol' => 'BTC', 'name' => 'Bitcoin'],
            ['symbol' => 'ETH', 'name' => 'Ethereum'],
            ['symbol' => 'SOL', 'name' => 'Solana'],
            ['symbol' => 'BNB', 'name' => 'BNB'],
            ['symbol' => 'XRP', 'name' => 'XRP'],
            ['symbol' => 'ADA', 'name' => 'Cardano'],
            ['symbol' => 'DOGE', 'name' => 'Dogecoin'],
            ['symbol' => 'TRX', 'name' => 'TRON'],
            ['symbol' => 'DOT', 'name' => 'Polkadot'],
            ['symbol' => 'LTC', 'name' => 'Litecoin'],
        ],
        'symbol_map' => [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'SOL' => 'solana',
            'BNB' => 'binancecoin',
            'XRP' => 'ripple',
            'ADA' => 'cardano',
            'DOGE' => 'dogecoin',
            'TRX' => 'tron',
            'DOT' => 'polkadot',
            'LTC' => 'litecoin',
        ],
    ],

];
