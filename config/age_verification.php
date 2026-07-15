<?php

declare(strict_types=1);

return [

    'provider' => env('AGE_VERIFICATION_PROVIDER', 'local'),

    'min_age' => (int) env('AGE_VERIFICATION_MIN_AGE', 18),

    'session_ttl_minutes' => 15,

    'redeem_ttl_minutes' => 30,

    'document_retention_hours' => 24,

    'verify_url_base' => rtrim(env('APP_URL', 'http://localhost'), '/').'/verify',

    'veriff' => [
        'api_key' => env('VERIFF_API_KEY'),
        'api_secret' => env('VERIFF_API_SECRET'),
        'base_url' => env('VERIFF_BASE_URL', 'https://stationapi.veriff.com'),
    ],

];
