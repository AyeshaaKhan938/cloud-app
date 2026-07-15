<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\CouponCode;
use Filament\Support\Contracts\HasLabel;

enum CouponDistributionRule: string implements HasLabel
{
    case CouponCode = 'coupon_code';

    case QrCode = 'qr_code';

    case CouponCodeAndQr = 'coupon_code_and_qr';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CouponCode => 'Coupon Code',
            self::QrCode => 'QR Code',
            self::CouponCodeAndQr => 'Coupon Code + QR Code',
        };
    }

    /**
     * Whether this coupon should have generated rows in {@see CouponCode}
     * (list + optional QR in admin).
     */
    public function requiresGeneratedCouponCodes(): bool
    {
        return match ($this) {
            self::CouponCode, self::QrCode, self::CouponCodeAndQr => true,
        };
    }
}
