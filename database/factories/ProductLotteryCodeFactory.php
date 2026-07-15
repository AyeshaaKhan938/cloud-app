<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductLottery;
use App\Models\ProductLotteryCode;
use App\Models\ProductLotteryPrize;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductLotteryCode>
 */
final class ProductLotteryCodeFactory extends Factory
{
    protected $model = ProductLotteryCode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->uniqueCodeString(),
            'redeemed_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ProductLotteryCode $model): void {
            if ($model->product_lottery_id === null) {
                $lottery = ProductLottery::factory()->create();
                $model->product_lottery_id = $lottery->id;
            }

            if ($model->product_lottery_prize_id === null) {
                $prize = ProductLotteryPrize::factory()->create([
                    'product_lottery_id' => $model->product_lottery_id,
                ]);
                $model->product_lottery_prize_id = $prize->id;
            }
        });
    }

    private function uniqueCodeString(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (ProductLotteryCode::query()->where('code', $code)->exists());

        return $code;
    }
}
