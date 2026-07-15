<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Kiosk\OperatorAlertEmailService;
use Illuminate\Console\Command;

final class SendOperatorAlertEmailsCommand extends Command
{
    protected $signature = 'operator-alerts:email {--force : Send even if alerts unchanged in the last hour}';

    protected $description = 'Email active operator alerts to the configured notification address';

    public function handle(OperatorAlertEmailService $emailService): int
    {
        $result = $emailService->sendDigest($this->option('force'));

        match ($result['reason']) {
            'sent' => $this->info("Sent {$result['alert_count']} alert(s) by email."),
            'no_active_alerts' => $this->comment('No active alerts — email skipped.'),
            'notification_email_not_configured' => $this->warn('Set Notification email under System → Notification Configuration.'),
            'unchanged_since_last_email' => $this->comment('Alerts unchanged since last email — skipped (use --force).'),
            'mail_send_failed' => $this->error('Failed to send email. Check storage/logs/laravel.log and MAIL_* in .env.'),
            default => $this->comment("Skipped: {$result['reason']}"),
        };

        return self::SUCCESS;
    }
}
