<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Enums\UserRole;
use App\Mail\DailyMachineAnalyticsMail;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Support\ContactEmailList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class DailyMachineAnalyticsEmailService
{
    public function __construct(
        private readonly BusinessAnalyticsService $analyticsService,
    ) {}

    /**
     * @return array{sent: int, skipped: int, failed: int}
     */
    public function processScheduled(bool $force = false, ?string $emailFilter = null): array
    {
        if (! config('daily_analytics.enabled', true)) {
            return ['sent' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->eligibleOwners($emailFilter) as $owner) {
            $result = $this->sendToOwner($owner, $force);

            match (true) {
                $result['sent'] => $sent++,
                str_starts_with($result['reason'], 'mail_send_failed') => $failed++,
                default => $skipped++,
            };
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'failed' => $failed];
    }

    /**
     * @return array{sent: bool, reason: string, email_count: int, owner_name: string, recipient: string}
     */
    public function sendTestEmail(?User $owner = null, ?string $recipientEmail = null): array
    {
        $settings = NotificationSetting::current();
        $recipient = ContactEmailList::firstValidEmail((string) ($recipientEmail ?? ''))
            ?? ContactEmailList::firstValidEmail((string) ($settings->notification_email ?? ''));

        $owner ??= $this->eligibleOwners()->first();

        if ($owner === null) {
            return [
                'sent' => false,
                'reason' => 'no_account_owners_with_machines',
                'email_count' => 0,
                'owner_name' => '',
                'recipient' => '',
            ];
        }

        if ($recipient === null) {
            $recipient = ContactEmailList::firstValidEmail((string) ($owner->contact_emails ?? ''))
                ?? ContactEmailList::firstValidEmail((string) ($owner->email ?? ''));
        }

        if ($recipient === null) {
            return [
                'sent' => false,
                'reason' => 'notification_email_not_configured',
                'email_count' => 0,
                'owner_name' => $owner->name,
                'recipient' => '',
            ];
        }

        $report = $this->buildYesterdayReport($owner);

        try {
            Mail::to($recipient)->send(new DailyMachineAnalyticsMail($owner, $report));
        } catch (\Throwable $exception) {
            Log::error('daily_analytics_test_email_failed', [
                'owner_id' => $owner->id,
                'email' => $recipient,
                'message' => $exception->getMessage(),
            ]);

            return [
                'sent' => false,
                'reason' => 'mail_send_failed',
                'email_count' => 0,
                'owner_name' => $owner->name,
                'recipient' => $recipient,
            ];
        }

        return [
            'sent' => true,
            'reason' => 'test_sent',
            'email_count' => 1,
            'owner_name' => $owner->name,
            'recipient' => $recipient,
        ];
    }

    /**
     * @return array{sent: bool, reason: string, email_count: int}
     */
    public function sendToOwner(User $owner, bool $force = false): array
    {
        if (! config('daily_analytics.enabled', true)) {
            return ['sent' => false, 'reason' => 'daily_analytics_disabled', 'email_count' => 0];
        }

        if (! NotificationSetting::current()->account_email_notification) {
            return ['sent' => false, 'reason' => 'account_email_notification_disabled', 'email_count' => 0];
        }

        $emails = $this->resolveRecipientEmails($owner);

        if ($emails === []) {
            return ['sent' => false, 'reason' => 'no_valid_recipient_email', 'email_count' => 0];
        }

        $timezone = $this->resolveTimezone($owner);
        $now = now($timezone);
        $yesterday = $now->copy()->subDay();
        $sendHour = (int) config('daily_analytics.send_hour', 7);
        $cacheKey = $this->sentCacheKey($owner->id, $yesterday->toDateString());

        if (! $force && $now->hour !== $sendHour) {
            return ['sent' => false, 'reason' => 'outside_send_window', 'email_count' => 0];
        }

        if (! $force && Cache::has($cacheKey)) {
            return ['sent' => false, 'reason' => 'already_sent_today', 'email_count' => 0];
        }

        $report = $this->buildYesterdayReport($owner, $timezone);

        try {
            Mail::to($emails)->send(new DailyMachineAnalyticsMail($owner, $report));
        } catch (\Throwable $exception) {
            Log::error('daily_analytics_email_failed', [
                'owner_id' => $owner->id,
                'emails' => $emails,
                'message' => $exception->getMessage(),
            ]);

            return ['sent' => false, 'reason' => 'mail_send_failed', 'email_count' => count($emails)];
        }

        Cache::put($cacheKey, true, $now->copy()->endOfDay()->addHour());

        return ['sent' => true, 'reason' => 'sent', 'email_count' => count($emails)];
    }

    /**
     * @return Collection<int, User>
     */
    private function eligibleOwners(?string $emailFilter = null): Collection
    {
        $query = User::query()
            ->where('is_enabled', true)
            ->whereNull('parent_user_id')
            ->whereIn('role', [
                UserRole::Customer,
                UserRole::Agency,
                UserRole::Operator,
            ])
            ->whereHas('machines')
            ->orderBy('id');

        if ($emailFilter !== null && $emailFilter !== '') {
            $needle = strtolower(trim($emailFilter));
            $query->where(function (Builder $builder) use ($needle): void {
                $builder
                    ->whereRaw('LOWER(email) = ?', [$needle])
                    ->orWhereRaw('LOWER(contact_emails) LIKE ?', ['%'.$needle.'%']);
            });
        }

        return $query->get();
    }

    /**
     * @return list<string>
     */
    private function resolveRecipientEmails(User $owner): array
    {
        $emails = [];

        foreach (ContactEmailList::parseAddresses((string) ($owner->contact_emails ?? '')) as $address) {
            if (filter_var($address, FILTER_VALIDATE_EMAIL) !== false) {
                $emails[] = strtolower($address);
            }
        }

        if ($emails === [] && filter_var((string) $owner->email, FILTER_VALIDATE_EMAIL) !== false) {
            $emails[] = strtolower((string) $owner->email);
        }

        return array_values(array_unique($emails));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildYesterdayReport(User $owner, ?string $timezone = null): array
    {
        $timezone = $timezone ?? $this->resolveTimezone($owner);
        $yesterday = now($timezone)->subDay();

        return $this->analyticsService->buildReport(
            Carbon::parse($yesterday->toDateString(), $timezone)->startOfDay(),
            Carbon::parse($yesterday->toDateString(), $timezone)->endOfDay(),
            [],
            $owner,
        );
    }

    private function resolveTimezone(User $owner): string
    {
        $timezone = trim((string) ($owner->timezone ?? ''));

        if ($timezone !== '' && in_array($timezone, timezone_identifiers_list(), true)) {
            return $timezone;
        }

        return 'UTC';
    }

    private function sentCacheKey(int $userId, string $reportDate): string
    {
        return "daily_analytics:sent:{$userId}:{$reportDate}";
    }
}
