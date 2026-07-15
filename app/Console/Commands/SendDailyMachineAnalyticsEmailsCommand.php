<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Analytics\DailyMachineAnalyticsEmailService;
use Illuminate\Console\Command;

final class SendDailyMachineAnalyticsEmailsCommand extends Command
{
    protected $signature = 'analytics:daily-email
                            {--force : Send regardless of local hour and deduplication cache}
                            {--email= : Only process the account owner matching this email address}';

    protected $description = 'Send daily machine sales and profit reports to account owners';

    public function handle(DailyMachineAnalyticsEmailService $emailService): int
    {
        if (! config('daily_analytics.enabled', true)) {
            $this->warn('Daily analytics emails are disabled (DAILY_ANALYTICS_EMAIL_ENABLED=false).');

            return self::SUCCESS;
        }

        $result = $emailService->processScheduled(
            force: (bool) $this->option('force'),
            emailFilter: $this->option('email'),
        );

        $this->info("Sent {$result['sent']} email(s).");
        $this->comment("Skipped {$result['skipped']} account(s).");

        if ($result['failed'] > 0) {
            $this->error("Failed to send {$result['failed']} email(s). Check storage/logs/laravel.log and MAIL_* in .env.");
        }

        return self::SUCCESS;
    }
}
