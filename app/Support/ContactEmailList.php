<?php

declare(strict_types=1);

namespace App\Support;

final class ContactEmailList
{
    /**
     * @return list<string>
     */
    public static function parseAddresses(string $value): array
    {
        $parts = array_map('trim', explode(',', $value));

        $out = [];
        foreach ($parts as $part) {
            if ($part !== '') {
                $out[] = $part;
            }
        }

        return $out;
    }

    public static function firstValidEmail(string $value): ?string
    {
        foreach (self::parseAddresses($value) as $address) {
            if (filter_var($address, FILTER_VALIDATE_EMAIL) !== false) {
                return $address;
            }
        }

        return null;
    }
}
