<?php

namespace Database\Factories;

use App\Models\SpecificationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpecificationType>
 */
class SpecificationTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
