<?php

declare(strict_types=1);

namespace App\Filament\Admin\Navigation;

use App\Filament\Admin\Pages\BrandSettings;
use App\Filament\Admin\Pages\BusinessAnalytics;
use App\Filament\Admin\Pages\CloudDashboard;
use App\Filament\Admin\Pages\CollectionAccountConfig;
use App\Filament\Admin\Pages\DataDashboard;
use App\Filament\Admin\Pages\DateIncome;
use App\Filament\Admin\Pages\DeviceIncome;
use App\Filament\Admin\Pages\MachineMap;
use App\Filament\Admin\Pages\NotificationConfiguration;
use App\Filament\Admin\Pages\ProductIncome;
use App\Filament\Admin\Pages\RechargeWallet;
use App\Filament\Admin\Pages\RefundRecords;
use App\Filament\Admin\Pages\RenewalCenter;
use App\Filament\Admin\Pages\ReportStatistics;
use App\Filament\Admin\Pages\SupportAvailability;
use App\Filament\Admin\Pages\UserIncome;
use App\Filament\Admin\Resources\AdvertisementGroups\AdvertisementGroupResource;
use App\Filament\Admin\Resources\Advertisements\AdvertisementResource;
use App\Filament\Admin\Resources\AdvertisementTags\AdvertisementTagResource;
use App\Filament\Admin\Resources\Coupons\CouponResource;
use App\Filament\Admin\Resources\FinanceGroups\FinanceGroupResource;
use App\Filament\Admin\Resources\InformationStorageRecords\InformationStorageRecordResource;
use App\Filament\Admin\Resources\MachineAlarms\MachineAlarmResource;
use App\Filament\Admin\Resources\MachineGroups\MachineGroupResource;
use App\Filament\Admin\Resources\MachineLabelGroups\MachineLabelGroupResource;
use App\Filament\Admin\Resources\Machines\MachineResource;
use App\Filament\Admin\Resources\MyWorkOrders\MyWorkOrderResource;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\Products\ProductLotteryResource;
use App\Filament\Admin\Resources\Products\ProductResource;
use App\Filament\Admin\Resources\ProductTags\ProductTagResource;
use App\Filament\Admin\Resources\ProductTypes\ProductTypeResource;
use App\Filament\Admin\Resources\PushRecords\PushRecordResource;
use App\Filament\Admin\Resources\RechargeRecords\RechargeRecordResource;
use App\Filament\Admin\Resources\Specifications\SpecificationResource;
use App\Filament\Admin\Resources\TeamMembers\TeamMemberResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Resources\WorkOrders\WorkOrderResource;
use Filament\GlobalSearch\GlobalSearchResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class AdminNavigationSearch
{
    /**
     * @return Collection<int, GlobalSearchResult>
     */
    public static function resultsFor(string $query): Collection
    {
        $terms = self::terms($query);

        if ($terms === []) {
            return collect();
        }

        return collect(self::entries())
            ->filter(fn (array $entry): bool => ($entry['visible'])())
            ->filter(fn (array $entry): bool => self::matches($entry, $terms))
            ->map(fn (array $entry): GlobalSearchResult => new GlobalSearchResult(
                title: $entry['label'],
                url: ($entry['url'])(),
                details: [
                    'Section' => $entry['group'],
                ],
            ))
            ->values();
    }

    /**
     * @return list<string>
     */
    private static function terms(string $query): array
    {
        return array_values(array_filter(
            preg_split('/\s+/u', Str::lower(trim($query))) ?: [],
            fn (string $term): bool => $term !== '',
        ));
    }

    /**
     * @param  array{label: string, group: string, keywords: list<string>, url: callable(): string, visible: callable(): bool}  $entry
     * @param  list<string>  $terms
     */
    private static function matches(array $entry, array $terms): bool
    {
        $haystack = Str::lower(implode(' ', [
            $entry['label'],
            $entry['group'],
            ...$entry['keywords'],
        ]));

        foreach ($terms as $term) {
            if (! Str::contains($haystack, $term)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<array{label: string, group: string, keywords: list<string>, url: callable(): string, visible: callable(): bool}>
     */
    private static function entries(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'group' => 'Home',
                'keywords' => ['home', 'overview', 'start'],
                'url' => fn (): string => CloudDashboard::getUrl(),
                'visible' => fn (): bool => auth()->check(),
            ],
            [
                'label' => 'All machines',
                'group' => AdminNavigationGroups::Machines,
                'keywords' => ['machine', 'kiosk', 'device', 'fleet', 'slots', 'inventory'],
                'url' => fn (): string => MachineResource::getUrl(),
                'visible' => fn (): bool => MachineResource::canViewAny(),
            ],
            [
                'label' => 'Machine groups',
                'group' => AdminNavigationGroups::Machines,
                'keywords' => ['machine', 'group', 'route', 'location'],
                'url' => fn (): string => MachineGroupResource::getUrl(),
                'visible' => fn (): bool => MachineGroupResource::canViewAny(),
            ],
            [
                'label' => 'Finance groups',
                'group' => AdminNavigationGroups::Machines,
                'keywords' => ['finance', 'billing', 'revenue', 'group'],
                'url' => fn (): string => FinanceGroupResource::getUrl(),
                'visible' => fn (): bool => FinanceGroupResource::canViewAny(),
            ],
            [
                'label' => 'Label groups',
                'group' => AdminNavigationGroups::Machines,
                'keywords' => ['label', 'tag', 'machine', 'filter'],
                'url' => fn (): string => MachineLabelGroupResource::getUrl(),
                'visible' => fn (): bool => MachineLabelGroupResource::canViewAny(),
            ],
            [
                'label' => 'Alarms',
                'group' => AdminNavigationGroups::Machines,
                'keywords' => ['alarm', 'alert', 'fault', 'notification', 'machine'],
                'url' => fn (): string => MachineAlarmResource::getUrl(),
                'visible' => fn (): bool => MachineAlarmResource::canViewAny(),
            ],
            [
                'label' => 'Map view',
                'group' => AdminNavigationGroups::Machines,
                'keywords' => ['map', 'location', 'gps', 'geography', 'machine'],
                'url' => fn (): string => MachineMap::getUrl(),
                'visible' => fn (): bool => MachineMap::canAccess(),
            ],
            [
                'label' => 'All products',
                'group' => AdminNavigationGroups::Products,
                'keywords' => ['product', 'catalog', 'sku', 'inventory', 'item'],
                'url' => fn (): string => ProductResource::getUrl(),
                'visible' => fn (): bool => ProductResource::canViewAny(),
            ],
            [
                'label' => 'Categories',
                'group' => AdminNavigationGroups::Products,
                'keywords' => ['category', 'specification', 'product', 'type'],
                'url' => fn (): string => SpecificationResource::getUrl(),
                'visible' => fn (): bool => SpecificationResource::canViewAny(),
            ],
            [
                'label' => 'Coupons',
                'group' => AdminNavigationGroups::Products,
                'keywords' => ['coupon', 'promo', 'discount', 'code'],
                'url' => fn (): string => CouponResource::getUrl(),
                'visible' => fn (): bool => CouponResource::canViewAny(),
            ],
            [
                'label' => 'Lotteries',
                'group' => AdminNavigationGroups::Products,
                'keywords' => ['lottery', 'draw', 'prize', 'code', 'promotion'],
                'url' => fn (): string => ProductLotteryResource::getUrl(),
                'visible' => fn (): bool => ProductLotteryResource::canViewAny(),
            ],
            [
                'label' => 'Product types',
                'group' => AdminNavigationGroups::Products,
                'keywords' => ['product', 'type', 'category'],
                'url' => fn (): string => ProductTypeResource::getUrl(),
                'visible' => fn (): bool => ProductTypeResource::canViewAny(),
            ],
            [
                'label' => 'Product tags',
                'group' => AdminNavigationGroups::Products,
                'keywords' => ['product', 'tag', 'label'],
                'url' => fn (): string => ProductTagResource::getUrl(),
                'visible' => fn (): bool => ProductTagResource::canViewAny(),
            ],
            [
                'label' => 'Advertisement list',
                'group' => AdminNavigationGroups::Advertising,
                'keywords' => ['advertisement', 'ad', 'media', 'video', 'promo'],
                'url' => fn (): string => AdvertisementResource::getUrl(),
                'visible' => fn (): bool => AdvertisementResource::canViewAny(),
            ],
            [
                'label' => 'Advertisement group',
                'group' => AdminNavigationGroups::Advertising,
                'keywords' => ['advertisement', 'group', 'campaign', 'playlist'],
                'url' => fn (): string => AdvertisementGroupResource::getUrl(),
                'visible' => fn (): bool => AdvertisementGroupResource::canViewAny(),
            ],
            [
                'label' => 'Tag advertisement',
                'group' => AdminNavigationGroups::Advertising,
                'keywords' => ['advertisement', 'tag', 'label'],
                'url' => fn (): string => AdvertisementTagResource::getUrl(),
                'visible' => fn (): bool => AdvertisementTagResource::canViewAny(),
            ],
            [
                'label' => 'Order list',
                'group' => AdminNavigationGroups::Sales,
                'keywords' => ['order', 'sale', 'transaction', 'payment', 'revenue'],
                'url' => fn (): string => OrderResource::getUrl(),
                'visible' => fn (): bool => OrderResource::canViewAny(),
            ],
            [
                'label' => 'Refund Records',
                'group' => AdminNavigationGroups::Sales,
                'keywords' => ['refund', 'return', 'order', 'sale'],
                'url' => fn (): string => RefundRecords::getUrl(),
                'visible' => fn (): bool => RefundRecords::canAccess(),
            ],
            [
                'label' => 'Sales & profit',
                'group' => AdminNavigationGroups::Reports,
                'keywords' => ['sales', 'profit', 'analytics', 'revenue', 'margin', 'report'],
                'url' => fn (): string => BusinessAnalytics::getUrl(),
                'visible' => fn (): bool => BusinessAnalytics::canAccess(),
            ],
            [
                'label' => 'Overview dashboard',
                'group' => AdminNavigationGroups::Reports,
                'keywords' => ['dashboard', 'chart', 'trend', 'analytics', 'report'],
                'url' => fn (): string => DataDashboard::getUrl(),
                'visible' => fn (): bool => DataDashboard::canAccess(),
            ],
            [
                'label' => 'Revenue by machine',
                'group' => AdminNavigationGroups::Reports,
                'keywords' => ['revenue', 'machine', 'device', 'income', 'report'],
                'url' => fn (): string => DeviceIncome::getUrl(),
                'visible' => fn (): bool => DeviceIncome::canAccess(),
            ],
            [
                'label' => 'Revenue by product',
                'group' => AdminNavigationGroups::Reports,
                'keywords' => ['revenue', 'product', 'income', 'report'],
                'url' => fn (): string => ProductIncome::getUrl(),
                'visible' => fn (): bool => ProductIncome::canAccess(),
            ],
            [
                'label' => 'Revenue by account',
                'group' => AdminNavigationGroups::Reports,
                'keywords' => ['revenue', 'user', 'account', 'customer', 'income', 'report'],
                'url' => fn (): string => UserIncome::getUrl(),
                'visible' => fn (): bool => UserIncome::canAccess(),
            ],
            [
                'label' => 'Revenue by date',
                'group' => AdminNavigationGroups::Reports,
                'keywords' => ['revenue', 'date', 'daily', 'calendar', 'report'],
                'url' => fn (): string => DateIncome::getUrl(),
                'visible' => fn (): bool => DateIncome::canAccess(),
            ],
            [
                'label' => 'Sales statistics',
                'group' => AdminNavigationGroups::Reports,
                'keywords' => ['statistics', 'stats', 'kpi', 'sales', 'report'],
                'url' => fn (): string => ReportStatistics::getUrl(),
                'visible' => fn (): bool => ReportStatistics::canAccess(),
            ],
            [
                'label' => 'My support tickets',
                'group' => AdminNavigationGroups::Support,
                'keywords' => ['support', 'ticket', 'help', 'issue', 'work order'],
                'url' => fn (): string => MyWorkOrderResource::getUrl(),
                'visible' => fn (): bool => MyWorkOrderResource::canViewAny(),
            ],
            [
                'label' => 'Support queue',
                'group' => AdminNavigationGroups::Support,
                'keywords' => ['support', 'queue', 'ticket', 'admin', 'help', 'urgent'],
                'url' => fn (): string => WorkOrderResource::getUrl(),
                'visible' => fn (): bool => WorkOrderResource::canViewAny(),
            ],
            [
                'label' => 'Live chat availability',
                'group' => AdminNavigationGroups::Support,
                'keywords' => ['live', 'chat', 'support', 'agent', 'online'],
                'url' => fn (): string => SupportAvailability::getUrl(),
                'visible' => fn (): bool => SupportAvailability::shouldRegisterNavigation(),
            ],
            [
                'label' => 'Team members',
                'group' => AdminNavigationGroups::Account,
                'keywords' => ['team', 'sub account', 'user', 'access', 'permission'],
                'url' => fn (): string => TeamMemberResource::getUrl(),
                'visible' => fn (): bool => TeamMemberResource::canViewAny(),
            ],
            [
                'label' => 'Recharge Wallet',
                'group' => AdminNavigationGroups::Wallet,
                'keywords' => ['wallet', 'recharge', 'top up', 'balance', 'payment'],
                'url' => fn (): string => RechargeWallet::getUrl(),
                'visible' => fn (): bool => RechargeWallet::canAccess(),
            ],
            [
                'label' => 'Recharge Record',
                'group' => AdminNavigationGroups::Wallet,
                'keywords' => ['wallet', 'recharge', 'history', 'payment', 'record'],
                'url' => fn (): string => RechargeRecordResource::getUrl(),
                'visible' => fn (): bool => RechargeRecordResource::canViewAny(),
            ],
            [
                'label' => 'Renewal Center',
                'group' => AdminNavigationGroups::Wallet,
                'keywords' => ['renewal', 'subscription', 'wallet', 'machine', 'billing'],
                'url' => fn (): string => RenewalCenter::getUrl(),
                'visible' => fn (): bool => RenewalCenter::canAccess(),
            ],
            [
                'label' => 'Collection account config',
                'group' => AdminNavigationGroups::Wallet,
                'keywords' => ['collection', 'payment', 'gateway', 'wallet', 'account'],
                'url' => fn (): string => CollectionAccountConfig::getUrl(),
                'visible' => fn (): bool => CollectionAccountConfig::canAccess(),
            ],
            [
                'label' => 'Alerts & email settings',
                'group' => AdminNavigationGroups::System,
                'keywords' => ['alert', 'email', 'notification', 'analytics', 'settings'],
                'url' => fn (): string => NotificationConfiguration::getUrl(),
                'visible' => fn (): bool => NotificationConfiguration::canAccess(),
            ],
            [
                'label' => 'Users & access',
                'group' => AdminNavigationGroups::System,
                'keywords' => ['user', 'admin', 'role', 'access', 'account'],
                'url' => fn (): string => UserResource::getUrl(),
                'visible' => fn (): bool => UserResource::canViewAny(),
            ],
            [
                'label' => 'Information storage',
                'group' => AdminNavigationGroups::PlatformOps,
                'keywords' => ['information', 'storage', 'content', 'kiosk'],
                'url' => fn (): string => InformationStorageRecordResource::getUrl(),
                'visible' => fn (): bool => InformationStorageRecordResource::canViewAny(),
            ],
            [
                'label' => 'Push record',
                'group' => AdminNavigationGroups::PlatformOps,
                'keywords' => ['push', 'remote', 'content', 'kiosk', 'deploy'],
                'url' => fn (): string => PushRecordResource::getUrl(),
                'visible' => fn (): bool => PushRecordResource::canViewAny(),
            ],
            [
                'label' => 'Brand & appearance',
                'group' => AdminNavigationGroups::Brand,
                'keywords' => ['brand', 'logo', 'appearance', 'theme', 'kiosk'],
                'url' => fn (): string => BrandSettings::getUrl(),
                'visible' => fn (): bool => BrandSettings::canAccess(),
            ],
        ];
    }
}
