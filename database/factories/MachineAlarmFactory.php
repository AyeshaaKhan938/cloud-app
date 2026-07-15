<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Machine;
use App\Models\MachineAlarm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MachineAlarm>
 */
final class MachineAlarmFactory extends Factory
{
    protected $model = MachineAlarm::class;

    public function definition(): array
    {
        return [
            'machine_id' => Machine::factory(),
            'title' => fake()->sentence(3),
            'message' => fake()->optional()->paragraph(),
            'severity' => fake()->randomElement(['info', 'warning', 'critical']),
            'triggered_at' => fake()->dateTimeBetween('-1 week'),
            'acknowledged_at' => fake()->optional()->dateTimeBetween('-1 day'),
        ];
    }
}
