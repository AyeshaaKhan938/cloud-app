<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MachineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'machine_number',
    'machine_name',
    'machine_group_id',
    'finance_group_id',
    'advertisement_group_id',
    'machine_scenario',
    'service_hot_line',
    'detailed_address',
    'is_enabled',
    'age_verification_enabled',
    'minimum_age',
    'last_seen_at',
    'remarks',
    'latitude',
    'longitude',
])]
final class Machine extends Model
{
    /** @use HasFactory<MachineFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'age_verification_enabled' => 'boolean',
            'minimum_age' => 'integer',
            'last_seen_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<MachineGroup, $this> */
    public function machineGroup(): BelongsTo
    {
        return $this->belongsTo(MachineGroup::class);
    }

    /** @return BelongsTo<FinanceGroup, $this> */
    public function financeGroup(): BelongsTo
    {
        return $this->belongsTo(FinanceGroup::class);
    }

    /**
     * Grupo de anuncios asignado directamente a esta máquina (override individual).
     *
     * @return BelongsTo<AdvertisementGroup, $this>
     */
    public function advertisementGroup(): BelongsTo
    {
        return $this->belongsTo(AdvertisementGroup::class);
    }

    /** @return BelongsToMany<MachineLabelGroup, $this> */
    public function labelGroups(): BelongsToMany
    {
        return $this->belongsToMany(MachineLabelGroup::class, 'machine_label_group_machine');
    }

    /** @return HasMany<MachineAlarm, $this> */
    public function alarms(): HasMany
    {
        return $this->hasMany(MachineAlarm::class);
    }

    /**
     * Slots físicos de la máquina con su inventario y producto asignado.
     *
     * @return HasMany<MachineSlot, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(MachineSlot::class)->orderBy('line_number');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subMinutes(15));
    }

    public function touchLastSeen(): void
    {
        $this->timestamps = false;
        $this->update(['last_seen_at' => now()]);
        $this->timestamps = true;
    }

    /**
     * Resuelve el AdvertisementGroup efectivo para esta máquina.
     *
     * Prioridad:
     *   1. Grupo asignado directamente a la máquina (override individual)
     *   2. Grupo asignado al MachineGroup al que pertenece
     *   3. null → sin anuncios
     */
    public function resolveAdvertisementGroup(): ?AdvertisementGroup
    {
        if ($this->advertisement_group_id) {
            return $this->advertisementGroup;
        }

        $this->loadMissing('machineGroup.advertisementGroup');

        return $this->machineGroup?->advertisementGroup;
    }
}
