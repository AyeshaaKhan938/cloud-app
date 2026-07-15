<?php

declare(strict_types=1);

namespace App\Services\Lottery;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Wrapper for the Ten Point Media / Intouch Insight code lookup API.
 *
 *   GET https://api.capture.intouchinsight.com/v2/search?UNIQUEID={code}
 *   Authorization: Bearer {token}
 *
 * Real-world response contract (verified empirically — the PDF docs are
 * misleading on this):
 *   200 + {"data": []}        → code NOT found  (treat as invalid)
 *   200 + {"data": [ ... ]}   → code FOUND      (treat as valid)
 *   200 + {"UNIQUEID": "..."} → also valid      (the shape shown in the docs)
 *   404                       → invalid         (kept for safety even though
 *                                                production seems to always 200)
 *   401                       → token expired   → fail-closed
 *   400 / 5xx / network error → fail-closed
 */
final class TenpointMediaClient
{
    /**
     * @return bool true if the code exists, false if not.
     *
     * @throws RuntimeException on auth failure, network error, or unexpected response shape.
     */
    public function validate(string $code): bool
    {
        $config = config('scratch_card.tenpoint');

        if (blank($config['token'] ?? null)) {
            throw new RuntimeException('TENPOINT_API_TOKEN is not configured.');
        }

        try {
            $response = Http::withToken($config['token'])
                ->acceptJson()
                ->timeout($config['timeout'])
                ->get($config['base_url'], ['UNIQUEID' => $code]);
        } catch (ConnectionException $e) {
            Log::warning('Ten Point Media connection error', [
                'code' => $code,
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException('Validation service unreachable.', previous: $e);
        }

        // Documented "not found" — kept even though the real API seems to
        // prefer the 200+empty-array pattern.
        if ($response->status() === 404) {
            return false;
        }

        if ($response->status() === 200) {
            $body = $response->json();

            if (! is_array($body)) {
                Log::warning('Ten Point Media: 200 with non-JSON body', [
                    'code' => $code,
                    'raw' => $response->body(),
                ]);
                throw new RuntimeException('Validation service returned unexpected body.');
            }

            // Shape A — search-result wrapper: { "data": [ ... ] }
            if (array_key_exists('data', $body)) {
                $data = $body['data'];
                if (is_array($data)) {
                    return count($data) > 0;
                }
                // data present but not an array — be strict.
                Log::warning('Ten Point Media: data field is not an array', [
                    'code' => $code,
                    'body' => $body,
                ]);
                throw new RuntimeException('Validation service returned unexpected data shape.');
            }

            // Shape B — flat object: { "UNIQUEID": "..." }
            if (isset($body['UNIQUEID']) && filled($body['UNIQUEID'])) {
                return true;
            }

            // Unknown 200 shape — fail closed.
            Log::warning('Ten Point Media: unknown 200 response shape', [
                'code' => $code,
                'body' => $body,
            ]);
            throw new RuntimeException('Validation service returned unrecognized response.');
        }

        Log::warning('Ten Point Media unexpected status', [
            'code' => $code,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new RuntimeException(
            'Validation service returned status '.$response->status().'.',
        );
    }
}
