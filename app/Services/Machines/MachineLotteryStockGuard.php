<?php

declare(strict_types=1);

namespace App\Services\Machines;

use App\Models\MachineSlot;
use App\Models\ProductLottery;
use App\Support\VendingSlotLayout;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Enforces per-slot physical stock limits for machine-linked lotteries.
 * Each slot may only dispense while {@see MachineSlot::$current_stock} is greater than zero.
 */
final class MachineLotteryStockGuard
{
    public const string MACHINE_FULLY_DISPENSED_MESSAGE = 'This machine is fully dispensed and is not accepting codes.';

    public const string SLOT_OUT_OF_STOCK_MESSAGE = 'This prize slot is out of stock. This code cannot be used.';

    public const string RED_LINE_SLOT_MESSAGE = 'This slot is not used on this machine and cannot be dispensed.';

    public function appliesToLottery(ProductLottery $lottery): bool
    {
        return filled($lottery->machine_no);
    }

    public function assertCodeCanBeRedeemed(ProductLottery $lottery, ?int $lineNumber): void
    {
        if (! $this->appliesToLottery($lottery)) {
            return;
        }

        $machineNo = (string) $lottery->machine_no;

        if (! $this->machineHasAvailableStock($machineNo)) {
            throw new HttpException(422, self::MACHINE_FULLY_DISPENSED_MESSAGE);
        }

        if ($lineNumber === null) {
            return;
        }

        if (VendingSlotLayout::isRedLineSlot($lineNumber)) {
            throw new HttpException(422, self::RED_LINE_SLOT_MESSAGE);
        }

        if (! $this->lineHasStock($machineNo, $lineNumber)) {
            throw new HttpException(422, self::SLOT_OUT_OF_STOCK_MESSAGE);
        }
    }

    public function machineHasAvailableStock(string $machineNo): bool
    {
        return $this->activeStockedSlotsQuery($machineNo)->exists();
    }

    public function lineHasStock(string $machineNo, int $lineNumber): bool
    {
        return $this->activeStockedSlotsQuery($machineNo)
            ->where('line_number', $lineNumber)
            ->exists();
    }

    /**
     * @return list<int>
     */
    public function availableLineNumbers(string $machineNo): array
    {
        return $this->activeStockedSlotsQuery($machineNo)
            ->orderBy('line_number')
            ->pluck('line_number')
            ->map(static fn (mixed $line): int => (int) $line)
            ->values()
            ->all();
    }

    /**
     * @return Builder<MachineSlot>
     */
    private function activeStockedSlotsQuery(string $machineNo): Builder
    {
        return MachineSlot::query()
            ->whereHas('machine', static fn ($query) => $query->where('machine_number', $machineNo))
            ->where('is_active', true)
            ->whereNotNull('product_id')
            ->where('current_stock', '>', 0)
            ->whereNotIn('line_number', VendingSlotLayout::redLineLineNumbersInRange());
    }
}
