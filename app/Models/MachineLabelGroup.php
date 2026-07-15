<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MachineLabelGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name',
])]
final class MachineLabelGroup extends Model
{
    /** @use HasFactory<MachineLabelGroupFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<Machine, $this>
     */
    public function machines(): BelongsToMany
    {
        return $this->belongsToMany(Machine::class, 'machine_label_group_machine');
    }
}
