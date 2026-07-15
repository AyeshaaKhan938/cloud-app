<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\NotificationSetting;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds a test machine with conditions that trigger operator alerts.
 *
 * Run: php artisan db:seed --class=OperatorAlertTestDataSeeder
 */
final class OperatorAlertTestDataSeeder extends Seeder
{
    private const string MACHINE_NO = '866903255700003';

    public function run(): void
    {
        $user = User::query()->first();

        if ($user === null) {
            $this->command?->error('No users found. Run DatabaseSeeder first.');

            return;
        }

        NotificationSetting::current()->update([
            'notification_email' => NotificationSetting::current()->notification_email ?? 'test@example.com',
            'inventory_shortage_notification' => true,
            'equipment_offline_notification' => true,
            'slot_failure_notification' => true,
            'dispense_failure_notification' => true,
            'network_anomaly_notification' => true,
        ]);

        $machine = Machine::query()->updateOrCreate(
            ['machine_number' => self::MACHINE_NO],
            [
                'user_id' => $user->id,
                'machine_name' => 'Production Kiosk',
                'is_enabled' => true,
                'last_seen_at' => now()->subHour(),
            ],
        );

        MachineSlot::query()->updateOrCreate(
            [
                'machine_id' => $machine->id,
                'line_number' => 1,
            ],
            [
                'price' => '2.50',
                'max_stock' => 20,
                'current_stock' => 0,
                'stock_alarm_threshold' => 2,
                'is_active' => true,
                'is_fault' => true,
            ],
        );

        Order::query()->create([
            'machine_no' => self::MACHINE_NO,
            'product_name' => 'Test Product',
            'line_number' => 1,
            'prize_name' => 'Test Prize',
            'prize_amount' => '2.50',
            'payment_method' => 'cash',
            'status' => 'failed',
            'notes' => 'Test delivery failure - motor jam',
        ]);

        $this->command?->info('Operator alert test data seeded for machine '.self::MACHINE_NO);
    }
}
