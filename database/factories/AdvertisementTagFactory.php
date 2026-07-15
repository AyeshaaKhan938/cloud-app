<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AdvertisementTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdvertisementTag>
 */
final class AdvertisementTagFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => substr(fake()->unique()->word().'-'.fake()->word(), 0, 50),
        ];
    }
}
