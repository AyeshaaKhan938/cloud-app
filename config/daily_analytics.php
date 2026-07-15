<?php

declare(strict_types=1);

return [

    'enabled' => (bool) env('DAILY_ANALYTICS_EMAIL_ENABLED', true),

    'send_hour' => (int) env('DAILY_ANALYTICS_SEND_HOUR', 7),

];
