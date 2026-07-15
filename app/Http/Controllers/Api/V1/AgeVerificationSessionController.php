<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\AgeVerificationDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateAgeVerificationSessionRequest;
use App\Http\Requests\Api\UploadAgeVerificationDocumentRequest;
use App\Models\AgeVerificationSession;
use App\Services\AgeVerification\AgeVerificationSessionService;
use Illuminate\Http\JsonResponse;

final class AgeVerificationSessionController extends Controller
{
    public function store(
        CreateAgeVerificationSessionRequest $request,
        AgeVerificationSessionService $sessionService,
    ): JsonResponse {
        $session = $sessionService->createSession($request->validated('machine_no'));

        return response()->json([
            'session_id' => $session->session_id,
            'verify_url' => $sessionService->verifyUrl($session),
            'expires_at' => $session->expires_at->toIso8601String(),
        ], 201);
    }

    public function show(
        string $sessionId,
        AgeVerificationSessionService $sessionService,
    ): JsonResponse {
        $session = $sessionService->findOrFail($sessionId);

        return response()->json($sessionService->statusPayload($session));
    }

    public function uploadDocument(
        UploadAgeVerificationDocumentRequest $request,
        AgeVerificationSession $session,
        AgeVerificationSessionService $sessionService,
    ): JsonResponse {
        $session = $sessionService->findOrFail($session->session_id);

        $session = $sessionService->uploadDocument(
            $session,
            $request->file('document'),
            AgeVerificationDocumentType::from($request->validated('document_type')),
        );

        return response()->json([
            'status' => 'processing',
            'message' => $session->message ?? 'Document received. Verification is in progress.',
        ]);
    }
}
