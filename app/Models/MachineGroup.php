<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MachineGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'operation_and_maintenance_user_id',
    'advertisement_group_id',
])]
final class MachineGroup extends Model
{
    /** @use HasFactory<MachineGroupFactory> */
    use HasFactory;

    // ── Relationships ────────────────────────────────────────────────────────

    /** @return BelongsTo<User, $this> */
    public function operationAndMaintenanceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operation_and_maintenance_user_id');
    }

    /**
     * Grupo de anuncios por defecto para todas las máquinas de este grupo.
     * Cada máquina puede sobreescribirlo con su propio advertisement_group_id.
     *
     * @return BelongsTo<AdvertisementGroup, $this>
     */
    public function advertisementGroup(): BelongsTo
    {
        return $this->belongsTo(AdvertisementGroup::class);
    }

    /** @return HasMany<Machine, $this> */
    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }
}
