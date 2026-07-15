<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RechargeRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RechargeRecord>
 */
final class RechargeRecordFactory extends Factory
{
    protected $model = RechargeRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orderedAt = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'user_id' => User::factory(),
            'user_account' => fake()->safeEmail(),
            'machine_number' => fake()->numerify('##############'),
            'amount' => fake()->randomElement([0.01, 0.23, 1.00, 5.50]),
            'detail' => fake()->randomElement([
                'Receiving email...',
                'Age recognition',
                'Machine service fee',
            ]),
            'service_type' => fake()->randomElement([
                'mail serve',
                'Age ID Screen',
            ]),
            'ordered_at' => $orderedAt,
            'paid_at' => $orderedAt,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (RechargeRecord $record): void {
            $record->loadMissing('user');
            if ($record->user !== null) {
                $record->user_account = (string) $record->user->email;
                $record->saveQuietly();
            }
        });
    }
}
