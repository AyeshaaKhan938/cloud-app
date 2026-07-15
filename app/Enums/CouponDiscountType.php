<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CouponDiscountType: string implements HasLabel
{
    case FixedAmount = 'fixed_amount';

    case Percentage = 'percentage';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FixedAmount => 'Fixed amount',
            self::Percentage => 'Discount percentage',
        };
    }
}
