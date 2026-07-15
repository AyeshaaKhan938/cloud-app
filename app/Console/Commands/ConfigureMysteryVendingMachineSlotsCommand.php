<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Machine;
use App\Services\Machines\ConfigureMysteryVendingMachineService;
use App\Support\VendingSlotLayout;
use Illuminate\Console\Command;
use InvalidArgumentException;

final class ConfigureMysteryVendingMachineSlotsCommand extends Command
{
    protected $signature = 'machine:configure-mystery-vending
                            {--suffix=34 : Machine number suffix (default: 34)}
                            {--machine= : Full machine number (overrides --suffix)}
                            {--price=0 : Slot and product price}
                            {--max-stock=4 : Max stock per slot (physical units)}
                            {--current-stock=4 : Current stock per slot}
                            {--reset-lottery : Delete existing lottery codes and rebuild prize tiers}
                            {--regenerate-codes : Delete codes and generate balanced codes (default qty 144 = 36×4)}
                            {--code-quantity= : Override number of lottery codes to generate}
                            {--force : Run without confirmation prompt}';

    protected $description = 'Assign Mystery Vending to slots 1–46 (skip red-line columns), tier A on slots 1–3, tier B on the rest';

    public function handle(ConfigureMysteryVendingMachineService $service): int
    {
        $machine = $this->resolveMachine($service);

        if ($machine === null) {
            $identifier = $this->option('machine') ?: 'ending in '.$this->option('suffix');
            $this->error("No machine found {$identifier}.");

            return self::FAILURE;
        }

        $this->info("Machine: {$machine->machine_name} ({$machine->machine_number})");
        $this->line('Active slots: '.implode(', ', VendingSlotLayout::activeLineNumbers()));
        $this->line('Skipping red line: '.implode(', ', VendingSlotLayout::redLineLineNumbersInRange()));

        if (! $this->option('force') && ! $this->confirm('Continue? Existing slot products will be replaced.', true)) {
            $this->comment('Cancelled.');

            return self::SUCCESS;
        }

        try {
            $codeQuantity = $this->option('code-quantity');
            $result = $service->configure(
                machine: $machine,
                resetLottery: (bool) $this->option('reset-lottery'),
                productPrice: (float) $this->option('price'),
                maxStock: (int) $this->option('max-stock'),
                currentStock: (int) $this->option('current-stock'),
                regenerateCodes: (bool) $this->option('regenerate-codes'),
                lotteryCodeQuantity: is_numeric($codeQuantity) ? (int) $codeQuantity : null,
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Product: {$result['product_id']} — ".ConfigureMysteryVendingMachineService::MYSTERY_PRODUCT_NAME);
        $this->info("Slots configured: {$result['slots_configured']}");
        $this->info("Slots removed (red line / >46): {$result['slots_removed']}");

        if ($result['prizes_created'] > 0) {
            $this->info("Lottery #{$result['lottery_id']}: {$result['prizes_created']} prize tiers (A = slots 1–3, B = rest).");
        } else {
            $this->warn('Lottery prizes not rebuilt — existing codes remain. Use --reset-lottery to replace tiers (deletes all codes).');
        }

        if ($result['lottery_codes_deleted'] > 0) {
            $this->warn("Deleted {$result['lottery_codes_deleted']} lottery code(s).");
        }

        if ($result['lottery_codes_generated'] > 0) {
            $this->info("Generated {$result['lottery_codes_generated']} balanced lottery code(s) (all 36 client rows).");
        }

        return self::SUCCESS;
    }

    private function resolveMachine(ConfigureMysteryVendingMachineService $service): ?Machine
    {
        $machineNumber = $this->option('machine');

        if (is_string($machineNumber) && $machineNumber !== '') {
            return Machine::query()
                ->where('machine_number', $machineNumber)
                ->first();
        }

        try {
            return $service->findMachineByNumberSuffix((string) $this->option('suffix'));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return null;
        }
    }
}
