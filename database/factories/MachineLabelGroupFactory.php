<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MachineLabelGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MachineLabelGroup>
 */
final class MachineLabelGroupFactory extends Factory
{
    protected $model = MachineLabelGroup::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' labels',
        ];
    }
}
