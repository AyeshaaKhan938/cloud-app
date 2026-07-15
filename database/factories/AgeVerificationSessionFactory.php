<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AgeVerificationSessionStatus;
use App\Models\AgeVerificationSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AgeVerificationSession>
 */
final class AgeVerificationSessionFactory extends Factory
{
    protected $model = AgeVerificationSession::class;

    public function definition(): array
    {
        return [
            'session_id' => (string) Str::uuid(),
            'machine_no' => (string) fake()->numerify('86###########'),
            'status' => AgeVerificationSessionStatus::Pending,
            'age_verified' => false,
            'document_type' => null,
            'provider_ref' => null,
            'document_path' => null,
            'message' => null,
            'expires_at' => now()->addMinutes(15),
            'verified_at' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (): array => [
            'status' => AgeVerificationSessionStatus::Verified,
            'age_verified' => true,
            'verified_at' => now(),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (): array => [
            'status' => AgeVerificationSessionStatus::Processing,
        ]);
    }
}
