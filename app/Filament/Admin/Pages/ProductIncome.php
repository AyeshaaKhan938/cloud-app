<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\AuthorizesFeaturePage;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Filament\Admin\Widgets\ProductIncomeTable;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class ProductIncome extends Page
{
    use AuthorizesFeaturePage;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Revenue by product';

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::Reports;

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'Product Income';

    protected static ?string $slug = 'reports/product-income';

    public function getSubheading(): ?string
    {
        return 'See which products drive the most revenue across your machines.';
    }

    /**
     * @return array<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [ProductIncomeTable::class];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    protected static function requiredUserFeature(): UserFeature
    {
        return UserFeature::Reports;
    }
}
