<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Machine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Machine>
 */
final class MachineFactory extends Factory
{
    protected $model = Machine::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'machine_number' => (string) fake()->unique()->numerify('86###########'),
            'machine_name' => fake()->company().' machine',
            'machine_group_id' => null,
            'finance_group_id' => null,
            'machine_scenario' => fake()->optional()->words(2, true),
            'service_hot_line' => fake()->optional()->phoneNumber(),
            'detailed_address' => fake()->optional()->address(),
            'is_enabled' => true,
            'remarks' => fake()->optional()->sentence(),
            'latitude' => fake()->optional()->latitude(),
            'longitude' => fake()->optional()->longitude(),
        ];
    }
}
