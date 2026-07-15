<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'machine_no',
    'product_lottery_code_id',
    'machine_slot_id',
    'product_name',
    'line_number',
    'prize_name',
    'prize_amount',
    'payment_method',
    'payment_reference',
    'status',
    'notes',
    'completed_at',
])]
final class Order extends Model
{
    protected function casts(): array
    {
        return [
            'prize_amount' => 'decimal:2',
            'line_number' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    /**
     * @return BelongsTo<ProductLotteryCode, $this>
     */
    public function lotteryCode(): BelongsTo
    {
        return $this->belongsTo(ProductLotteryCode::class, 'product_lottery_code_id');
    }

    /**
     * @return BelongsTo<MachineSlot, $this>
     */
    public function machineSlot(): BelongsTo
    {
        return $this->belongsTo(MachineSlot::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeForMachine(Builder $query, string $machineNo): Builder
    {
        return $query->where('machine_no', $machineNo);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
