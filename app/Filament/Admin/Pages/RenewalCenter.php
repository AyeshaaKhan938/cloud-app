<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\AuthorizesFeaturePage;
use App\Filament\Admin\Widgets\RenewalEquipmentTableWidget;
use App\Filament\Admin\Widgets\RenewalHistoryTableWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

final class RenewalCenter extends Page
{
    use AuthorizesFeaturePage;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Renewal Center';

    protected static string|UnitEnum|null $navigationGroup = 'Wallet';

    protected static ?int $navigationSort = 92;

    protected static ?string $title = 'Renewal Center';

    protected static ?string $slug = 'renewal-center';

    public function getSubheading(): ?string
    {
        return 'Track machine subscription renewals and payment history.';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Renewal Center';
    }

    /**
     * @return array<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [
            RenewalEquipmentTableWidget::class,
            RenewalHistoryTableWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    protected static function requiredUserFeature(): UserFeature
    {
        return UserFeature::Wallet;
    }
}
