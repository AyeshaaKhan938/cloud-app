<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class VerifyOperatorAlertEmailAssetsCommand extends Command
{
    protected $signature = 'operator-alerts:verify-email-assets';

    protected $description = 'Verify logo and email template exist for operator alert emails';

    public function handle(): int
    {
        $logoPaths = [
            resource_path('images/vmfs-logo.jpg'),
            resource_path('images/vmfs-logo.png'),
            public_path('images/vmfs-logo.jpg'),
        ];

        $found = false;
        foreach ($logoPaths as $path) {
            if (file_exists($path)) {
                $this->info("Logo OK: {$path}");
                $found = true;
                break;
            }
        }

        if (! $found) {
            $this->error('Logo MISSING. Upload resources/images/vmfs-logo.jpg to the server.');
        }

        $template = resource_path('views/mail/operator-alerts-summary.blade.php');
        if (! file_exists($template)) {
            $this->error("Template MISSING: {$template}");

            return self::FAILURE;
        }

        $contents = (string) file_get_contents($template);
        if (str_contains($contents, '<x-mail::message>')) {
            $this->error('Template is OLD (Laravel markdown). Deploy the custom HTML version.');
        } elseif (str_contains($contents, 'VMFS USA')) {
            $this->info('Template OK: custom HTML with VMFS USA footer.');
        } else {
            $this->warn('Template exists but may be outdated.');
        }

        return $found ? self::SUCCESS : self::FAILURE;
    }
}
