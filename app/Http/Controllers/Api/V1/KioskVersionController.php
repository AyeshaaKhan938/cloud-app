<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KioskAppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KioskVersionController extends Controller
{
    /**
     * GET /api/v1/kiosk/version  and  /api/v1/kiosk/update-check
     *
     * OTA update check for the Flutter kiosk. Serves the latest ACTIVE row
     * from kiosk_app_versions (managed in the Filament admin panel) and
     * returns the flat shape the app's UpdateService.check() reads.
     */
    public function show(Request $request): JsonResponse
    {
        // The kiosk app sends `current_version_code`; accept `build_number`
        // as a fallback for any older caller.
        $currentBuild = (int) ($request->query('current_version_code')
            ?? $request->query('build_number', 0));

        $latest = KioskAppVersion::latestActive();

        if ($latest === null
            || empty($latest->apk_url)
            || $currentBuild >= $latest->version_code
        ) {
            return response()->json(['update_available' => false]);
        }

        return response()->json([
            'update_available' => true,
            'version_code' => $latest->version_code,
            'version_name' => $latest->version_name,
            'apk_url' => $latest->apk_url,
            'apk_sha256' => $latest->apk_sha256,
            'apk_size_bytes' => $latest->apk_size_bytes,
            'mandatory' => $latest->mandatory,
            'release_notes' => $latest->release_notes ?? '',
        ]);
    }
}
