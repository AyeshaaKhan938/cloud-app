<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AdvertisementTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
final class AdvertisementTag extends Model
{
    /** @use HasFactory<AdvertisementTagFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<Advertisement, $this>
     */
    public function advertisements(): BelongsToMany
    {
        return $this->belongsToMany(Advertisement::class);
    }
}
