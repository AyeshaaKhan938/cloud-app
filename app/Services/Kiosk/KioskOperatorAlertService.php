<?php

declare(strict_types=1);

namespace App\Services\Kiosk;

use App\Models\Machine;
use App\Models\NotificationSetting;
use App\Models\Order;
use Illuminate\Support\Collection;

final class KioskOperatorAlertService
{
    /**
     * @return list<array{type: string, severity: string, title: string, message: string}>
     */
    public function alertsFor(Machine $machine): array
    {
        $settings = NotificationSetting::current();
        $slots = $machine->slots()->get();

        $alerts = [];

        if ($settings->equipment_offline_notification && ! $machine->isOnline()) {
            $alerts[] = $this->alert(
                type: 'equipment_offline',
                severity: 'critical',
                title: 'Machine offline',
                message: 'This machine has not checked in recently. Verify power and network.',
            );
        }

        if ($settings->network_anomaly_notification && ! $machine->isOnline()) {
            $alerts[] = $this->alert(
                type: 'network_anomaly',
                severity: 'warning',
                title: 'Network anomaly',
                message: 'No recent heartbeat from the kiosk. Check Wi‑Fi or Ethernet.',
            );
        }

        $lowStockCount = $slots->filter(fn ($slot) => $slot->isLowStock())->count();
        if ($settings->inventory_shortage_notification && $lowStockCount > 0) {
            $alerts[] = $this->alert(
                type: 'inventory_shortage',
                severity: 'warning',
                title: 'Low stock',
                message: "{$lowStockCount} slot(s) are at or below the alarm threshold.",
            );
        }

        $faultCount = $slots->where('is_fault', true)->count();
        if ($settings->slot_failure_notification && $faultCount > 0) {
            $alerts[] = $this->alert(
                type: 'slot_failure',
                severity: 'critical',
                title: 'Slot fault',
                message: "{$faultCount} slot(s) are marked as faulty.",
            );
        }

        if ($settings->dispense_failure_notification) {
            $recentFailures = Order::query()
                ->forMachine($machine->machine_number)
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subHours(24))
                ->orderByDesc('created_at')
                ->get();

            if ($recentFailures->isNotEmpty()) {
                $latest = $recentFailures->first();
                $count = $recentFailures->count();
                $detail = $latest->notes !== null && $latest->notes !== ''
                    ? ": {$latest->notes}"
                    : '';

                $message = $count === 1
                    ? "Product delivery failed on slot {$latest->line_number}{$detail}."
                    : "{$count} failed delivery attempt(s) in the last 24 hours. Latest: slot {$latest->line_number}{$detail}.";

                $alerts[] = $this->alert(
                    type: 'dispense_failure',
                    severity: 'critical',
                    title: 'Dispense failure',
                    message: $message,
                );
            }
        }

        return $this->deduplicateByType($alerts);
    }

    /**
     * @param  list<array{type: string, severity: string, title: string, message: string}>  $alerts
     * @return list<array{type: string, severity: string, title: string, message: string}>
     */
    private function deduplicateByType(array $alerts): array
    {
        /** @var Collection<string, array{type: string, severity: string, title: string, message: string}> $byType */
        $byType = collect($alerts)->keyBy('type');

        return $byType->values()->all();
    }

    /**
     * @return array{type: string, severity: string, title: string, message: string}
     */
    private function alert(string $type, string $severity, string $title, string $message): array
    {
        return [
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
        ];
    }
}
