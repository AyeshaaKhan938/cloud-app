<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ProductLottery;
use App\Models\ProductLotteryCode;
use App\Models\ProductLotteryPrize;
use App\Services\Products\ProductLotteryUnredeemedRedistributor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ProductLotteryPrizeRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_delete_prize_that_has_codes(): void
    {
        $lottery = ProductLottery::factory()->create();
        $prize = ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'A',
        ]);
        ProductLotteryCode::factory()->create([
            'product_lottery_id' => $lottery->id,
            'product_lottery_prize_id' => $prize->id,
        ]);

        $this->expectException(ValidationException::class);

        $prize->delete();
    }

    public function test_redistribute_only_changes_unredeemed_codes_when_new_prize_is_added(): void
    {
        $lottery = ProductLottery::factory()->create();
        $prizeA = ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'A',
            'weight' => 1,
        ]);
        $redeemed = ProductLotteryCode::factory()->create([
            'product_lottery_id' => $lottery->id,
            'product_lottery_prize_id' => $prizeA->id,
            'redeemed_at' => now(),
        ]);
        $unredeemed = ProductLotteryCode::factory()->create([
            'product_lottery_id' => $lottery->id,
            'product_lottery_prize_id' => $prizeA->id,
            'redeemed_at' => null,
        ]);

        ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'B',
            'weight' => 1,
        ]);

        $redeemed->refresh();
        $unredeemed->refresh();

        $this->assertSame($prizeA->id, $redeemed->product_lottery_prize_id);
        $lottery->load('prizes');
        $this->assertContains(
            $unredeemed->product_lottery_prize_id,
            $lottery->prizes->pluck('id')->all()
        );
    }

    public function test_redistribute_keeps_unredeemed_on_valid_prize_when_weight_changes(): void
    {
        $lottery = ProductLottery::factory()->create();
        $prizeA = ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'A',
            'weight' => 10,
        ]);
        $prizeB = ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'B',
            'weight' => 1,
        ]);
        $code = ProductLotteryCode::factory()->create([
            'product_lottery_id' => $lottery->id,
            'product_lottery_prize_id' => $prizeB->id,
            'redeemed_at' => null,
        ]);

        $prizeA->update(['weight' => 1]);

        $code->refresh();
        $this->assertContains(
            $code->product_lottery_prize_id,
            [$prizeA->id, $prizeB->id]
        );
    }

    public function test_redistributor_service_leaves_redeemed_unchanged(): void
    {
        $lottery = ProductLottery::factory()->create();
        $prizeA = ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'A',
            'weight' => 1,
        ]);
        $prizeB = ProductLotteryPrize::factory()->create([
            'product_lottery_id' => $lottery->id,
            'tier_code' => 'B',
            'weight' => 1,
        ]);
        $redeemed = ProductLotteryCode::factory()->create([
            'product_lottery_id' => $lottery->id,
            'product_lottery_prize_id' => $prizeA->id,
            'redeemed_at' => now(),
        ]);

        app(ProductLotteryUnredeemedRedistributor::class)->redistributeUnredeemed($lottery->fresh());

        $this->assertSame($prizeA->id, $redeemed->fresh()->product_lottery_prize_id);
    }
}
