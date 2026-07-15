<?php

declare(strict_types=1);

namespace App\Support;

/**
 * PayPal-supported currency codes for product checkout display (legacy Library module).
 *
 * USD is the application’s primary currency; other codes remain available where PayPal allows checkout in that currency.
 */
final class PayPalCurrencyOptions
{
    /**
     * @return array<string, string> code => label
     */
    public static function selectOptions(): array
    {
        $codes = [
            'USD', 'AUD', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN',
            'GBP', 'BRL', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'CAD', 'CNY',
            'CZK', 'DKK', 'EUR', 'HKD', 'HUF',
        ];

        return array_combine($codes, $codes) ?: [];
    }
}
