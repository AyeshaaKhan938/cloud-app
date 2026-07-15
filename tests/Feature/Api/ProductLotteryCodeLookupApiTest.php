<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ProductLottery;
use App\Models\ProductLotteryPrize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class ProductLotteryCodeLookupApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_returns_prize_for_valid_code(): void
    {
        $lottery = ProductLottery::factory()->create(['is_active' => true]);
        $prize = ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'C',
            'name' => 'Tier C prize',
            'prize_amount' => 19.99,
            'weight' => 1,
        ]);
        $code = $lottery->codes()->create([
            'product_lottery_prize_id' => $prize->id,
            'code' => 'ABCD1234',
        ]);

        $response = $this->postJson('/api/v1/lottery-codes/lookup', [
            'code' => 'abcd1234',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.code', $code->code)
            ->assertJsonPath('data.price_tier', 'C')
            ->assertJsonPath('data.prize_amount', '19.99')
            ->assertJsonPath('data.product.sku', $lottery->product->sku);
    }

    public function test_lookup_returns_404_for_unknown_code(): void
    {
        $response = $this->postJson('/api/v1/lottery-codes/lookup', [
            'code' => 'DOESNOTEXIST',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('message', 'Code not found.');
    }

    public function test_lookup_returns_422_when_lottery_inactive(): void
    {
        $lottery = ProductLottery::factory()->create(['is_active' => false]);
        $prize = ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'A',
            'weight' => 1,
        ]);
        $lottery->codes()->create([
            'product_lottery_prize_id' => $prize->id,
            'code' => 'ZZZZ9999',
        ]);

        $response = $this->postJson('/api/v1/lottery-codes/lookup', [
            'code' => 'ZZZZ9999',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'This lottery is not active.');
    }

    public function test_management_index_requires_token(): void
    {
        Config::set('services.lottery.management_token', 'secret-token');

        $this->getJson('/api/v1/product-lotteries')
            ->assertUnauthorized();
    }

    public function test_management_index_returns_lotteries_with_valid_token(): void
    {
        Config::set('services.lottery.management_token', 'secret-token');
        $lottery = ProductLottery::factory()->create();

        $response = $this->withToken('secret-token')
            ->getJson('/api/v1/product-lotteries');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $lottery->id);
    }

    public function test_management_show_includes_prizes(): void
    {
        Config::set('services.lottery.management_token', 'secret-token');
        $lottery = ProductLottery::factory()->create();
        ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'B',
        ]);

        $response = $this->withToken('secret-token')
            ->getJson('/api/v1/product-lotteries/'.$lottery->id);

        $response->assertOk()
            ->assertJsonPath('data.prizes.0.tier_code', 'B');
    }
}
