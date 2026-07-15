<?php

declare(strict_types=1);

namespace App\Services\AgeVerification;

use App\Contracts\AgeVerification\AgeVerificationProvider;
use App\Enums\AgeVerificationDocumentType;
use App\Enums\AgeVerificationSessionStatus;
use App\Jobs\ProcessAgeVerificationDocumentJob;
use App\Models\AgeVerificationSession;
use App\Models\Machine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AgeVerificationSessionService
{
    public function createSession(string $machineNo): AgeVerificationSession
    {
        $machine = Machine::query()
            ->where('machine_number', $machineNo)
            ->first();

        if ($machine === null) {
            throw ValidationException::withMessages([
                'machine_no' => 'Machine not found.',
            ]);
        }

        if (! $machine->age_verification_enabled) {
            throw ValidationException::withMessages([
                'machine_no' => 'Age verification is not enabled for this machine.',
            ]);
        }

        $ttl = (int) config('age_verification.session_ttl_minutes', 15);

        return AgeVerificationSession::query()->create([
            'machine_no' => $machineNo,
            'status' => AgeVerificationSessionStatus::Pending,
            'expires_at' => now()->addMinutes($ttl),
        ]);
    }

    public function findOrFail(string $sessionId): AgeVerificationSession
    {
        $session = AgeVerificationSession::query()
            ->where('session_id', $sessionId)
            ->first();

        if ($session === null) {
            throw new NotFoundHttpException('Age verification session not found.');
        }

        $session->markExpiredIfNeeded();

        return $session->fresh();
    }

    public function uploadDocument(
        AgeVerificationSession $session,
        UploadedFile $document,
        AgeVerificationDocumentType $documentType,
    ): AgeVerificationSession {
        if ($session->isExpired()) {
            throw ValidationException::withMessages([
                'document' => 'Verification session has expired.',
            ]);
        }

        if (! in_array($session->status, [
            AgeVerificationSessionStatus::Pending,
            AgeVerificationSessionStatus::Uploaded,
            AgeVerificationSessionStatus::Rejected,
        ], true)) {
            throw ValidationException::withMessages([
                'document' => 'Document cannot be uploaded for the current session status.',
            ]);
        }

        if ($session->document_path !== null) {
            Storage::disk('local')->delete($session->document_path);
        }

        $storageDirectory = 'age-verification/'.$session->session_id;
        Storage::disk('local')->makeDirectory($storageDirectory);

        $path = $document->store($storageDirectory, 'local');

        $session->update([
            'document_type' => $documentType,
            'document_path' => $path,
            'status' => AgeVerificationSessionStatus::Uploaded,
            'message' => 'Document uploaded. Verification is starting.',
        ]);

        if (config('age_verification.provider') === 'local') {
            ProcessAgeVerificationDocumentJob::dispatchSync($session->session_id);
        } else {
            ProcessAgeVerificationDocumentJob::dispatch($session->session_id);
        }

        return $session->fresh();
    }

    public function verifyUrl(AgeVerificationSession $session): string
    {
        return config('age_verification.verify_url_base').'?session='.$session->session_id;
    }

    /**
     * @return array{session_id: string, status: string, age_verified: bool, message: string|null}
     */
    public function statusPayload(AgeVerificationSession $session): array
    {
        return [
            'session_id' => $session->session_id,
            'status' => $session->status->value,
            'age_verified' => $session->age_verified,
            'message' => $session->message,
        ];
    }

    public function assertRedeemable(?string $sessionId, string $machineNo, bool $required): void
    {
        if ($sessionId === null || $sessionId === '') {
            if ($required) {
                throw ValidationException::withMessages([
                    'age_verification_session_id' => 'Age verification is required for this purchase.',
                ]);
            }

            return;
        }

        $session = AgeVerificationSession::query()
            ->where('session_id', $sessionId)
            ->first();

        if ($session === null) {
            throw ValidationException::withMessages([
                'age_verification_session_id' => 'Age verification session not found.',
            ]);
        }

        $session->markExpiredIfNeeded();
        $session->refresh();

        if ($session->machine_no !== $machineNo) {
            throw ValidationException::withMessages([
                'age_verification_session_id' => 'Age verification session does not match this machine.',
            ]);
        }

        if (! $session->isVerified()) {
            throw ValidationException::withMessages([
                'age_verification_session_id' => 'Age verification session is not verified.',
            ]);
        }

        if (! $session->isRedeemable($machineNo)) {
            throw ValidationException::withMessages([
                'age_verification_session_id' => 'Age verification session has expired.',
            ]);
        }
    }

    public function resolveProvider(): AgeVerificationProvider
    {
        return match (config('age_verification.provider')) {
            'veriff' => app(VeriffAgeVerificationProvider::class),
            default => app(LocalAgeVerificationProvider::class),
        };
    }
}
