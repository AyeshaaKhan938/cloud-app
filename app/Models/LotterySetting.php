<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tier_a_name',
    'tier_a_weight',
    'tier_b_name',
    'tier_b_weight',
])]
final class LotterySetting extends Model
{
    protected $table = 'lottery_settings';

    private static ?self $currentCache = null;

    protected function casts(): array
    {
        return [
            'tier_a_weight' => 'integer',
            'tier_b_weight' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::$currentCache ??= self::query()->first() ?? self::query()->create([]);
    }

    public static function forgetCurrentCache(): void
    {
        self::$currentCache = null;
    }
}
