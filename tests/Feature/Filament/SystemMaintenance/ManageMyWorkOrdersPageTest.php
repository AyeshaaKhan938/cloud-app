<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\SystemMaintenance;

use App\Enums\WorkOrderStatus;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManageMyWorkOrdersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_my_work_orders_index(): void
    {
        $this->get(route('filament.admin.resources.my-work-orders.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_my_work_orders_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.my-work-orders.index'))
            ->assertOk();
    }

    public function test_my_work_orders_list_only_shows_records_for_current_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $mine = WorkOrder::factory()->create([
            'user_id' => $userA->id,
            'work_order_number' => 'WO-MINE-001',
            'status' => WorkOrderStatus::Unprocessed,
        ]);

        WorkOrder::factory()->create([
            'user_id' => $userB->id,
            'work_order_number' => 'WO-THEIRS-001',
            'status' => WorkOrderStatus::Unprocessed,
        ]);

        $this->actingAs($userA)
            ->get(route('filament.admin.resources.my-work-orders.index'))
            ->assertOk()
            ->assertSee($mine->work_order_number, false)
            ->assertDontSee('WO-THEIRS-001', false);
    }

    public function test_my_work_orders_list_shows_unprocessed_record_under_default_filter(): void
    {
        $user = User::factory()->create();
        $workOrder = WorkOrder::factory()->create([
            'user_id' => $user->id,
            'work_order_number' => 'WO-MY-UNPROC',
            'status' => WorkOrderStatus::Unprocessed,
        ]);

        $this->actingAs($user)
            ->get(route('filament.admin.resources.my-work-orders.index'))
            ->assertOk()
            ->assertSee($workOrder->work_order_number, false);
    }
}
