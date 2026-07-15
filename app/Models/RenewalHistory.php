<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RenewalPayType;
use App\Enums\RenewalProgress;
use Database\Factories\RenewalHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'user_account',
    'user_name',
    'renewal_account',
    'renewal_number',
    'order_number',
    'amount',
    'renew_equipment',
    'renewal_schedule',
    'renewal_progress',
    'pay_type',
    'application_time',
])]
final class RenewalHistory extends Model
{
    /** @use HasFactory<RenewalHistoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'renewal_progress' => RenewalProgress::class,
            'pay_type' => RenewalPayType::class,
            'application_time' => 'datetime',
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
