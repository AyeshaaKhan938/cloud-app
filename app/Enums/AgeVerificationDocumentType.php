<?php

declare(strict_types=1);

namespace App\Enums;

enum AgeVerificationDocumentType: string
{
    case DriversLicense = 'drivers_license';

    case IdCard = 'id_card';

    case Passport = 'passport';

    public function veriffDocumentType(): string
    {
        return match ($this) {
            self::DriversLicense => 'DRIVERS_LICENSE',
            self::IdCard => 'ID_CARD',
            self::Passport => 'PASSPORT',
        };
    }
}
