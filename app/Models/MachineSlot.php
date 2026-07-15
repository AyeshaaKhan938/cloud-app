<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'machine_id',
    'line_number',
    'product_id',
    'price',
    'max_stock',
    'current_stock',
    'stock_alarm_threshold',
    'is_active',
    'is_fault',
])]
final class MachineSlot extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_fault' => 'boolean',
        ];
    }

    // ── Relaciones ────────────────────────────────────────────────────────

    /** @return BelongsTo<Machine, $this> */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isAvailable(): bool
    {
        return $this->is_active
            && ! $this->is_fault
            && $this->current_stock > 0
            && $this->product_id !== null;
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->stock_alarm_threshold;
    }
}
