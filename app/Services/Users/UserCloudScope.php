<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Models\Machine;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductLottery;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Dedicated cloud access: client users only see machines, products, lotteries,
 * and sales tied to machines bound to their account (machines.user_id).
 * Sub-accounts inherit the parent account owner's machines and catalog scope.
 */
final class UserCloudScope
{
    public function actor(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    public function accountOwner(?User $user = null): User
    {
        $user ??= $this->actor();

        if ($user === null) {
            throw new \RuntimeException('No authenticated user.');
        }

        return $user->accountOwner();
    }

    public function hasFullCloudAccess(?User $user = null): bool
    {
        $user ??= $this->actor();

        if ($user === null) {
            return false;
        }

        return $user->role->hasFullCloudAccess();
    }

    public function requiresScoping(?User $user = null): bool
    {
        return ! $this->hasFullCloudAccess($user);
    }

    /**
     * @return list<int>
     */
    public function ownedMachineIds(?User $user = null): array
    {
        $owner = $this->resolveScopeUser($user);

        if ($owner === null) {
            return [];
        }

        return Machine::query()
            ->where('user_id', $owner->id)
            ->pluck('id')
            ->all();
    }

    /**
     * @return list<string>
     */
    public function ownedMachineNumbers(?User $user = null): array
    {
        $owner = $this->resolveScopeUser($user);

        if ($owner === null) {
            return [];
        }

        return Machine::query()
            ->where('user_id', $owner->id)
            ->pluck('machine_number')
            ->all();
    }

    public function ownsMachine(Machine $machine, ?User $user = null): bool
    {
        if ($this->hasFullCloudAccess($user)) {
            return true;
        }

        $owner = $this->resolveScopeUser($user);

        return $owner !== null && $machine->user_id === $owner->id;
    }

    public function canAccessProduct(Product $product, ?User $user = null): bool
    {
        if ($this->hasFullCloudAccess($user)) {
            return true;
        }

        $owner = $this->resolveScopeUser($user);

        if ($owner === null) {
            return false;
        }

        if ($product->user_id === $owner->id) {
            return true;
        }

        $machineNumbers = $this->ownedMachineNumbers($user);

        if ($machineNumbers === []) {
            return false;
        }

        return $product->machineSlots()
            ->whereHas('machine', fn (Builder $query): Builder => $query->where('user_id', $owner->id))
            ->exists()
            || $product->productLotteries()
                ->whereIn('machine_no', $machineNumbers)
                ->exists();
    }

    public function canAccessLottery(ProductLottery $lottery, ?User $user = null): bool
    {
        if ($this->hasFullCloudAccess($user)) {
            return true;
        }

        $machineNo = $lottery->machine_no;

        if ($machineNo === null || $machineNo === '') {
            return false;
        }

        return in_array($machineNo, $this->ownedMachineNumbers($user), true);
    }

    /**
     * @param  Builder<Machine>  $query
     * @return Builder<Machine>
     */
    public function scopeMachines(Builder $query, ?User $user = null): Builder
    {
        if ($this->hasFullCloudAccess($user)) {
            return $query;
        }

        $owner = $this->resolveScopeUser($user);

        if ($owner === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('user_id', $owner->id);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeProducts(Builder $query, ?User $user = null): Builder
    {
        if ($this->hasFullCloudAccess($user)) {
            return $query;
        }

        $owner = $this->resolveScopeUser($user);

        if ($owner === null) {
            return $query->whereRaw('0 = 1');
        }

        $machineNumbers = $this->ownedMachineNumbers($user);

        return $query->where(function (Builder $scoped) use ($owner, $machineNumbers): void {
            $scoped->where('user_id', $owner->id);

            $scoped->orWhere(function (Builder $linked) use ($owner, $machineNumbers): void {
                $linked->whereHas(
                    'machineSlots.machine',
                    fn (Builder $machineQuery): Builder => $machineQuery->where('user_id', $owner->id),
                );

                if ($machineNumbers !== []) {
                    $linked->orWhereHas(
                        'productLotteries',
                        fn (Builder $lotteryQuery): Builder => $lotteryQuery->whereIn('machine_no', $machineNumbers),
                    );
                }
            });
        });
    }

    /**
     * @param  Builder<ProductLottery>  $query
     * @return Builder<ProductLottery>
     */
    public function scopeProductLotteries(Builder $query, ?User $user = null): Builder
    {
        if ($this->hasFullCloudAccess($user)) {
            return $query;
        }

        $machineNumbers = $this->ownedMachineNumbers($user);

        if ($machineNumbers === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('machine_no', $machineNumbers);
    }

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeOrders(Builder $query, ?User $user = null): Builder
    {
        if ($this->hasFullCloudAccess($user)) {
            return $query;
        }

        $machineNumbers = $this->ownedMachineNumbers($user);

        if ($machineNumbers === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('machine_no', $machineNumbers);
    }

    private function resolveScopeUser(?User $user): ?User
    {
        $user ??= $this->actor();

        if ($user === null) {
            return null;
        }

        if ($this->hasFullCloudAccess($user)) {
            return $user;
        }

        return $user->accountOwner();
    }
}
