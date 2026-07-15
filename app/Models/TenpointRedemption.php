<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'machine_no',
    'validated_at',
    'tier',
    'line_number',
    'prize_amount',
    'dispense_success',
    'dispensed_at',
    'dispense_error',
])]
final class TenpointRedemption extends Model
{
    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
            'dispensed_at' => 'datetime',
            'dispense_success' => 'boolean',
            'prize_amount' => 'decimal:2',
        ];
    }
}
