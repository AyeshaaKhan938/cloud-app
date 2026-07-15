<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Wallet;

use App\Models\RenewalEquipment;
use App\Models\RenewalHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RenewalCenterPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_renewal_center_page(): void
    {
        $this->get(route('filament.admin.pages.renewal-center'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_renewal_center_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.pages.renewal-center'))
            ->assertOk();
    }

    public function test_renewal_center_page_renders_table_headings_and_rows(): void
    {
        $user = User::factory()->create();
        RenewalEquipment::factory()->for($user)->create([
            'device_name' => 'Jennifer Gonzalez',
            'equipment_number' => '859902837603003',
        ]);
        RenewalHistory::factory()
            ->for($user)
            ->create([
                'renew_equipment' => 'Device link example',
            ]);

        $this->actingAs($user)
            ->get(route('filament.admin.pages.renewal-center'))
            ->assertOk()
            ->assertSee('Renewal list', false)
            ->assertSee('Historical records', false)
            ->assertSee('859902837603003', false)
            ->assertSee('Device link example', false);
    }
}
