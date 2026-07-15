<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\PaymentGatewayFormBuilder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'gateway_slug',
    'payload',
])]
final class PaymentCollectionConfig extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
        ];
    }

    public static function isConfigured(string $gatewaySlug): bool
    {
        $row = self::query()->where('gateway_slug', $gatewaySlug)->first();
        if ($row === null) {
            return false;
        }

        /** @var array<string, mixed>|null $payload */
        $payload = is_array($row->payload) ? $row->payload : null;

        return PaymentGatewayFormBuilder::payloadIsComplete($gatewaySlug, $payload);
    }
}
