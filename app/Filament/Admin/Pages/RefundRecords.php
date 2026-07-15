<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\AuthorizesFeaturePage;
use App\Filament\Admin\Widgets\RefundRecordsTable;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class RefundRecords extends Page
{
    use AuthorizesFeaturePage;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static ?string $navigationLabel = 'Refund Records';

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 41;

    protected static ?string $title = 'Refund Records';

    protected static ?string $slug = 'sales/refund-records';

    public function getSubheading(): ?string
    {
        return 'All refunded transactions. Filter by date or machine number.';
    }

    /**
     * @return array<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [RefundRecordsTable::class];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    protected static function requiredUserFeature(): UserFeature
    {
        return UserFeature::Sales;
    }
}
