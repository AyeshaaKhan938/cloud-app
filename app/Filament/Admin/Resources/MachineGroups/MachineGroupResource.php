<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MachineGroups;

use App\Filament\Admin\Resources\MachineGroups\Pages\ManageMachineGroups;
use App\Models\Machine;
use App\Models\MachineGroup;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use UnitEnum;

final class MachineGroupResource extends Resource
{
    protected static ?string $model = MachineGroup::class;

    protected static ?string $slug = 'machines/groups';

    protected static string|UnitEnum|null $navigationGroup = 'Machines';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Machine groups';

    protected static ?string $modelLabel = 'machine group';

    protected static ?string $pluralModelLabel = 'machine groups';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('machines')
            ->with('operationAndMaintenanceUser');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Group name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('operation_and_maintenance_user_id')
                                    ->label('Operation and maintenance account')
                                    ->placeholder('Please choose Operation and maintenance account')
                                    ->relationship('operationAndMaintenanceUser', 'account')
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Select::make('advertisement_group_id')
                                    ->label('Advertisement group')
                                    ->relationship('advertisementGroup', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->placeholder('No advertisements')
                                    ->helperText('All machines in this group will show these ads unless individually overridden.')
                                    ->columnSpanFull(),
                                Placeholder::make('currency_note')
                                    ->label('')
                                    ->content(new HtmlString(
                                        '<p class="text-sm text-gray-600 dark:text-gray-400">'
                                        .'Please note: The original currency symbol settings have been migrated to device configuration.'
                                        .'</p>'
                                    ))
                                    ->columnSpanFull(),
                                CheckboxList::make('machine_ids')
                                    ->label('Machines in this group')
                                    ->options(fn (): array => Machine::query()
                                        ->orderBy('machine_number')
                                        ->get()
                                        ->mapWithKeys(fn (Machine $m): array => [
                                            $m->id => $m->machine_number.' — '.$m->machine_name,
                                        ])
                                        ->all())
                                    ->afterStateHydrated(function (CheckboxList $component): void {
                                        $record = $component->getLivewire()->getMountedAction()?->getRecord();
                                        if ($record instanceof MachineGroup && $record->exists) {
                                            $component->state($record->machines()->pluck('id')->all());
                                        }
                                    })
                                    ->searchable()
                                    ->bulkToggleable()
                                    ->columns(1)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Group name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('operationAndMaintenanceUser.account')
                    ->label('O&M account')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? $state : '—'),
                TextColumn::make('machines_count')
                    ->label('Machines')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                Filter::make('name')
                    ->label('Group name')
                    ->form([
                        TextInput::make('value')
                            ->label('Group name')
                            ->placeholder('Group name'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('name', 'like', '%'.$data['value'].'%')
                        );
                    }),
            ])
            ->deferFilters(false)
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit Machine Group')
                    ->modalSubmitActionLabel('Confirm')
                    ->modalCancelActionLabel('Cancel')
                    ->using(function (array $data, HasActions&HasSchemas $livewire, Model $record): void {
                        if (! $record instanceof MachineGroup) {
                            return;
                        }
                        $ids = $data['machine_ids'] ?? [];
                        unset($data['machine_ids']);
                        $record->update(Arr::only($data, ['name', 'operation_and_maintenance_user_id', 'advertisement_group_id']));
                        Machine::query()->where('machine_group_id', $record->id)->update(['machine_group_id' => null]);
                        if ($ids !== []) {
                            Machine::query()->whereIn('id', $ids)->update(['machine_group_id' => $record->id]);
                        }
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No machine groups yet')
            ->emptyStateDescription('Group machines by location or route to simplify bulk operations.')
            ->emptyStateIcon(Heroicon::OutlinedSquares2x2);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMachineGroups::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->check();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->check();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->check();
    }

    public static function canDeleteAny(): bool
    {
        return auth()->check();
    }
}
