<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamMembers;

use App\Enums\UserFeature;
use App\Enums\UserRole;
use App\Filament\Admin\Concerns\EnrichesGlobalSearch;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Filament\Admin\Resources\TeamMembers\Pages\ManageTeamMembers;
use App\Filament\Admin\Support\AccessibleTable;
use App\Models\User;
use App\Rules\ContactEmailsUniqueFirst;
use App\Services\Users\FeatureAccess;
use App\Services\Users\SubAccountManager;
use BackedEnum;
use DateTimeZone;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class TeamMemberResource extends Resource
{
    use EnrichesGlobalSearch;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'team-members';

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::Account;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Team members';

    protected static ?string $modelLabel = 'team member';

    protected static ?string $pluralModelLabel = 'team members';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getEloquentQuery(): Builder
    {
        /** @var User $owner */
        $owner = auth()->user();

        return parent::getEloquentQuery()
            ->where('parent_user_id', $owner->id)
            ->where('role', UserRole::SubAccount);
    }

    public static function form(Schema $schema): Schema
    {
        $identifiers = DateTimeZone::listIdentifiers();
        $timezoneOptions = array_combine($identifiers, $identifiers) ?: [];

        return $schema
            ->components([
                Section::make('Account')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('account')
                                    ->label('Login account')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->label('Phone number')
                                    ->required()
                                    ->tel()
                                    ->maxLength(50),
                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (HasSchemas $livewire, string $operation): bool => $operation === 'create')
                                    ->confirmed()
                                    ->dehydrated(fn (?string $state): bool => filled($state)),
                                TextInput::make('password_confirmation')
                                    ->label('Confirm password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (HasSchemas $livewire, string $operation): bool => $operation === 'create')
                                    ->dehydrated(false),
                                TextInput::make('contact_emails')
                                    ->label('Email')
                                    ->helperText('Multiple email addresses are separated by commas (\',\').')
                                    ->required()
                                    ->maxLength(2000)
                                    ->columnSpanFull()
                                    ->rule(fn (Field $component): ContactEmailsUniqueFirst => new ContactEmailsUniqueFirst($component->getRecord()?->getKey())),
                                Select::make('timezone')
                                    ->label('Time zone')
                                    ->options($timezoneOptions)
                                    ->native(false)
                                    ->searchable()
                                    ->required(),
                                Radio::make('is_enabled')
                                    ->label('Account status')
                                    ->boolean('Enable', 'Disable')
                                    ->inline()
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Feature access')
                    ->description('Choose what this team member can see and do in the cloud panel.')
                    ->schema([
                        CheckboxList::make('feature_permissions')
                            ->label('Allowed features')
                            ->options(
                                collect(UserFeature::assignableToSubAccounts())
                                    ->mapWithKeys(fn (UserFeature $feature): array => [
                                        $feature->value => $feature->getLabel(),
                                    ])
                                    ->all()
                            )
                            ->descriptions(
                                collect(UserFeature::assignableToSubAccounts())
                                    ->mapWithKeys(fn (UserFeature $feature): array => [
                                        $feature->value => $feature->description(),
                                    ])
                                    ->all()
                            )
                            ->columns(2)
                            ->required()
                            ->dehydrated(true)
                            ->afterStateHydrated(function (CheckboxList $component, ?User $record): void {
                                if ($record === null) {
                                    return;
                                }

                                $component->state(
                                    collect(app(FeatureAccess::class)->enabledFeatures($record))
                                        ->map(fn (UserFeature $feature): string => $feature->value)
                                        ->values()
                                        ->all()
                                );
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account')
                    ->label('Login account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('feature_access')
                    ->label('Features')
                    ->state(fn (User $record): string => app(FeatureAccess::class)->featureSummary($record))
                    ->wrap(),
                IconColumn::make('is_enabled')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('contact_emails')
                    ->label('Email')
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => filled($state) ? $state : null),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('account')
                    ->label('Login account')
                    ->form([
                        TextInput::make('value')->label('Login account')->placeholder('Search account…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $q): Builder => $q->where('account', 'like', '%'.$data['value'].'%'),
                    )),
                Filter::make('name')
                    ->label('Name')
                    ->form([
                        TextInput::make('value')->label('Name')->placeholder('Search name…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $q): Builder => $q->where('name', 'like', '%'.$data['value'].'%'),
                    )),
                SelectFilter::make('is_enabled')
                    ->label('Active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit team member')
                    ->modalSubmitActionLabel('Save')
                    ->using(fn (array $data, User $record): User => ManageTeamMembers::saveTeamMember($data, $record)),
                DeleteAction::make()
                    ->authorize(fn (User $record): bool => app(SubAccountManager::class)->canManageSubAccount(auth()->user(), $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(),
                ]),
            ])
            ->emptyStateHeading('No team members yet')
            ->emptyStateDescription('Create sub-accounts and choose which features each person can use.');

        return AccessibleTable::apply($table, 'Search team members by account or name…');
    }

    /**
     * @return list<string>
     */
    protected static function globalSearchAttributes(): array
    {
        return ['name', 'account', 'email'];
    }

    /**
     * @return array<string, string>
     */
    protected static function globalSearchDetails(Model $record): array
    {
        if (! $record instanceof User) {
            return [];
        }

        return array_filter([
            'Account' => $record->account,
            'Features' => app(FeatureAccess::class)->featureSummary($record),
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return app(SubAccountManager::class)->canManageSubAccounts();
    }

    public static function canViewAny(): bool
    {
        return self::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return self::shouldRegisterNavigation();
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTeamMembers::route('/'),
        ];
    }
}
