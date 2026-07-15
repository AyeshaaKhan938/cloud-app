<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\ProductLotteryCode;
use App\Services\Machines\MachineLotteryStockGuard;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ProductLotteryCodeLookupService
{
    public function __construct(
        private readonly MachineLotteryStockGuard $stockGuard,
    ) {}

    /**
     * Resolves a lottery code and ensures the parent lottery is currently accepting lookups.
     *
     * @throws HttpException 404 when the code does not exist, 422 when the lottery cannot be used
     */
    public function resolve(string $code): ProductLotteryCode
    {
        $normalized = strtoupper(trim($code));

        if ($normalized === '') {
            throw new HttpException(404, 'Code not found.');
        }

        $record = ProductLotteryCode::query()
            ->whereRaw('UPPER(code) = ?', [$normalized])
            ->with(['prize', 'productLottery.product'])
            ->first();

        if ($record === null) {
            throw new HttpException(404, 'Code not found.');
        }

        $lottery = $record->productLottery;
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

        $this->stockGuard->assertCodeCanBeRedeemed(
            $lottery,
            $record->prize?->line_number !== null ? (int) $record->prize->line_number : null,
        );

        return $record;
    }
}
