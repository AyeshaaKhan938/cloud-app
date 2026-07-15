<?php

declare(strict_types=1);

namespace App\Filament\Admin\Navigation;

use App\Providers\Filament\AdminPanelProvider;

/**
 * Canonical sidebar group names — keep in sync with {@see AdminPanelProvider}.
 */
final class AdminNavigationGroups
{
    public const string Machines = 'Machines';

    public const string Products = 'Products';

    public const string Advertising = 'Advertising';

    public const string Sales = 'Sales';

    public const string Reports = 'Reports & analytics';

    public const string Support = 'Support';

    public const string Account = 'Account';

    public const string Wallet = 'Wallet';

    public const string System = 'System settings';

    public const string PlatformOps = 'Platform operations';

    public const string Brand = 'Brand';

    /**
     * @return list<string>
     */
    public static function ordered(): array
    {
        return [
            self::Machines,
            self::Products,
            self::Advertising,
            self::Sales,
            self::Reports,
            self::Support,
            self::Account,
            self::Wallet,
            self::System,
            self::PlatformOps,
            self::Brand,
        ];
    }
}
