<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AdvertisementGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdvertisementGroup>
 */
final class AdvertisementGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => substr(fake()->unique()->words(4, true), 0, 50),
        ];
    }
}
