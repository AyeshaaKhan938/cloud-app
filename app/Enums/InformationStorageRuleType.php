<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InformationStorageRuleType: string implements HasLabel
{
    case Points = 'points';

    case Times = 'times';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Points => 'Points',
            self::Times => 'Times',
        };
    }
}
