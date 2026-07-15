<?php

declare(strict_types=1);

namespace App\Services\AgeVerification;

use App\Contracts\AgeVerification\AgeVerificationProvider;
use App\Enums\AgeVerificationSessionStatus;
use App\Models\AgeVerificationSession;
use Illuminate\Support\Facades\Storage;

final class LocalAgeVerificationProvider implements AgeVerificationProvider
{
    public function submitDocument(AgeVerificationSession $session, string $documentPath): void
    {
        $session->update([
            'status' => AgeVerificationSessionStatus::Processing,
            'provider_ref' => 'local-'.$session->session_id,
            'message' => 'Document submitted for verification.',
        ]);
    }

    public function pollDecision(AgeVerificationSession $session): AgeVerificationSession
    {
        if (! Storage::disk('local')->exists($documentPath = (string) $session->document_path)) {
            $session->update([
                'status' => AgeVerificationSessionStatus::Rejected,
                'age_verified' => false,
                'message' => 'Document not found.',
            ]);

            return $session->fresh();
        }

        $session->update([
            'status' => AgeVerificationSessionStatus::Verified,
            'age_verified' => true,
            'verified_at' => now(),
            'message' => 'Age verified successfully.',
        ]);

        return $session->fresh();
    }
}
