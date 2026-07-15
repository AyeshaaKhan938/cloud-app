<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Enums\UserFeature;
use App\Enums\UserRegistrationMethod;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\ContactEmailList;
use RuntimeException;

final class SubAccountManager
{
    public function __construct(
        private readonly FeatureAccess $featureAccess,
    ) {}

    public function canManageSubAccounts(?User $actor = null): bool
    {
        $actor ??= auth()->user();

        return $actor instanceof User
            && $actor->isAccountOwner()
            && in_array($actor->role, [UserRole::Customer, UserRole::Agency], true);
    }

    public function canManageSubAccount(User $actor, User $target): bool
    {
        if (! $this->canManageSubAccounts($actor)) {
            return false;
        }

        return $target->parent_user_id === $actor->id
            && $target->role === UserRole::SubAccount;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, User $owner, ?User $record = null): User
    {
        if (! $this->canManageSubAccounts($owner)) {
            abort(403);
        }

        if ($record !== null && ! $this->canManageSubAccount($owner, $record)) {
            abort(403);
        }

        $featureValues = $data['feature_permissions'] ?? [];
        unset($data['feature_permissions'], $data['password_confirmation']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $raw = trim((string) ($data['contact_emails'] ?? ''));
        $data['contact_emails'] = $raw;
        $first = ContactEmailList::firstValidEmail($raw);

        if ($first !== null) {
            $data['email'] = $first;
        }

        $data['role'] = UserRole::SubAccount;
        $data['parent_user_id'] = $owner->id;

        if ($record === null) {
            $data['created_by'] = $owner->id;
            $data['registration_method'] = UserRegistrationMethod::Admin;
            $record = User::query()->create($data);
        } else {
            $record->update($data);
            $record->refresh();
        }

        $this->featureAccess->syncFeatures($record, is_array($featureValues) ? $featureValues : []);

        return $record;
    }

    public function assertCanAssignFeatures(User $owner, array $featureValues): void
    {
        foreach ($featureValues as $featureValue) {
            $feature = UserFeature::tryFrom((string) $featureValue);

            if ($feature === null) {
                throw new RuntimeException('Invalid feature permission.');
            }
        }
    }
}
