<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AdvertisementType;
use App\Models\Advertisement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Advertisement>
 */
final class AdvertisementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(AdvertisementType::cases());

        return [
            'title' => fake()->words(4, true),
            'type' => $type,
            'media_path' => match ($type) {
                AdvertisementType::Image => 'advertisements/media/'.fake()->uuid().'.jpg',
                AdvertisementType::Video => 'advertisements/media/'.fake()->uuid().'.mp4',
            },
            'link_url' => fake()->optional(0.4)->url(),
            'advertiser_name' => fake()->optional(0.6)->company(),
            'cost' => fake()->optional(0.5)->randomFloat(2, 10, 5000),
            'remarks' => fake()->optional(0.3)->realText(120),
        ];
    }
}
