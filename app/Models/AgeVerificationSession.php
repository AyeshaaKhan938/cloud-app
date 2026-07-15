<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AgeVerificationDocumentType;
use App\Enums\AgeVerificationSessionStatus;
use Database\Factories\AgeVerificationSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'session_id',
    'machine_no',
    'status',
    'age_verified',
    'document_type',
    'provider_ref',
    'document_path',
    'message',
    'expires_at',
    'verified_at',
])]
final class AgeVerificationSession extends Model
{
    /** @use HasFactory<AgeVerificationSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AgeVerificationSessionStatus::class,
            'document_type' => AgeVerificationDocumentType::class,
            'age_verified' => 'boolean',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::creating(function (AgeVerificationSession $session): void {
            if ($session->session_id === null) {
                $session->session_id = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'session_id';
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast()
            || $this->status === AgeVerificationSessionStatus::Expired;
    }

    public function isVerified(): bool
    {
        return $this->status === AgeVerificationSessionStatus::Verified
            && $this->age_verified;
    }

    public function isRedeemable(string $machineNo): bool
    {
        if (! $this->isVerified()) {
            return false;
        }

        if ($this->machine_no !== $machineNo) {
            return false;
        }

        if ($this->verified_at === null) {
            return false;
        }

        $redeemTtl = (int) config('age_verification.redeem_ttl_minutes', 30);

        return $this->verified_at->gt(now()->subMinutes($redeemTtl));
    }

    public function markExpiredIfNeeded(): void
    {
        if ($this->isExpired() && $this->status !== AgeVerificationSessionStatus::Expired) {
            $this->update([
                'status' => AgeVerificationSessionStatus::Expired,
                'message' => 'Verification session expired.',
            ]);
        }
    }
}
