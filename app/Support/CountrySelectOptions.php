<?php

declare(strict_types=1);

namespace App\Support;

final class CountrySelectOptions
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'MX' => 'Mexico',
            'BR' => 'Brazil',
            'AR' => 'Argentina',
            'CO' => 'Colombia',
            'CL' => 'Chile',
            'PE' => 'Peru',
            'ES' => 'Spain',
            'FR' => 'France',
            'DE' => 'Germany',
            'IT' => 'Italy',
            'GB' => 'United Kingdom',
            'PT' => 'Portugal',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'CH' => 'Switzerland',
            'AT' => 'Austria',
            'PL' => 'Poland',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'IE' => 'Ireland',
            'CN' => 'China',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'IN' => 'India',
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
            'ZA' => 'South Africa',
            'EG' => 'Egypt',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
        ];
    }
}
