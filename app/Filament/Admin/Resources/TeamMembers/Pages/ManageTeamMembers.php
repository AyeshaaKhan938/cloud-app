<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamMembers\Pages;

use App\Filament\Admin\Resources\TeamMembers\TeamMemberResource;
use App\Models\User;
use App\Services\Users\SubAccountManager;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Schema;

final class ManageTeamMembers extends ManageRecords
{
    protected static string $resource = TeamMemberResource::class;

    public function getTitle(): string
    {
        return 'Team members';
    }

    public function getSubheading(): ?string
    {
        return 'Create sub-accounts and control which cloud features each team member can access.';
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof CreateAction, $action instanceof EditAction => fn (Schema $schema): Schema => $this->form($schema->hasCustomColumns() ? $schema : $schema->columns(1)),
            $action instanceof ViewAction => fn (Schema $schema): Schema => $this->infolist($this->form($schema->hasCustomColumns() ? $schema : $schema->columns(1))),
            default => parent::getDefaultActionSchemaResolver($action),
        };
    }

    public static function saveTeamMember(array $data, ?User $record = null): User
    {
        /** @var User $owner */
        $owner = auth()->user();

        return app(SubAccountManager::class)->save($data, $owner, $record);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add team member')
                ->modalHeading('Add team member')
                ->modalSubmitActionLabel('Create')
                ->authorize(fn (): bool => app(SubAccountManager::class)->canManageSubAccounts())
                ->using(fn (array $data): User => self::saveTeamMember($data)),
        ];
    }
}
