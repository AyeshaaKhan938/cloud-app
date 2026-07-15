<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AdvertisementGroupSlot: string implements HasLabel
{
    case Screensaver = 'screensaver';

    case Top = 'top';

    case ExternalScreen = 'external_screen';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Screensaver => 'Screensaver slot',
            self::Top => 'Top slot',
            self::ExternalScreen => 'External screen',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function formFieldKeys(): array
    {
        return [
            self::Screensaver->value => 'screensaver_advertisement_ids',
            self::Top->value => 'top_advertisement_ids',
            self::ExternalScreen->value => 'external_screen_advertisement_ids',
        ];
    }

    public function formFieldKey(): string
    {
        return self::formFieldKeys()[$this->value];
    }

    public function typeFilterFieldKey(): string
    {
        return match ($this) {
            self::Screensaver => 'screensaver_type_filter',
            self::Top => 'top_type_filter',
            self::ExternalScreen => 'external_screen_type_filter',
        };
    }
}
