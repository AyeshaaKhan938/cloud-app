<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FinanceGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'finance_user_id',
    'remarks',
])]
final class FinanceGroup extends Model
{
    /** @use HasFactory<FinanceGroupFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function financeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finance_user_id');
    }

    /**
     * @return HasMany<Machine, $this>
     */
    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }
}
