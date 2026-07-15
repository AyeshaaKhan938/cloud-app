<?php

namespace Database\Factories;

use App\Enums\SpecificationSellingType;
use App\Models\Specification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Specification>
 */
class SpecificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'specification_type' => fake()->randomElement(SpecificationSellingType::cases()),
            'value' => fake()->optional(0.3)->numerify('###'),
            'remarks' => fake()->optional(0.4)->sentence(),
        ];
    }
}
