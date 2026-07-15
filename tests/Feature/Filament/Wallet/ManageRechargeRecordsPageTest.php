<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Wallet;

use App\Models\RechargeRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManageRechargeRecordsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_wallet_recharge_records_index(): void
    {
        $this->get(route('filament.admin.resources.wallet-recharge-records.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_wallet_recharge_records_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.wallet-recharge-records.index'))
            ->assertOk();
    }

    public function test_table_lists_recharge_record_rows(): void
    {
        $actor = User::factory()->create();
        $owner = User::factory()->create(['email' => 'admin@vmfusa.com']);
        $record = RechargeRecord::factory()
            ->for($owner)
            ->create([
                'machine_number' => '859902837603003',
                'amount' => 0.23,
                'detail' => 'Age recognition',
                'service_type' => 'Age ID Screen',
            ]);

        $this->actingAs($actor)
            ->get(route('filament.admin.resources.wallet-recharge-records.index'))
            ->assertOk()
            ->assertSee($owner->email, false)
            ->assertSee($record->machine_number, false);
    }
}
