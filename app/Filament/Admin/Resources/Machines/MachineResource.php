<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Machines;

use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\EnrichesGlobalSearch;
use App\Filament\Admin\Resources\Machines\Pages\ManageMachines;
use App\Filament\Admin\Resources\Machines\Pages\ManageMachineSlots;
use App\Filament\Admin\Resources\Machines\Pages\ViewMachine;
use App\Filament\Admin\Support\AccessibleTable;
use App\Models\Machine;
use App\Services\Filament\InterconnectedEntityService;
use App\Services\Users\FeatureAccess;
use App\Services\Users\UserCloudScope;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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

final class MachineResource extends Resource
{
    use EnrichesGlobalSearch;

    protected static ?string $model = Machine::class;

    protected static ?string $slug = 'machines/list';

    protected static string|UnitEnum|null $navigationGroup = 'Machines';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'All machines';

    protected static ?string $modelLabel = 'machine';

    protected static ?string $pluralModelLabel = 'machines';

    protected static ?string $recordTitleAttribute = 'machine_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user', 'machineGroup', 'financeGroup', 'slots']);

        return app(UserCloudScope::class)->scopeMachines($query);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('machine_number')
                                    ->label('Machine number')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('machine_name')
                                    ->label('Machine name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('user_id')
                                    ->label('Bound user')
                                    ->placeholder('Please choose user')
                                    ->relationship('user', 'account')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->visible(fn (): bool => app(UserCloudScope::class)->hasFullCloudAccess())
                                    ->columnSpanFull(),
                                Select::make('machine_group_id')
                                    ->label('Machine group')
                                    ->relationship('machineGroup', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Select::make('finance_group_id')
                                    ->label('Financial group')
                                    ->relationship('financeGroup', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Select::make('advertisement_group_id')
                                    ->label('Advertisement group')
                                    ->relationship('advertisementGroup', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->placeholder('Inherit from machine group')
                                    ->helperText('Override the machine group\'s ad group for this machine only.')
                                    ->columnSpanFull(),
                                TextInput::make('machine_scenario')
                                    ->label('Scenario')
                                    ->maxLength(255),
                                TextInput::make('service_hot_line')
                                    ->label('Service hot line')
                                    ->maxLength(255),
                                Textarea::make('detailed_address')
                                    ->label('Detailed address')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Toggle::make('is_enabled')
                                    ->label('Enabled')
                                    ->default(true)
                                    ->inline(false),
                                Toggle::make('age_verification_enabled')
                                    ->label('Age verification enabled')
                                    ->helperText('Require ID scanner verification for age-restricted products on this machine.')
                                    ->default(false)
                                    ->inline(false),
                                TextInput::make('minimum_age')
                                    ->label('Default minimum age')
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(99)
                                    ->default(21)
                                    ->visible(fn (Get $get): bool => (bool) $get('age_verification_enabled')),
                                Textarea::make('remarks')
                                    ->label('Remarks')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->step(0.0000001),
                                TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->step(0.0000001),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('machine_number')
                    ->label('Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('machine_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Machine $record): string => app(InterconnectedEntityService::class)->machineViewUrl($record))
                    ->color('primary'),
                TextColumn::make('machineGroup.name')
                    ->label('Machine group')
                    ->placeholder('—'),
                TextColumn::make('financeGroup.name')
                    ->label('Financial group')
                    ->placeholder('—'),
                TextColumn::make('user.account')
                    ->label('User')
                    ->placeholder('—'),
                IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),
                TextColumn::make('last_seen_at')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Machine $record): string => $record->isOnline() ? 'Online' : 'Offline')
                    ->color(fn (string $state): string => $state === 'Online' ? 'success' : 'gray')
                    ->icon(fn (string $state): string => $state === 'Online' ? 'heroicon-o-signal' : 'heroicon-o-signal-slash')
                    ->description(fn (Machine $record): ?string => $record->last_seen_at?->diffForHumans()),
                TextColumn::make('inventory_status')
                    ->label('Inventory')
                    ->badge()
                    ->state(function (Machine $record): string {
                        $active = $record->slots->filter(
                            fn ($slot) => $slot->is_active && $slot->product_id !== null
                        );

                        if ($active->isEmpty()) {
                            return 'No slots';
                        }

                        if ($active->where('current_stock', 0)->isNotEmpty()) {
                            return 'Out of stock';
                        }

                        if ($active->filter(fn ($s) => $s->current_stock <= $s->stock_alarm_threshold)->isNotEmpty()) {
                            return 'Low stock';
                        }

                        return 'Stocked';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Stocked' => 'success',
                        'Low stock' => 'warning',
                        'Out of stock' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Stocked' => 'heroicon-o-check-circle',
                        'Low stock' => 'heroicon-o-exclamation-triangle',
                        'Out of stock' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-minus-circle',
                    }),
                TextColumn::make('machine_scenario')
                    ->label('Scenario')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('machine_number')
            ->filters([
                Filter::make('machine_number')
                    ->label('Machine number')
                    ->form([
                        TextInput::make('value')
                            ->label('Machine number')
                            ->placeholder('Search by number…'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('machine_number', 'like', '%'.$data['value'].'%')
                        );
                    }),
                Filter::make('machine_name')
                    ->label('Machine name')
                    ->form([
                        TextInput::make('value')
                            ->label('Machine name')
                            ->placeholder('Search by name…'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('machine_name', 'like', '%'.$data['value'].'%')
                        );
                    }),
                SelectFilter::make('is_enabled')
                    ->label('Enabled')
                    ->options([
                        '1' => 'Enabled',
                        '0' => 'Disabled',
                    ])
                    ->native(false),
                SelectFilter::make('connectivity')
                    ->label('Connectivity')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === 'online') {
                            return $query->where('is_enabled', true)
                                ->where('last_seen_at', '>=', now()->subMinutes(15));
                        }

                        if ($value === 'offline') {
                            return $query->where(function (Builder $offline): void {
                                $offline
                                    ->where('is_enabled', false)
                                    ->orWhere('last_seen_at', '<', now()->subMinutes(15))
                                    ->orWhereNull('last_seen_at');
                            });
                        }

                        return $query;
                    })
                    ->native(false),
            ])
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make()
                    ->label('Open')
                    ->url(fn (Machine $record): string => ViewMachine::getUrl(['record' => $record]))
                    ->icon(Heroicon::OutlinedEye),
                Action::make('manageSlots')
                    ->label('Slots')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->url(fn (Machine $record): string => self::getUrl('slots', ['record' => $record]))
                    ->color('info'),
                EditAction::make()
                    ->modalSubmitActionLabel('Confirm')
                    ->modalCancelActionLabel('Cancel'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No machines found')
            ->emptyStateDescription('Machines linked to your account appear here. Platform admins can add machines with Add.')
            ->emptyStateIcon(Heroicon::OutlinedCpuChip);

        return AccessibleTable::apply($table, 'Search machines by number or name…');
    }

    /**
     * @return list<string>
     */
    protected static function globalSearchAttributes(): array
    {
        return ['machine_name', 'machine_number', 'remarks'];
    }

    /**
     * @return array<string, string>
     */
    protected static function globalSearchDetails(Model $record): array
    {
        if (! $record instanceof Machine) {
            return [];
        }

        return array_filter([
            'Number' => $record->machine_number,
            'Group' => $record->machineGroup?->name,
            'Status' => $record->isOnline() ? 'Online' : 'Offline',
        ]);
    }

    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        if (! $record instanceof Machine) {
            return null;
        }

        return ViewMachine::getUrl(['record' => $record]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMachines::route('/'),
            'view' => ViewMachine::route('/{record}'),
            'slots' => ManageMachineSlots::route('/{record}/slots'),
        ];
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Machine && (auth()->user()?->can('view', $record) ?? false);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', Machine::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Machine && (auth()->user()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Machine && (auth()->user()?->can('delete', $record) ?? false);
    }

    public static function canDeleteAny(): bool
    {
        return app(UserCloudScope::class)->hasFullCloudAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return app(FeatureAccess::class)->allowsAnyNavigation(
            UserFeature::MachinesView,
            UserFeature::MachinesCreate,
            UserFeature::MachineSlots,
        );
    }

    public static function canViewAny(): bool
    {
        return self::shouldRegisterNavigation();
    }
}
