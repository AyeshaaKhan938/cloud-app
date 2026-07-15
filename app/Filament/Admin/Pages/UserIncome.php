<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\AuthorizesFeaturePage;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Filament\Admin\Widgets\UserIncomeTable;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class UserIncome extends Page
{
    use AuthorizesFeaturePage;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Revenue by account';

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::Reports;

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'User Income';

    protected static ?string $slug = 'reports/user-income';

    public function getSubheading(): ?string
    {
        return 'Compare sales totals across customer and partner accounts.';
    }

    /**
     * @return array<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [UserIncomeTable::class];
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
