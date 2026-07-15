<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRegistrationMethod;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'account',
    'name',
    'email',
    'password',
    'phone',
    'timezone',
    'role',
    'is_enabled',
    'country',
    'region',
    'contact_emails',
    'registration_method',
    'client_version',
    'login_address',
    'created_by',
    'parent_user_id',
    'wallet_balance',
    'wallet_excess_amount',
    'wallet_recharge_pending',
    'wallet_accumulated_recharge',
    'wallet_withdrawal_pending',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_enabled' => 'boolean',
            'role' => UserRole::class,
            'registration_method' => UserRegistrationMethod::class,
            'wallet_balance' => 'decimal:2',
            'wallet_excess_amount' => 'decimal:2',
            'wallet_recharge_pending' => 'decimal:2',
            'wallet_accumulated_recharge' => 'decimal:2',
            'wallet_withdrawal_pending' => 'decimal:2',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        return $this->is_enabled;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_user_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function subAccounts(): HasMany
    {
        return $this->hasMany(self::class, 'parent_user_id');
    }

    /**
     * @return HasMany<UserFeaturePermission, $this>
     */
    public function featurePermissions(): HasMany
    {
        return $this->hasMany(UserFeaturePermission::class);
    }

    /** @return HasMany<Machine, $this> */
    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class);
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function hasFullCloudAccess(): bool
    {
        return $this->role->hasFullCloudAccess();
    }

    /** @return HasMany<WorkOrder, $this> */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /** @return HasMany<WorkOrder, $this> */
    public function assignedWorkOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'assigned_to_user_id');
    }

    /** @return HasOne<SupportAgentPresence, $this> */
    public function supportAgentPresence(): HasOne
    {
        return $this->hasOne(SupportAgentPresence::class);
    }

    public function canManageUsers(): bool
    {
        return $this->role->canManageUsers();
    }

    public function isAccountOwner(): bool
    {
        return $this->parent_user_id === null && $this->role !== UserRole::SubAccount;
    }

    public function isSubAccount(): bool
    {
        return $this->role === UserRole::SubAccount;
    }

    public function accountOwner(): self
    {
        $user = $this;

        while ($user->parent_user_id !== null) {
            $user->loadMissing('parent');
            $parent = $user->parent;

            if ($parent === null) {
                break;
            }

            $user = $parent;
        }

        return $user;
    }
}
