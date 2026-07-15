<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AgeVerificationSessionStatus;
use App\Models\AgeVerificationSession;
use App\Services\AgeVerification\AgeVerificationSessionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessAgeVerificationDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 30, 60, 120];

    public function __construct(
        public readonly string $sessionId,
    ) {}

    public function handle(AgeVerificationSessionService $sessionService): void
    {
        $session = AgeVerificationSession::query()
            ->where('session_id', $this->sessionId)
            ->first();

        if ($session === null || $session->document_path === null) {
            return;
        }

        if ($session->isExpired()) {
            $session->update([
                'status' => AgeVerificationSessionStatus::Expired,
                'message' => 'Verification session expired before processing completed.',
            ]);

            return;
        }

        $provider = $sessionService->resolveProvider();

        if ($session->status === AgeVerificationSessionStatus::Uploaded) {
            $provider->submitDocument($session, $session->document_path);
            $session->refresh();
        }

        if ($session->status === AgeVerificationSessionStatus::Processing) {
            $updated = $provider->pollDecision($session);

            if ($updated->status === AgeVerificationSessionStatus::Processing) {
                self::dispatch($this->sessionId)->delay(now()->addSeconds(10));
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Age verification document processing failed.', [
            'session_id' => $this->sessionId,
            'message' => $exception?->getMessage(),
        ]);

        AgeVerificationSession::query()
            ->where('session_id', $this->sessionId)
            ->update([
                'status' => AgeVerificationSessionStatus::Rejected,
                'age_verified' => false,
                'message' => 'Verification processing failed. Please try again.',
            ]);
    }
}
