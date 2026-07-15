<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Physical slot layout for 8-column vending grids (slots numbered 1–46).
 * Columns 7–8 are the "red line" and are not used for product assignment.
 */
final class VendingSlotLayout
{
    public const int MAX_SLOT_NUMBER = 46;

    /** @var list<int> */
    private const array RED_LINE_COLUMNS = [7, 8];

    /** @var list<int> */
    private const array TIER_A_LINE_NUMBERS = [1, 2, 3];

    /** Tier A (lines 1–3 combined) ≈ 1/50 vs all tier B prizes. */
    public const int TIER_A_PRIZE_WEIGHT = 2;

    public const int TIER_B_PRIZE_WEIGHT = 9;

    public const int CLIENT_SLOT_COUNT = 36;

    public static function isRedLineSlot(int $lineNumber): bool
    {
        if ($lineNumber < 1 || $lineNumber > self::MAX_SLOT_NUMBER) {
            return true;
        }

        $column = (($lineNumber - 1) % 8) + 1;

        return in_array($column, self::RED_LINE_COLUMNS, true);
    }

    /**
     * @return list<int>
     */
    public static function activeLineNumbers(): array
    {
        $lines = [];

        for ($line = 1; $line <= self::MAX_SLOT_NUMBER; $line++) {
            if (! self::isRedLineSlot($line)) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    public static function tierCodeForLine(int $lineNumber): string
    {
        return in_array($lineNumber, self::TIER_A_LINE_NUMBERS, true) ? 'A' : 'B';
    }

    public static function prizeWeightForLine(int $lineNumber): int
    {
        return self::tierCodeForLine($lineNumber) === 'A'
            ? self::TIER_A_PRIZE_WEIGHT
            : self::TIER_B_PRIZE_WEIGHT;
    }

    /**
     * Client-facing slot label (1–36 on the 6×6 UI) for a hardware line.
     */
    public static function hardwareLineToClientNumber(int $hardwareLine): ?int
    {
        if (self::isRedLineSlot($hardwareLine)) {
            return null;
        }

        $index = array_search($hardwareLine, self::activeLineNumbers(), true);

        return $index === false ? null : $index + 1;
    }

    /**
     * Hardware / backend line (original 8-column layout) for a client slot number.
     */
    public static function clientNumberToHardwareLine(int $clientNumber): ?int
    {
        if ($clientNumber < 1 || $clientNumber > self::CLIENT_SLOT_COUNT) {
            return null;
        }

        return self::activeLineNumbers()[$clientNumber - 1] ?? null;
    }

    public static function prizeLabel(int $hardwareLine): string
    {
        $client = self::hardwareLineToClientNumber($hardwareLine);
        $tier = self::tierCodeForLine($hardwareLine);

        if ($client === null) {
            return 'Line '.$hardwareLine.' — '.$tier;
        }

        return sprintf('Client %d (line %d) — %s', $client, $hardwareLine, $tier);
    }

    /**
     * @return list<int>
     */
    public static function redLineLineNumbersInRange(): array
    {
        $skipped = [];

        for ($line = 1; $line <= self::MAX_SLOT_NUMBER; $line++) {
            if (self::isRedLineSlot($line)) {
                $skipped[] = $line;
            }
        }

        return $skipped;
    }
}
