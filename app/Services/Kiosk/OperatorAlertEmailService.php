<?php

declare(strict_types=1);

namespace App\Services\Kiosk;

use App\Mail\OperatorAlertsSummaryMail;
use App\Models\Machine;
use App\Models\NotificationSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class OperatorAlertEmailService
{
    private const string CACHE_KEY = 'operator_alerts_email_digest_hash';

    public function __construct(
        private readonly KioskOperatorAlertService $alertService,
    ) {}

    /**
     * @return array{sent: bool, reason: string, alert_count: int}
     */
    public function sendDigest(bool $force = false): array
    {
        $settings = NotificationSetting::current();
        $email = $settings->notification_email;

        if ($email === null || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'reason' => 'notification_email_not_configured', 'alert_count' => 0];
        }

        $rows = $this->collectAlertRows();

        if ($rows === []) {
            Cache::forget(self::CACHE_KEY);

            return ['sent' => false, 'reason' => 'no_active_alerts', 'alert_count' => 0];
        }

        $hash = md5((string) json_encode($rows));

        if (! $force && Cache::get(self::CACHE_KEY) === $hash) {
            return ['sent' => false, 'reason' => 'unchanged_since_last_email', 'alert_count' => count($rows)];
        }

        try {
            Mail::to($email)->send(new OperatorAlertsSummaryMail($rows));
        } catch (\Throwable $exception) {
            Log::error('operator_alert_email_failed', [
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);

            return ['sent' => false, 'reason' => 'mail_send_failed', 'alert_count' => count($rows)];
        }

        Cache::put(self::CACHE_KEY, $hash, now()->addHour());

        return ['sent' => true, 'reason' => 'sent', 'alert_count' => count($rows)];
    }

    /**
     * Sends a sample alert digest for email/SMTP verification (ignores live machine state).
     *
     * @return array{sent: bool, reason: string, alert_count: int}
     */
    public function sendTestEmail(): array
    {
        $settings = NotificationSetting::current();
        $email = $settings->notification_email;

        if ($email === null || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'reason' => 'notification_email_not_configured', 'alert_count' => 0];
        }

        $rows = $this->sampleAlertRows();

        try {
            Mail::to($email)->send(new OperatorAlertsSummaryMail($rows));
        } catch (\Throwable $exception) {
            Log::error('operator_alert_test_email_failed', [
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);

            return ['sent' => false, 'reason' => 'mail_send_failed', 'alert_count' => count($rows)];
        }

        return ['sent' => true, 'reason' => 'test_sent', 'alert_count' => count($rows)];
    }

    /**
     * Sends an immediate email when a dispense/delivery attempt fails on a kiosk.
     *
     * @return array{sent: bool, reason: string, alert_count: int}
     */
    public function sendInstantDispenseFailureAlert(Order $order): array
    {
        $settings = NotificationSetting::current();

        if (! $settings->dispense_failure_notification) {
            return ['sent' => false, 'reason' => 'dispense_failure_notification_disabled', 'alert_count' => 0];
        }

        $email = $settings->notification_email;

        if ($email === null || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'reason' => 'notification_email_not_configured', 'alert_count' => 0];
        }

        $machine = Machine::query()
            ->where('machine_number', $order->machine_no)
            ->first();

        $machineName = $machine?->machine_name ?? $order->machine_no;
        $detail = $order->notes !== null && $order->notes !== ''
            ? ": {$order->notes}"
            : '';
        $product = $order->product_name !== null && $order->product_name !== ''
            ? " ({$order->product_name})"
            : '';

        $rows = [[
            'machine_number' => $order->machine_no,
            'machine_name' => $machineName,
            'type' => 'dispense_failure',
            'severity' => 'critical',
            'title' => 'Dispense failure',
            'message' => "Product delivery failed on slot {$order->line_number}{$product}{$detail}.",
        ]];

        try {
            Mail::to($email)->send(new OperatorAlertsSummaryMail($rows));
        } catch (\Throwable $exception) {
            Log::error('operator_dispense_failure_instant_email_failed', [
                'email' => $email,
                'order_id' => $order->id,
                'machine_no' => $order->machine_no,
                'message' => $exception->getMessage(),
            ]);

            return ['sent' => false, 'reason' => 'mail_send_failed', 'alert_count' => 1];
        }

        return ['sent' => true, 'reason' => 'instant_dispense_failure_sent', 'alert_count' => 1];
    }

    /**
     * @return list<array{machine_number: string, machine_name: string, type: string, severity: string, title: string, message: string}>
     */
    public function sampleAlertRows(): array
    {
        return [
            [
                'machine_number' => '866903255700003',
                'machine_name' => 'Production Kiosk',
                'type' => 'equipment_offline',
                'severity' => 'critical',
                'title' => 'Machine offline',
                'message' => 'This is a test alert. Your machine has not checked in recently.',
            ],
            [
                'machine_number' => '866903255700003',
                'machine_name' => 'Production Kiosk',
                'type' => 'inventory_shortage',
                'severity' => 'warning',
                'title' => 'Low stock',
                'message' => 'This is a test alert. 2 slot(s) are at or below the alarm threshold.',
            ],
            [
                'machine_number' => '866903255700003',
                'machine_name' => 'Production Kiosk',
                'type' => 'dispense_failure',
                'severity' => 'critical',
                'title' => 'Dispense failure',
                'message' => 'This is a test alert. Product delivery failed on slot 1: motor jam.',
            ],
        ];
    }

    /**
     * @return list<array{machine_number: string, machine_name: string, type: string, severity: string, title: string, message: string}>
     */
    public function collectAlertRows(): array
    {
        $rows = [];

        foreach (Machine::query()->with('slots')->orderBy('machine_name')->get() as $machine) {
            foreach ($this->alertService->alertsFor($machine) as $alert) {
                $rows[] = [
                    'machine_number' => $machine->machine_number,
                    'machine_name' => $machine->machine_name ?? $machine->machine_number,
                    'type' => $alert['type'],
                    'severity' => $alert['severity'],
                    'title' => $alert['title'],
                    'message' => $alert['message'],
                ];
            }
        }

        return $rows;
    }
}
