<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WorkOrderIssueType;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderReportingStatus;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
final class WorkOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'work_order_number' => fake()->unique()->bothify('WO-########'),
            'device_number' => fake()->optional(0.8)->bothify('DEV-#####'),
            'device_name' => fake()->optional(0.7)->words(3, true),
            'associated_account' => fake()->optional(0.6)->userName(),
            'device_type' => fake()->optional(0.7)->randomElement(['Vending', 'Kiosk', 'Cooler', 'Screen']),
            'submitted_by' => fake()->optional(0.9)->name(),
            'issue_description' => fake()->optional(0.85)->paragraph(),
            'issue_type' => fake()->optional(0.7)->randomElement(WorkOrderIssueType::cases()),
            'attachments' => null,
            'submitted_at' => fake()->dateTimeBetween('-60 days', 'now'),
            'user_rating' => fake()->optional(0.3)->numberBetween(1, 5),
            'priority' => fake()->randomElement(WorkOrderPriority::cases()),
            'reporting_status' => fake()->randomElement(WorkOrderReportingStatus::cases()),
            'status' => fake()->randomElement(WorkOrderStatus::cases()),
        ];
    }
}
