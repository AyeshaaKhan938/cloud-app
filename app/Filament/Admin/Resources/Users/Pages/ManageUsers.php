<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Enums\UserRegistrationMethod;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use App\Services\Users\UserAccessManager;
use App\Support\ContactEmailList;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;

final class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Users & access';
    }

    public function getSubheading(): ?string
    {
        return 'Create accounts, assign roles, and control which machines each client can access.';
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof CreateAction, $action instanceof EditAction => fn (Schema $schema): Schema => $this->form($schema->hasCustomColumns() ? $schema : $schema->columns(1)),
            $action instanceof ViewAction => fn (Schema $schema): Schema => $this->infolist($this->form($schema->hasCustomColumns() ? $schema : $schema->columns(1))),
            default => parent::getDefaultActionSchemaResolver($action),
        };
    }

    public static function saveUser(array $data, ?User $record = null): User
    {
        /** @var User $actor */
        $actor = auth()->user();
        $accessManager = app(UserAccessManager::class);

        $machineIds = $data['machine_ids'] ?? [];
        unset($data['machine_ids']);

        $role = $data['role'] instanceof UserRole
            ? $data['role']
            : UserRole::from((string) $data['role']);

        $accessManager->assertCanAssignRole($actor, $role);

        if ($record !== null && ! $accessManager->canManageTarget($actor, $record)) {
            abort(403);
        }

        $payload = self::prepareUserFormData($data, $record === null);

        if ($record === null) {
            $record = User::query()->create($payload);
        } else {
            $record->update($payload);
            $record->refresh();
        }

        $accessManager->syncMachineAccess($record, $machineIds);

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    public static function prepareUserFormData(array $data, bool $isCreate): array
    {
        unset($data['password_confirmation'], $data['machine_ids']);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $raw = trim((string) ($data['contact_emails'] ?? ''));
        $data['contact_emails'] = $raw;
        $first = ContactEmailList::firstValidEmail($raw);
        if ($first !== null) {
            $data['email'] = $first;
        }

        if ($isCreate) {
            $data['created_by'] = auth()->id();
            $data['registration_method'] = UserRegistrationMethod::Admin;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add user')
                ->modalHeading('Add user')
                ->modalSubmitActionLabel('Create')
                ->authorize(fn (): bool => auth()->user()?->can('create', User::class) ?? false)
                ->using(fn (array $data): User => self::saveUser($data)),
        ];
    }
}
