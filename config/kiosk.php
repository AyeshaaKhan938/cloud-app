<?php

declare(strict_types=1);

return [

    'latest_version' => env('KIOSK_LATEST_VERSION', '1.0.0'),

    'latest_build' => (int) env('KIOSK_LATEST_BUILD', 1),

    'apk_url' => env('KIOSK_APK_URL', ''),

    'mandatory' => filter_var(env('KIOSK_MANDATORY', false), FILTER_VALIDATE_BOOL),

    'release_notes' => env('KIOSK_RELEASE_NOTES', ''),

];
