<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Products;

use App\Enums\CouponGenerationRule;
use App\Models\ProductLottery;
use App\Models\ProductLotteryPrize;
use App\Models\User;
use App\Services\Products\ProductLotteryCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManageProductLotteriesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_product_lotteries_index(): void
    {
        $this->get(route('filament.admin.resources.products.product-lotteries.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_access_product_lotteries_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.products.product-lotteries.index'))
            ->assertOk();
    }

    public function test_product_lotteries_list_shows_records(): void
    {
        $user = User::factory()->create();
        $lotteries = ProductLottery::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.products.product-lotteries.index'))
            ->assertOk()
            ->assertSee($lotteries->first()->name);
    }

    public function test_authenticated_user_can_access_lottery_codes_page(): void
    {
        $user = User::factory()->create();
        $lottery = ProductLottery::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.resources.products.product-lotteries.codes', ['record' => $lottery]))
            ->assertOk()
            ->assertSee($lottery->name);
    }

    public function test_lottery_codes_page_lists_generated_codes(): void
    {
        $user = User::factory()->create();
        $lottery = ProductLottery::factory()->create(['quantity' => 2]);
        ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'A',
            'weight' => 1,
            'prize_amount' => 10,
        ]);
        app(ProductLotteryCodeGenerator::class)->generateIfNeeded($lottery);
        $code = $lottery->fresh()->codes->first()->code;

        $this->actingAs($user)
            ->get(route('filament.admin.resources.products.product-lotteries.codes', ['record' => $lottery]))
            ->assertOk()
            ->assertSee($code);
    }

    public function test_product_lottery_code_generator_creates_expected_codes(): void
    {
        $lottery = ProductLottery::factory()->create([
            'quantity' => 5,
            'generation_rule' => CouponGenerationRule::Numbers,
        ]);
        ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'A',
            'weight' => 1,
            'prize_amount' => 5,
        ]);

        app(ProductLotteryCodeGenerator::class)->generateIfNeeded($lottery);

        $lottery->load('codes');

        $this->assertCount(5, $lottery->codes);
        $this->assertSame(6, strlen((string) $lottery->codes->first()->code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $lottery->codes->first()->code);
        $this->assertNotNull($lottery->codes->first()->product_lottery_prize_id);
    }

    public function test_product_lottery_code_generator_uses_letter_rule(): void
    {
        $lottery = ProductLottery::factory()->create([
            'generation_rule' => CouponGenerationRule::Letter,
            'quantity' => 3,
        ]);
        ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'B',
            'weight' => 1,
        ]);

        app(ProductLotteryCodeGenerator::class)->generateIfNeeded($lottery);

        $lottery->load('codes');

        $this->assertCount(3, $lottery->codes);
        $this->assertMatchesRegularExpression('/^[A-Z]{6}$/', (string) $lottery->codes->first()->code);
    }

    public function test_product_lottery_code_generator_uses_letters_and_numbers_rule(): void
    {
        $lottery = ProductLottery::factory()->create([
            'generation_rule' => CouponGenerationRule::LettersAndNumbers,
            'quantity' => 2,
        ]);
        ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'C',
            'weight' => 1,
        ]);

        app(ProductLotteryCodeGenerator::class)->generateIfNeeded($lottery);

        $lottery->load('codes');

        $this->assertCount(2, $lottery->codes);
        $this->assertSame(8, strlen((string) $lottery->codes->first()->code));
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', (string) $lottery->codes->first()->code);
    }
}
