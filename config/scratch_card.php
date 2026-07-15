<?php

declare(strict_types=1);

return [
    'tenpoint' => [
        'base_url' => env('TENPOINT_API_URL', 'https://api.capture.intouchinsight.com/v2/search'),
        'token' => env('TENPOINT_API_TOKEN'),
        'timeout' => (int) env('TENPOINT_API_TIMEOUT', 8),
    ],

    'tiers' => [
        'A' => [
            'name' => env('LOTTERY_TIER_A_NAME', 'Grand Prize'),
            'slot' => (int) env('LOTTERY_TIER_A_SLOT', 1),
            'amount' => env('LOTTERY_TIER_A_AMOUNT', '10.00'),
            'weight' => (int) env('LOTTERY_TIER_A_WEIGHT', 1),
        ],
        'B' => [
            'name' => env('LOTTERY_TIER_B_NAME', 'Consolation'),
            'slot' => (int) env('LOTTERY_TIER_B_SLOT', 2),
            'amount' => env('LOTTERY_TIER_B_AMOUNT', '2.50'),
            'weight' => (int) env('LOTTERY_TIER_B_WEIGHT', 49),
        ],
    ],
];
