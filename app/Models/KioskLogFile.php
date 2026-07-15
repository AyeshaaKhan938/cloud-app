<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'machine_number',
    'original_filename',
    'stored_path',
    'size_bytes',
    'app_version',
    'sha256',
    'notes',
])]
final class KioskLogFile extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    /**
     * Absolute URL the operator can click in the Filament admin to
     * download the .log file. Uses the same private disk pattern the
     * rest of the kiosk upload flows use (storage/app/...), behind
     * the storage:link symlink.
     */
    public function downloadUrl(): string
    {
        return Storage::url($this->stored_path);
    }

    public function exists(): bool
    {
        return Storage::exists($this->stored_path);
    }
}
