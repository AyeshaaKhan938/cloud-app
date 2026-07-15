<?php

declare(strict_types=1);

namespace App\Services\Machines;

use App\Enums\CouponGenerationRule;
use App\Models\Machine;
use App\Models\MachineSlot;
use App\Models\Product;
use App\Models\ProductLottery;
use App\Models\ProductLotteryPrize;
use App\Services\Products\ProductLotteryCodeGenerator;
use App\Support\VendingSlotLayout;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ConfigureMysteryVendingMachineService
{
    public const string MYSTERY_PRODUCT_SKU = 'MYSTERY-VEND';

    public const string MYSTERY_PRODUCT_NAME = 'Mystery Vending';

    public const string DEFAULT_LOTTERY_NAME = 'Mystery Vending Lottery';

    /** Physical units stocked per slot (each successful dispense decrements by one). */
    public const int UNITS_PER_SLOT = 4;

    /**
     * @return array{
     *     machine_number: string,
     *     machine_name: string,
     *     product_id: int,
     *     slots_configured: int,
     *     slots_removed: int,
     *     lottery_id: int|null,
     *     prizes_created: int,
     *     lottery_codes_deleted: int,
     *     lottery_codes_generated: int,
     * }
     */
    public function configure(
        Machine $machine,
        bool $resetLottery = false,
        float $productPrice = 0,
        int $maxStock = self::UNITS_PER_SLOT,
        int $currentStock = self::UNITS_PER_SLOT,
        bool $regenerateCodes = false,
        ?int $lotteryCodeQuantity = null,
    ): array {
        $activeLines = VendingSlotLayout::activeLineNumbers();
        $redLineNumbers = VendingSlotLayout::redLineLineNumbersInRange();

        return DB::transaction(function () use (
            $machine,
            $resetLottery,
            $regenerateCodes,
            $lotteryCodeQuantity,
            $productPrice,
            $maxStock,
            $currentStock,
            $activeLines,
            $redLineNumbers,
        ): array {
            $product = Product::query()->updateOrCreate(
                ['sku' => self::MYSTERY_PRODUCT_SKU],
                [
                    'name' => self::MYSTERY_PRODUCT_NAME,
                    'description' => 'Mystery vending product for all machine slots.',
                    'price' => $productPrice,
                    'is_active' => true,
                ],
            );

            $slotsConfigured = 0;

            foreach ($activeLines as $lineNumber) {
                MachineSlot::query()->updateOrCreate(
                    [
                        'machine_id' => $machine->id,
                        'line_number' => $lineNumber,
                    ],
                    [
                        'product_id' => $product->id,
                        'price' => $productPrice,
                        'max_stock' => $maxStock,
                        'current_stock' => $currentStock,
                        'stock_alarm_threshold' => min(2, max(1, $maxStock - 1)),
                        'is_active' => true,
                        'is_fault' => false,
                    ],
                );

                $slotsConfigured++;
            }

            $slotsRemoved = MachineSlot::query()
                ->where('machine_id', $machine->id)
                ->where(function ($query) use ($redLineNumbers): void {
                    $query->where('line_number', '>', VendingSlotLayout::MAX_SLOT_NUMBER)
                        ->orWhereIn('line_number', $redLineNumbers);
                })
                ->delete();

            $lotteryResult = $this->syncLottery(
                machine: $machine,
                product: $product,
                activeLines: $activeLines,
                resetLottery: $resetLottery,
                regenerateCodes: $regenerateCodes,
                lotteryCodeQuantity: $lotteryCodeQuantity,
            );

            return [
                'machine_number' => $machine->machine_number,
                'machine_name' => $machine->machine_name,
                'product_id' => $product->id,
                'slots_configured' => $slotsConfigured,
                'slots_removed' => $slotsRemoved,
                'lottery_id' => $lotteryResult['lottery_id'],
                'prizes_created' => $lotteryResult['prizes_created'],
                'lottery_codes_deleted' => $lotteryResult['lottery_codes_deleted'],
                'lottery_codes_generated' => $lotteryResult['lottery_codes_generated'],
            ];
        });
    }

    public function findMachineByNumberSuffix(string $suffix): ?Machine
    {
        $suffix = trim($suffix);

        if ($suffix === '') {
            return null;
        }

        $machines = Machine::query()
            ->where('machine_number', 'like', '%'.$suffix)
            ->orderBy('machine_number')
            ->get();

        if ($machines->count() > 1) {
            throw new InvalidArgumentException(
                'Multiple machines match suffix "'.$suffix.'": '.$machines->pluck('machine_number')->implode(', '),
            );
        }

        return $machines->first();
    }

    /**
     * @param  list<int>  $activeLines
     * @return array{lottery_id: int|null, prizes_created: int, lottery_codes_deleted: int, lottery_codes_generated: int}
     */
    private function syncLottery(
        Machine $machine,
        Product $product,
        array $activeLines,
        bool $resetLottery,
        bool $regenerateCodes,
        ?int $lotteryCodeQuantity,
    ): array {
        $lottery = ProductLottery::query()
            ->where('machine_no', $machine->machine_number)
            ->first();

        if ($lottery === null) {
            $lottery = ProductLottery::query()->create([
                'product_id' => $product->id,
                'name' => self::DEFAULT_LOTTERY_NAME,
                'is_active' => true,
                'quantity' => 0,
                'generation_rule' => CouponGenerationRule::LettersAndNumbers,
                'machine_no' => $machine->machine_number,
            ]);
        } else {
            $lottery->update([
                'product_id' => $product->id,
                'name' => self::DEFAULT_LOTTERY_NAME,
            ]);
        }

        $codesDeleted = 0;
        $codesGenerated = 0;
        $prizesCreated = 0;

        if ($lottery->codes()->exists() && ! $resetLottery && ! $regenerateCodes) {
            return [
                'lottery_id' => $lottery->id,
                'prizes_created' => 0,
                'lottery_codes_deleted' => 0,
                'lottery_codes_generated' => 0,
            ];
        }

        if (($resetLottery || $regenerateCodes) && $lottery->codes()->exists()) {
            $codesDeleted = $lottery->codes()->delete();
        }

        $shouldRebuildPrizes = $resetLottery
            || $lottery->prizes()->count() !== count($activeLines);

        if ($shouldRebuildPrizes) {
            $lottery->prizes()->delete();

            $sortOrder = 0;

            foreach ($activeLines as $lineNumber) {
                ProductLotteryPrize::query()->create([
                    'product_lottery_id' => $lottery->id,
                    'tier_code' => VendingSlotLayout::tierCodeForLine($lineNumber),
                    'name' => VendingSlotLayout::prizeLabel($lineNumber),
                    'prize_amount' => 0,
                    'weight' => VendingSlotLayout::prizeWeightForLine($lineNumber),
                    'sort_order' => $sortOrder,
                    'line_number' => $lineNumber,
                ]);

                $sortOrder++;
                $prizesCreated++;
            }
        }

        if (($resetLottery || $regenerateCodes) && ! $lottery->codes()->exists()) {
            $quantity = $lotteryCodeQuantity ?? count($activeLines) * self::UNITS_PER_SLOT;
            $lottery->update(['quantity' => $quantity]);
            app(ProductLotteryCodeGenerator::class)->generateIfNeeded($lottery->fresh());
            $codesGenerated = $lottery->fresh()->codes()->count();
        }

        return [
            'lottery_id' => $lottery->id,
            'prizes_created' => $prizesCreated,
            'lottery_codes_deleted' => $codesDeleted,
            'lottery_codes_generated' => $codesGenerated,
        ];
    }
}
