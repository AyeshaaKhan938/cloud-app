<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MachineGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MachineGroup>
 */
final class MachineGroupFactory extends Factory
{
    protected $model = MachineGroup::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'operation_and_maintenance_user_id' => fake()->optional(0.8)->passthrough(User::factory()->create()->getKey()),
        ];
    }
}
