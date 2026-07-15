<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdvertisementType;
use Database\Factories\AdvertisementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'title',
    'type',
    'media_path',
    'link_url',
    'advertiser_name',
    'cost',
    'remarks',
])]
final class Advertisement extends Model
{
    /** @use HasFactory<AdvertisementFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AdvertisementType::class,
            'cost' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsToMany<AdvertisementGroup, $this>
     */
    public function advertisementGroups(): BelongsToMany
    {
        return $this->belongsToMany(AdvertisementGroup::class, 'advertisement_group_advertisement')
            ->withPivot(['slot', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<AdvertisementTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(AdvertisementTag::class);
    }
}
