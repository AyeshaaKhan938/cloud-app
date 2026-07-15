<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InformationStorageCollectionMethod;
use App\Enums\InformationStorageRuleType;
use Database\Factories\InformationStorageRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'collection_method',
    'ic_card_number',
    'user_name',
    'account',
    'mobile_number',
    'email',
    'promotion_plan',
    'rule_type',
    'points',
    'available_times_in_cycle',
    'used_times_in_cycle',
    'remarks',
])]
final class InformationStorageRecord extends Model
{
    /** @use HasFactory<InformationStorageRecordFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'collection_method' => InformationStorageCollectionMethod::class,
            'rule_type' => InformationStorageRuleType::class,
            'points' => 'decimal:2',
        ];
    }
}
