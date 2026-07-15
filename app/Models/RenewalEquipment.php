<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RenewalEquipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'device_name',
    'equipment_number',
    'expires_at',
    'yearly_renewal_amount',
])]
final class RenewalEquipment extends Model
{
    /** @use HasFactory<RenewalEquipmentFactory> */
    use HasFactory;

    protected $table = 'renewal_equipment';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'yearly_renewal_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
