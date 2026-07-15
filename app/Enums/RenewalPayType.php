<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RenewalPayType: string implements HasLabel
{
    case Paypal = 'paypal';

    case Stripe = 'stripe';

    case Offline = 'offline';

    case Balance = 'balance';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Paypal => 'PAYPAL',
            self::Stripe => 'STRIPE',
            self::Offline => 'OFFLINE',
            self::Balance => 'BALANCE',
        };
    }
}
