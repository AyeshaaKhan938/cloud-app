<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PushMethod;
use Database\Factories\PushRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'message_title',
    'push_method',
    'publisher_account',
    'pushed_at',
])]
final class PushRecord extends Model
{
    /** @use HasFactory<PushRecordFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'push_method' => PushMethod::class,
            'pushed_at' => 'datetime',
        ];
    }
}
