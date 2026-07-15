<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ProductLottery;
use App\Models\ProductLotteryPrize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductLotteryDrawApiTest extends TestCase
{
    use RefreshDatabase;

    private function drawUrl(ProductLottery $lottery): string
    {
        $lottery->refresh();

        return '/api/v1/product-lottery-draw/'.$lottery->public_draw_token;
    }

    public function test_draw_claims_next_code_and_returns_price_and_message_in_english(): void
    {
        $lottery = ProductLottery::factory()->create(['is_active' => true]);
        $this->assertNotNull($lottery->fresh()->public_draw_token);
        $prize = ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'A',
            'name' => 'Precio especial verano',
            'prize_amount' => 15.5,
            'weight' => 1,
        ]);
        $code = $lottery->codes()->create([
            'product_lottery_prize_id' => $prize->id,
            'code' => 'DRAW0001',
        ]);

        $response = $this->postJson($this->drawUrl($lottery));

        $response->assertOk()
            ->assertJson([
                'price' => 'Precio especial verano',
                'message' => 'Congratulations! Your special price is Precio especial verano',
            ]);

        $this->assertNotNull($code->fresh()->redeemed_at);
    }

    public function test_draw_uses_tier_code_when_prize_name_is_empty(): void
    {
        $lottery = ProductLottery::factory()->create(['is_active' => true]);
        $prize = ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'GOLD',
            'name' => null,
            'weight' => 1,
        ]);
        $lottery->codes()->create([
            'product_lottery_prize_id' => $prize->id,
            'code' => 'TIERONLY',
        ]);

        $this->postJson($this->drawUrl($lottery))
            ->assertOk()
            ->assertJsonPath('price', 'GOLD')
            ->assertJsonPath('message', 'Congratulations! Your special price is GOLD');
    }

    public function test_draw_takes_codes_in_stable_order(): void
    {
        $lottery = ProductLottery::factory()->create(['is_active' => true]);
        $prize = ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'prize_amount' => 1,
            'weight' => 1,
        ]);
        $first = $lottery->codes()->create([
            'product_lottery_prize_id' => $prize->id,
            'code' => 'FIRST001',
        ]);
        $second = $lottery->codes()->create([
            'product_lottery_prize_id' => $prize->id,
            'code' => 'SECOND02',
        ]);

        $this->postJson($this->drawUrl($lottery))->assertOk();
        $this->assertNotNull($first->fresh()->redeemed_at);
        $this->assertNull($second->fresh()->redeemed_at);

        $this->postJson($this->drawUrl($lottery))->assertOk();
        $this->assertNotNull($second->fresh()->redeemed_at);
    }

    public function test_draw_returns_404_when_no_codes_left(): void
    {
        $lottery = ProductLottery::factory()->create(['is_active' => true]);

        $this->postJson($this->drawUrl($lottery))
            ->assertNotFound()
            ->assertJsonPath('message', 'No unredeemed codes available for this lottery.');
    }

    public function test_draw_returns_422_when_lottery_inactive(): void
    {
        $lottery = ProductLottery::factory()->create(['is_active' => false]);
        $prize = ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'weight' => 1,
        ]);
        $lottery->codes()->create([
            'product_lottery_prize_id' => $prize->id,
            'code' => 'INACTIVE1',
        ]);

        $this->postJson($this->drawUrl($lottery))
            ->assertStatus(422)
            ->assertJsonPath('message', 'This lottery is not active.');
    }

    public function test_draw_returns_404_for_unknown_token(): void
    {
        $this->postJson('/api/v1/product-lottery-draw/zzzzzzzzzzzzzzzzzzzzzzzzzz')
            ->assertNotFound()
            ->assertJsonPath('message', 'Lottery not found.');
    }
}
