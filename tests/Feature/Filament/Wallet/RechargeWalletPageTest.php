<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Wallet;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RechargeWalletPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_recharge_wallet_page(): void
    {
        $this->get(route('filament.admin.pages.recharge-wallet'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_recharge_wallet_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.pages.recharge-wallet'))
            ->assertOk();
    }

    public function test_recharge_wallet_page_displays_wallet_balance(): void
    {
        $user = User::factory()->create([
            'wallet_balance' => 12.34,
        ]);

        $this->actingAs($user)
            ->get(route('filament.admin.pages.recharge-wallet'))
            ->assertOk()
            ->assertSee('12.34', false);
    }
}
