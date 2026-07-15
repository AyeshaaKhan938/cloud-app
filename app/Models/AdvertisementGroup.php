<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdvertisementGroupSlot;
use Database\Factories\AdvertisementGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['name'])]
final class AdvertisementGroup extends Model
{
    /** @use HasFactory<AdvertisementGroupFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<Advertisement, $this>
     */
    public function advertisements(): BelongsToMany
    {
        return $this->belongsToMany(Advertisement::class, 'advertisement_group_advertisement')
            ->withPivot(['slot', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * @return list<int>
     */
    public function advertisementIdsForSlot(AdvertisementGroupSlot $slot): array
    {
        return $this->advertisements()
            ->wherePivot('slot', $slot->value)
            ->pluck('advertisements.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  list<int|string>  $orderedAdvertisementIds
     */
    public function syncAdvertisementsForSlot(AdvertisementGroupSlot $slot, array $orderedAdvertisementIds): void
    {
        $ids = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $orderedAdvertisementIds)));

        DB::table('advertisement_group_advertisement')
            ->where('advertisement_group_id', $this->getKey())
            ->where('slot', $slot->value)
            ->delete();

        foreach ($ids as $order => $advertisementId) {
            $this->advertisements()->attach($advertisementId, [
                'slot' => $slot->value,
                'sort_order' => $order,
            ]);
        }
    }

    /**
     * @param  array<string, list<int|string>>  $slotsBySlotValue  keys: screensaver, top, external_screen
     */
    public function syncAllAdvertisementSlots(array $slotsBySlotValue): void
    {
        foreach (AdvertisementGroupSlot::cases() as $slot) {
            $this->syncAdvertisementsForSlot($slot, $slotsBySlotValue[$slot->value] ?? []);
        }
    }
}
