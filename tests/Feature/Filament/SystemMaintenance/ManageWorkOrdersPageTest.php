<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\SystemMaintenance;

use App\Enums\WorkOrderStatus;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManageWorkOrdersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_work_orders_index(): void
    {
        $this->get(route('filament.admin.resources.work-orders.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_work_orders_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.work-orders.index'))
            ->assertOk();
    }

    public function test_work_orders_list_shows_unprocessed_record_under_default_filter(): void
    {
        $user = User::factory()->create();
        $workOrder = WorkOrder::factory()->create([
            'work_order_number' => 'WO-TEST-001',
            'status' => WorkOrderStatus::Unprocessed,
        ]);

        $this->actingAs($user)
            ->get(route('filament.admin.resources.work-orders.index'))
            ->assertOk()
            ->assertSee('WO-TEST-001', false);
    }
}
