<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'version_code',
    'version_name',
    'apk_url',
    'apk_sha256',
    'apk_size_bytes',
    'release_notes',
    'is_active',
    'mandatory',
])]
final class KioskAppVersion extends Model
{
    protected function casts(): array
    {
        return [
            'version_code' => 'integer',
            'apk_size_bytes' => 'integer',
            'is_active' => 'boolean',
            'mandatory' => 'boolean',
        ];
    }

    /**
     * Latest active version, or null if none are flagged for rollout.
     */
    public static function latestActive(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->orderByDesc('version_code')
            ->first();
    }
}
