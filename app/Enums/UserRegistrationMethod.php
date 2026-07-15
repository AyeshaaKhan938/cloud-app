<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRegistrationMethod: string implements HasLabel
{
    case Email = 'email';

    case Phone = 'phone';

    case Social = 'social';

    case Admin = 'admin';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Social => 'Social',
            self::Admin => 'Admin created',
        };
    }
}
