<?php

declare(strict_types=1);

namespace App\Services\AgeVerification;

use App\Contracts\AgeVerification\AgeVerificationProvider;
use App\Enums\AgeVerificationSessionStatus;
use App\Models\AgeVerificationSession;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class VeriffAgeVerificationProvider implements AgeVerificationProvider
{
    public function submitDocument(AgeVerificationSession $session, string $documentPath): void
    {
        $apiKey = (string) config('age_verification.veriff.api_key');
        $apiSecret = (string) config('age_verification.veriff.api_secret');

        if ($apiKey === '' || $apiSecret === '') {
            throw new RuntimeException('Veriff API credentials are not configured.');
        }

        $documentType = $session->document_type?->veriffDocumentType() ?? 'ID_CARD';
        $createPayload = [
            'verification' => [
                'callback' => rtrim((string) config('app.url'), '/').'/api/v1/age-verification/webhooks/veriff',
                'vendorData' => $session->session_id,
                'document' => [
                    'type' => $documentType,
                ],
            ],
        ];

        $createResponse = $this->client($apiKey)
            ->post('/v1/sessions', $createPayload)
            ->throw()
            ->json();

        $veriffSessionId = data_get($createResponse, 'verification.id');

        if (! is_string($veriffSessionId) || $veriffSessionId === '') {
            throw new RuntimeException('Veriff session creation did not return a session id.');
        }

        $binary = Storage::disk('local')->get($documentPath);
        $mimeType = Storage::disk('local')->mimeType($documentPath) ?: 'image/jpeg';
        $base64 = base64_encode((string) $binary);

        $mediaPayload = [
            'image' => [
                'context' => 'document-front',
                'content' => 'data:'.$mimeType.';base64,'.$base64,
            ],
        ];

        $mediaBody = json_encode($mediaPayload, JSON_THROW_ON_ERROR);

        $this->client($apiKey)
            ->withHeaders([
                'X-HMAC-SIGNATURE' => $this->signature($mediaBody, $apiSecret),
            ])
            ->withBody($mediaBody, 'application/json')
            ->post('/v1/sessions/'.$veriffSessionId.'/media')
            ->throw();

        $submitPayload = json_encode(['status' => 'submitted'], JSON_THROW_ON_ERROR);

        $this->client($apiKey)
            ->withHeaders([
                'X-HMAC-SIGNATURE' => $this->signature($submitPayload, $apiSecret),
            ])
            ->withBody($submitPayload, 'application/json')
            ->patch('/v1/sessions/'.$veriffSessionId)
            ->throw();

        $session->update([
            'status' => AgeVerificationSessionStatus::Processing,
            'provider_ref' => $veriffSessionId,
            'message' => 'Document submitted to Veriff for verification.',
        ]);
    }

    public function pollDecision(AgeVerificationSession $session): AgeVerificationSession
    {
        $apiKey = (string) config('age_verification.veriff.api_key');
        $apiSecret = (string) config('age_verification.veriff.api_secret');
        $providerRef = (string) $session->provider_ref;

        if ($providerRef === '') {
            throw new RuntimeException('Veriff provider reference is missing.');
        }

        $response = $this->client($apiKey)
            ->withHeaders([
                'X-HMAC-SIGNATURE' => $this->signature($providerRef, $apiSecret),
            ])
            ->get('/v1/sessions/'.$providerRef.'/decision')
            ->json();

        $status = (string) data_get($response, 'verification.status', '');
        $decision = (string) data_get($response, 'verification.decision', '');

        if ($status !== 'success' && $decision === '') {
            return $session->fresh();
        }

        if ($decision === 'approved') {
            $dateOfBirth = data_get($response, 'verification.person.dateOfBirth');
            $minAge = (int) config('age_verification.min_age', 18);
            $isOldEnough = $this->isAtLeastMinimumAge($dateOfBirth, $minAge);

            $session->update([
                'status' => $isOldEnough
                    ? AgeVerificationSessionStatus::Verified
                    : AgeVerificationSessionStatus::Rejected,
                'age_verified' => $isOldEnough,
                'verified_at' => now(),
                'message' => $isOldEnough
                    ? 'Age verified successfully.'
                    : 'Customer does not meet the minimum age requirement.',
            ]);

            return $session->fresh();
        }

        if (in_array($decision, ['declined', 'expired', 'abandoned'], true)) {
            $session->update([
                'status' => AgeVerificationSessionStatus::Rejected,
                'age_verified' => false,
                'verified_at' => now(),
                'message' => 'Age verification was rejected.',
            ]);
        }

        return $session->fresh();
    }

    private function client(string $apiKey): PendingRequest
    {
        return Http::baseUrl((string) config('age_verification.veriff.base_url'))
            ->timeout(30)
            ->connectTimeout(10)
            ->retry(2, 500)
            ->withHeaders([
                'X-AUTH-CLIENT' => $apiKey,
                'Content-Type' => 'application/json',
            ]);
    }

    private function signature(string $payload, string $secret): string
    {
        return strtolower(hash_hmac('sha256', $payload, $secret));
    }

    private function isAtLeastMinimumAge(mixed $dateOfBirth, int $minAge): bool
    {
        if (! is_string($dateOfBirth) || $dateOfBirth === '') {
            Log::warning('Veriff decision missing date of birth; rejecting age verification.');

            return false;
        }

        try {
            $birthDate = Carbon::parse($dateOfBirth);
        } catch (\Throwable) {
            Log::warning('Veriff decision had invalid date of birth.', ['date_of_birth' => $dateOfBirth]);

            return false;
        }

        return $birthDate->age >= $minAge;
    }
}
