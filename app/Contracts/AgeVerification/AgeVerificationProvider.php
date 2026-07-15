<?php

declare(strict_types=1);

namespace App\Contracts\AgeVerification;

use App\Models\AgeVerificationSession;

interface AgeVerificationProvider
{
    public function submitDocument(AgeVerificationSession $session, string $documentPath): void;

    public function pollDecision(AgeVerificationSession $session): AgeVerificationSession;
}
