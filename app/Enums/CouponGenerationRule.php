<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CouponGenerationRule: string implements HasLabel
{
    case Numbers = 'numbers';

    case Letter = 'letter';

    case LettersAndNumbers = 'letters_and_numbers';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Numbers => 'Numbers',
            self::Letter => 'Letter',
            self::LettersAndNumbers => 'Letters and numbers',
        };
    }
}
