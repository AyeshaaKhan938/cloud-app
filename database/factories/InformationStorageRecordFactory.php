<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InformationStorageCollectionMethod;
use App\Enums\InformationStorageRuleType;
use App\Models\InformationStorageRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InformationStorageRecord>
 */
final class InformationStorageRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ruleType = fake()->randomElement(InformationStorageRuleType::cases());

        return [
            'collection_method' => InformationStorageCollectionMethod::MemberCard,
            'ic_card_number' => fake()->unique()->bothify('IC-##########'),
            'user_name' => fake()->name(),
            'account' => fake()->optional(0.5)->userName(),
            'mobile_number' => fake()->optional(0.7)->phoneNumber(),
            'email' => fake()->optional(0.6)->safeEmail(),
            'promotion_plan' => fake()->optional(0.4)->words(3, true),
            'rule_type' => $ruleType,
            'points' => $ruleType === InformationStorageRuleType::Points
                ? fake()->randomFloat(2, 0, 5000)
                : null,
            'available_times_in_cycle' => $ruleType === InformationStorageRuleType::Times
                ? fake()->numberBetween(1, 100)
                : null,
            'used_times_in_cycle' => $ruleType === InformationStorageRuleType::Times
                ? fake()->numberBetween(0, 5)
                : 0,
            'remarks' => fake()->optional(0.3)->sentence(),
        ];
    }
}
