<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AgeVerificationSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class DeleteExpiredAgeVerificationDocumentsCommand extends Command
{
    protected $signature = 'age-verification:delete-expired-documents';

    protected $description = 'Delete age verification ID images older than the retention period';

    public function handle(): int
    {
        $retentionHours = (int) config('age_verification.document_retention_hours', 24);
        $cutoff = now()->subHours($retentionHours);

        $deleted = 0;

        AgeVerificationSession::query()
            ->whereNotNull('document_path')
            ->where(function ($query) use ($cutoff): void {
                $query->whereNotNull('verified_at')
                    ->where('verified_at', '<=', $cutoff)
                    ->orWhere(function ($query) use ($cutoff): void {
                        $query->whereNull('verified_at')
                            ->where('updated_at', '<=', $cutoff);
                    });
            })
            ->orderBy('id')
            ->chunkById(100, function ($sessions) use (&$deleted): void {
                foreach ($sessions as $session) {
                    $path = (string) $session->document_path;

                    if ($path !== '' && Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->delete($path);
                        $deleted++;
                    }

                    $session->update(['document_path' => null]);
                }
            });

        $this->info("Deleted {$deleted} age verification document(s).");

        return self::SUCCESS;
    }
}
