<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserFeature: string implements HasLabel
{
    case MachinesView = 'machines_view';

    case MachinesCreate = 'machines_create';

    case Products = 'products';

    case MachineSlots = 'machine_slots';

    case Advertising = 'advertising';

    case Sales = 'sales';

    case Reports = 'reports';

    case Wallet = 'wallet';

    case WorkOrders = 'work_orders';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MachinesView => 'View machines',
            self::MachinesCreate => 'Add machines',
            self::Products => 'Manage products',
            self::MachineSlots => 'Assign products to slots',
            self::Advertising => 'Advertising',
            self::Sales => 'Sales & orders',
            self::Reports => 'Reports & analytics',
            self::Wallet => 'Wallet & payments',
            self::WorkOrders => 'Support tickets',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MachinesView => 'See the machine list and open slot inventory.',
            self::MachinesCreate => 'Register new vending machines on the account.',
            self::Products => 'Create and edit products in the catalog.',
            self::MachineSlots => 'Assign products, prices, and stock to machine slots.',
            self::Advertising => 'Manage advertisements and ad groups.',
            self::Sales => 'View orders, refunds, and sales history.',
            self::Reports => 'Open income and business analytics reports.',
            self::Wallet => 'Access wallet balance, recharge, and payment settings.',
            self::WorkOrders => 'Submit support tickets, track status, and request live chat.',
        };
    }

    /**
     * @return list<self>
     */
    public static function assignableToSubAccounts(): array
    {
        return self::cases();
    }
}
