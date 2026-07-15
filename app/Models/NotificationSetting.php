<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'account_email_notification',
    'inventory_shortage_notification',
    'equipment_offline_notification',
    'slot_failure_notification',
    'dispense_failure_notification',
    'network_anomaly_notification',
    'notification_email',
])]
final class NotificationSetting extends Model
{
    private static ?self $currentCache = null;

    protected function casts(): array
    {
        return [
            'account_email_notification' => 'boolean',
            'inventory_shortage_notification' => 'boolean',
            'equipment_offline_notification' => 'boolean',
            'slot_failure_notification' => 'boolean',
            'dispense_failure_notification' => 'boolean',
            'network_anomaly_notification' => 'boolean',
        ];
    }

    public static function current(): self
    {
        if (self::$currentCache instanceof self) {
            return self::$currentCache;
        }

        $row = self::query()->first();
        if ($row === null) {
            $row = self::query()->create([
                'account_email_notification' => false,
                'inventory_shortage_notification' => false,
                'equipment_offline_notification' => false,
                'slot_failure_notification' => false,
                'dispense_failure_notification' => true,
                'network_anomaly_notification' => false,
                'notification_email' => null,
            ]);
        }

        return self::$currentCache = $row;
    }

    public static function forgetCurrentCache(): void
    {
        self::$currentCache = null;
    }
}
