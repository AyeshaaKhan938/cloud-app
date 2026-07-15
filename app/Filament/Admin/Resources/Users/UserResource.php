<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Admin\Concerns\EnrichesGlobalSearch;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Filament\Admin\Resources\Users\Pages\ManageUsers;
use App\Filament\Admin\Support\AccessibleTable;
use App\Models\Machine;
use App\Models\User;
use App\Rules\ContactEmailsUniqueFirst;
use App\Services\Users\UserAccessManager;
use App\Support\CountrySelectOptions;
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
use Filament\Schemas\Components\Utilities\Get;
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

final class UserResource extends Resource
{
    use EnrichesGlobalSearch;

    protected static ?string $model = User::class;

    protected static ?string $slug = 'system-users';

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::System;

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Users & access';

    protected static ?string $modelLabel = 'user';

    protected static ?string $pluralModelLabel = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with('creator')
            ->withCount('machines');

        $actor = auth()->user();

        if ($actor instanceof User && $actor->role === UserRole::SuperAdmin) {
            return $query;
        }

        return $query->whereRaw('0 = 1');
    }

    public static function form(Schema $schema): Schema
    {
        $identifiers = DateTimeZone::listIdentifiers();
        $timezoneOptions = array_combine($identifiers, $identifiers) ?: [];
        $actor = auth()->user();
        $assignableRoles = $actor instanceof User
            ? $actor->role->assignableRoles()
            : [];

        return $schema
            ->components([
                Section::make('Account')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('account')
                                    ->label('User account')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('name')
                                    ->label('User name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->label('Phone number')
                                    ->required()
                                    ->tel()
                                    ->maxLength(50),
                                TextInput::make('password')
                                    ->label('Account password')
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
                                    ->label('User email')
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
                                Select::make('country')
                                    ->label('Country')
                                    ->options(CountrySelectOptions::all())
                                    ->native(false)
                                    ->searchable(),
                                TextInput::make('region')
                                    ->label('Region')
                                    ->maxLength(100),
                            ]),
                    ]),
                Section::make('Cloud access')
                    ->description('Role controls what this user can see in the admin panel. Client roles only see machines bound below.')
                    ->schema([
                        Select::make('role')
                            ->label('User role')
                            ->options(
                                collect($assignableRoles)
                                    ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->getLabel()])
                                    ->all()
                            )
                            ->native(false)
                            ->searchable()
                            ->required()
                            ->live()
                            ->helperText(fn (Get $get): ?string => UserRole::tryFrom((string) $get('role'))?->description()),
                        CheckboxList::make('machine_ids')
                            ->label('Bound machines')
                            ->helperText('Machines this user can manage, sell from, and view in reports.')
                            ->options(
                                fn (): array => Machine::query()
                                    ->orderBy('machine_name')
                                    ->get()
                                    ->mapWithKeys(fn (Machine $machine): array => [
                                        $machine->id => $machine->machine_name.' ('.$machine->machine_number.')',
                                    ])
                                    ->all()
                            )
                            ->columns(2)
                            ->visible(fn (Get $get): bool => UserRole::tryFrom((string) $get('role'))?->requiresMachineScoping() ?? false)
                            ->dehydrated(true)
                            ->afterStateHydrated(function (CheckboxList $component, ?User $record): void {
                                if ($record === null) {
                                    return;
                                }

                                $component->state(app(UserAccessManager::class)->machineIdsForUser($record));
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account')
                    ->label('User account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('User name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?UserRole $state): string => $state?->getLabel() ?? '—'),
                TextColumn::make('cloud_access')
                    ->label('Cloud access')
                    ->state(fn (User $record): string => app(UserAccessManager::class)->cloudAccessSummary($record))
                    ->wrap(),
                TextColumn::make('machines_count')
                    ->label('Machines')
                    ->sortable()
                    ->alignCenter(),
                IconColumn::make('is_enabled')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('contact_emails')
                    ->label('Email')
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => filled($state) ? $state : null)
                    ->formatStateUsing(function (?string $state, Model $record): string {
                        if (filled($state)) {
                            return $state;
                        }

                        /** @var User $record */
                        return $record->email;
                    }),
                TextColumn::make('creator.name')
                    ->label('Created by')
                    ->toggleable()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? $state : '—'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(UserRole::class),
                Filter::make('account')
                    ->label('User account')
                    ->form([
                        TextInput::make('value')
                            ->label('User account')
                            ->placeholder('Search account…'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('account', 'like', '%'.$data['value'].'%')
                        );
                    }),
                Filter::make('name')
                    ->label('Name')
                    ->form([
                        TextInput::make('value')->label('Name')->placeholder('Search name…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $q): Builder => $q->where('name', 'like', '%'.$data['value'].'%'),
                    )),
                Filter::make('email')
                    ->label('Email')
                    ->form([
                        TextInput::make('value')->label('Email')->placeholder('Search email…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $q): Builder => $q->where('email', 'like', '%'.$data['value'].'%'),
                    )),
            ])
            ->deferFilters(false)
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit user access')
                    ->modalSubmitActionLabel('Save')
                    ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->using(fn (array $data, User $record): User => ManageUsers::saveUser($data, $record)),
                DeleteAction::make()
                    ->authorize(fn (User $record): bool => auth()->user()?->can('delete', $record) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords(),
                ]),
            ])
            ->emptyStateHeading('No users yet');

        return AccessibleTable::apply($table, 'Search users by name, account, or email…');
    }

    /**
     * @return list<string>
     */
    protected static function globalSearchAttributes(): array
    {
        return ['name', 'account', 'email', 'phone'];
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
            'Role' => $record->role->getLabel(),
            'Email' => $record->email,
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('viewAny', User::class) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', User::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', User::class) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
