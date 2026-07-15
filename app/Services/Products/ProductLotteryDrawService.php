<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\ProductLottery;
use App\Models\ProductLotteryCode;
use App\Services\Machines\MachineLotteryStockGuard;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ProductLotteryDrawService
{
    public function __construct(
        private readonly MachineLotteryStockGuard $stockGuard,
    ) {}

    /**
     * Toma el siguiente código no canjeado del sorteo, lo marca como usado y lo devuelve con su premio.
     *
     * @throws HttpException 404 si no quedan códigos, 422 si el sorteo no está disponible
     */
    public function claimNextAndRedeem(ProductLottery $lottery): ProductLotteryCode
    {
        $this->assertLotteryUsable($lottery);

        return DB::transaction(function () use ($lottery): ProductLotteryCode {
            $availableLines = [];

            if ($this->stockGuard->appliesToLottery($lottery)) {
                $machineNo = (string) $lottery->machine_no;
                $availableLines = $this->stockGuard->availableLineNumbers($machineNo);

                if ($availableLines === []) {
                    throw new HttpException(422, MachineLotteryStockGuard::MACHINE_FULLY_DISPENSED_MESSAGE);
                }
            }

            $codeQuery = ProductLotteryCode::query()
                ->where('product_lottery_id', $lottery->id)
                ->whereNull('redeemed_at')
                ->orderBy('id');

            if ($availableLines !== []) {
                $codeQuery->whereHas(
                    'prize',
                    static fn ($query) => $query->whereIn('line_number', $availableLines),
                );
            }

            /** @var ProductLotteryCode|null $code */
            $code = $codeQuery->lockForUpdate()->first();

            if ($code === null) {
                if ($this->stockGuard->appliesToLottery($lottery)
                    && $this->stockGuard->machineHasAvailableStock((string) $lottery->machine_no)) {
                    throw new HttpException(404, 'No unredeemed codes available for slots with stock.');
                }

                throw new HttpException(404, 'No unredeemed codes available for this lottery.');
            }

            $code->update(['redeemed_at' => now()]);

            return $code->load(['prize', 'productLottery.product']);
        });
    }

    private function assertLotteryUsable(ProductLottery $lottery): void
    {
        $now = now();

        if (! $lottery->is_active) {
            throw new HttpException(422, 'This lottery is not active.');
        }

        if ($lottery->valid_from !== null && $now->lt($lottery->valid_from)) {
            throw new HttpException(422, 'This lottery is not yet valid.');
        }

        if ($lottery->valid_until !== null && $now->gt($lottery->valid_until)) {
            throw new HttpException(422, 'This lottery has expired.');
        }
    }
}
