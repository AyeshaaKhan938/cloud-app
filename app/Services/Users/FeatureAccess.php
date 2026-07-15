<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Enums\UserFeature;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserFeaturePermission;

final class FeatureAccess
{
    public function can(User $user, UserFeature $feature): bool
    {
        if ($user->role->hasFullCloudAccess()) {
            return true;
        }

        if ($user->isAccountOwner()) {
            return true;
        }

        if ($user->role !== UserRole::SubAccount) {
            return true;
        }

        return UserFeaturePermission::query()
            ->where('user_id', $user->id)
            ->where('feature', $feature->value)
            ->exists();
    }

    public function allowsNavigation(UserFeature $feature): bool
    {
        $user = auth()->user();

        return $user instanceof User && $this->can($user, $feature);
    }

    public function allowsAnyNavigation(UserFeature ...$features): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $this->allowsAny($user, ...$features);
    }

    public function allowsAny(User $user, UserFeature ...$features): bool
    {
        foreach ($features as $feature) {
            if ($this->can($user, $feature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<UserFeature>
     */
    public function enabledFeatures(User $user): array
    {
        if ($user->role->hasFullCloudAccess() || $user->isAccountOwner()) {
            return UserFeature::cases();
        }

        return UserFeaturePermission::query()
            ->where('user_id', $user->id)
            ->pluck('feature')
            ->map(fn (mixed $feature): UserFeature => $feature instanceof UserFeature ? $feature : UserFeature::from((string) $feature))
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $featureValues
     */
    public function syncFeatures(User $user, array $featureValues): void
    {
        UserFeaturePermission::query()
            ->where('user_id', $user->id)
            ->delete();

        foreach ($featureValues as $featureValue) {
            $feature = UserFeature::tryFrom((string) $featureValue);

            if ($feature === null) {
                continue;
            }

            UserFeaturePermission::query()->create([
                'user_id' => $user->id,
                'feature' => $feature,
            ]);
        }
    }

    public function featureSummary(User $user): string
    {
        if ($user->role->hasFullCloudAccess() || $user->isAccountOwner()) {
            return 'All features';
        }

        $features = $this->enabledFeatures($user);

        if ($features === []) {
            return 'No features assigned';
        }

        return collect($features)
            ->map(fn (UserFeature $feature): string => $feature->getLabel() ?? $feature->value)
            ->implode(', ');
    }
}
