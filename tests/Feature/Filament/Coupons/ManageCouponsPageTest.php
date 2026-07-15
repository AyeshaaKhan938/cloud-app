<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Coupons;

use App\Enums\CouponDistributionRule;
use App\Enums\CouponGenerationRule;
use App\Models\Coupon;
use App\Models\User;
use App\Services\Coupons\CouponCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManageCouponsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_coupons_index(): void
    {
        $this->get(route('filament.admin.resources.coupons.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_coupons_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.coupons.index'))
            ->assertOk();
    }

    public function test_coupons_list_shows_records(): void
    {
        $user = User::factory()->create();
        $coupons = Coupon::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.coupons.index'))
            ->assertOk()
            ->assertSee($coupons->first()->name);
    }

    public function test_authenticated_user_can_access_coupon_codes_page(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.coupons.codes', ['record' => $coupon]))
            ->assertOk()
            ->assertSee($coupon->name);
    }

    public function test_coupon_codes_page_lists_generated_codes(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create(['quantity' => 2]);
        app(CouponCodeGenerator::class)->generateIfNeeded($coupon);
        $code = $coupon->fresh()->codes->first()->code;

        $this->actingAs($user)
            ->get(route('filament.admin.resources.coupons.codes', ['record' => $coupon]))
            ->assertOk()
            ->assertSee($code);
    }

    public function test_coupon_code_generator_creates_expected_codes(): void
    {
        $coupon = Coupon::factory()->create([
            'quantity' => 5,
            'usage_frequency' => 10,
        ]);

        app(CouponCodeGenerator::class)->generateIfNeeded($coupon);

        $coupon->load('codes');

        $this->assertCount(5, $coupon->codes);
        $this->assertSame(6, strlen((string) $coupon->codes->first()->code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $coupon->codes->first()->code);
        $this->assertSame(10, $coupon->codes->first()->max_uses);
    }

    public function test_coupon_code_generator_uses_letter_rule(): void
    {
        $coupon = Coupon::factory()->create([
            'generation_rule' => CouponGenerationRule::Letter,
            'distribution_rule' => CouponDistributionRule::QrCode,
            'quantity' => 3,
            'usage_frequency' => 1,
        ]);

        app(CouponCodeGenerator::class)->generateIfNeeded($coupon);

        $coupon->load('codes');

        $this->assertCount(3, $coupon->codes);
        $this->assertMatchesRegularExpression('/^[A-Z]{6}$/', (string) $coupon->codes->first()->code);
    }

    public function test_coupon_code_generator_uses_letters_and_numbers_rule(): void
    {
        $coupon = Coupon::factory()->create([
            'generation_rule' => CouponGenerationRule::LettersAndNumbers,
            'distribution_rule' => CouponDistributionRule::CouponCodeAndQr,
            'quantity' => 2,
            'usage_frequency' => 1,
        ]);

        app(CouponCodeGenerator::class)->generateIfNeeded($coupon);

        $coupon->load('codes');

        $this->assertCount(2, $coupon->codes);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{6}$/', (string) $coupon->codes->first()->code);
    }
}
