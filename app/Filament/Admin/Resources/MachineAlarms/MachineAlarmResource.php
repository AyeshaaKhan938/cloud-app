<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MachineAlarms;

use App\Filament\Admin\Resources\MachineAlarms\Pages\ManageMachineAlarms;
use App\Models\Machine;
use App\Models\MachineAlarm;
use App\Services\Filament\InterconnectedEntityService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class MachineAlarmResource extends Resource
{
    protected static ?string $model = MachineAlarm::class;

    protected static ?string $slug = 'machines/alarms';

    protected static string|UnitEnum|null $navigationGroup = 'Machines';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Alarms';

    protected static ?string $modelLabel = 'machine alarm';

    protected static ?string $pluralModelLabel = 'machine alarms';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('machine');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('machine_id')
                                    ->label('Machine')
                                    ->options(fn (): array => Machine::query()
                                        ->orderBy('machine_number')
                                        ->get()
                                        ->mapWithKeys(fn (Machine $m): array => [
                                            $m->id => $m->machine_number.' — '.$m->machine_name,
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->required()
                                    ->native(false)
                                    ->columnSpanFull(),
                                TextInput::make('title')
                                    ->label('Title')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('severity')
                                    ->label('Severity')
                                    ->options([
                                        'info' => 'Info',
                                        'warning' => 'Warning',
                                        'critical' => 'Critical',
                                    ])
                                    ->default('warning')
                                    ->required()
                                    ->native(false),
                                Textarea::make('message')
                                    ->label('Message')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                DateTimePicker::make('triggered_at')
                                    ->label('Triggered at')
                                    ->required()
                                    ->seconds(false)
                                    ->native(false),
                                DateTimePicker::make('acknowledged_at')
                                    ->label('Acknowledged at')
                                    ->seconds(false)
                                    ->native(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('machine.machine_number')
                    ->label('Machine')
                    ->description(fn (MachineAlarm $record): ?string => $record->machine?->machine_name)
                    ->sortable()
                    ->url(fn (MachineAlarm $record): ?string => $record->machine
                        ? app(InterconnectedEntityService::class)->machineViewUrl($record->machine, 'related')
                        : null),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('severity')
                    ->badge()
                    ->sortable(),
                TextColumn::make('triggered_at')
                    ->label('Triggered')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('acknowledged_at')
                    ->label('Acknowledged')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('triggered_at', direction: 'desc')
            ->filters([
                Filter::make('title')
                    ->label('Title')
                    ->form([
                        TextInput::make('value')
                            ->label('Title'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('title', 'like', '%'.$data['value'].'%')
                        );
                    }),
            ])
            ->deferFilters(false)
            ->recordActions([
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
            ->emptyStateHeading('No alarms')
            ->emptyStateDescription('Machine fault and stock alerts will appear here when triggered.')
            ->emptyStateIcon(Heroicon::OutlinedBellAlert);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMachineAlarms::route('/'),
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
