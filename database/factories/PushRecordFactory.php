<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PushMethod;
use App\Models\PushRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PushRecord>
 */
final class PushRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_title' => fake()->sentence(4),
            'push_method' => fake()->randomElement(PushMethod::cases()),
            'publisher_account' => fake()->userName().'@'.fake()->domainName(),
            'pushed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
