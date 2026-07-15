<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KioskLogFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Receives log-file uploads from kiosks.
 *
 * Endpoint: POST /api/v1/machines/{machineNo}/logs
 *
 * Authentication is gated by the EnsureLotteryManagementToken middleware
 * applied at the route level (same Bearer-token scheme the rest of the
 * /admin/ endpoints use). The kiosk app sends:
 *
 *   Authorization: Bearer <MANAGEMENT_TOKEN>
 *   Content-Type: multipart/form-data
 *   - log : the .log file
 *
 * Files land at storage/app/kiosk-logs/{machineNo}/{Y-m-d_H-i-s}-{original}
 * (the timestamp prefix lets multiple uploads of the same filename
 * coexist without overwriting).
 *
 * Hard cap of 10 MB per upload to keep a runaway kiosk from filling
 * the host disk.
 */
final class KioskLogController extends Controller
{
    private const MAX_BYTES = 10 * 1024 * 1024; // 10 MB

    public function store(Request $request, string $machineNo): JsonResponse
    {
        $request->validate([
            'log' => ['required', 'file', 'max:'.(self::MAX_BYTES / 1024)],
        ]);

        $file = $request->file('log');

        $originalName = $file->getClientOriginalName();
        $cleanName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName) ?: 'kiosk.log';

        $dir = 'kiosk-logs/'.preg_replace('/[^A-Za-z0-9_-]/', '_', $machineNo);
        $storedName = now()->format('Y-m-d_H-i-s').'-'.$cleanName;
        $storedPath = $file->storeAs($dir, $storedName);

        if ($storedPath === false) {
            return response()->json([
                'ok' => false,
                'error' => 'Failed to persist file to storage.',
            ], 500);
        }

        $sha256 = hash_file('sha256', Storage::path($storedPath)) ?: null;

        $row = KioskLogFile::query()->create([
            'machine_number' => $machineNo,
            'original_filename' => $originalName,
            'stored_path' => $storedPath,
            'size_bytes' => $file->getSize() ?: 0,
            'app_version' => $request->header('X-App-Version'),
            'sha256' => $sha256,
        ]);

        return response()->json([
            'ok' => true,
            'id' => $row->id,
            'saved_path' => $storedPath,
            'size_bytes' => $row->size_bytes,
        ], 201);
    }
}
