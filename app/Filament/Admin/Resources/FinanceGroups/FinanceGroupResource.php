<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FinanceGroups;

use App\Filament\Admin\Resources\FinanceGroups\Pages\ManageFinanceGroups;
use App\Models\FinanceGroup;
use App\Models\Machine;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use UnitEnum;

final class FinanceGroupResource extends Resource
{
    protected static ?string $model = FinanceGroup::class;

    protected static ?string $slug = 'machines/finance-groups';

    protected static string|UnitEnum|null $navigationGroup = 'Machines';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Finance groups';

    protected static ?string $modelLabel = 'financial group';

    protected static ?string $pluralModelLabel = 'financial groups';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('machines')
            ->with('financeUser');
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
                                Select::make('finance_user_id')
                                    ->label('Finance account')
                                    ->placeholder('Please choose finance account')
                                    ->relationship('financeUser', 'account')
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                                Textarea::make('remarks')
                                    ->label('Remarks')
                                    ->rows(3)
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
                                        if ($record instanceof FinanceGroup && $record->exists) {
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
                TextColumn::make('financeUser.account')
                    ->label('Finance account')
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
                    ->modalHeading('Edit Financial Group')
                    ->modalSubmitActionLabel('Confirm')
                    ->modalCancelActionLabel('Cancel')
                    ->using(function (array $data, HasActions&HasSchemas $livewire, Model $record): void {
                        if (! $record instanceof FinanceGroup) {
                            return;
                        }
                        $ids = $data['machine_ids'] ?? [];
                        unset($data['machine_ids']);
                        $record->update(Arr::only($data, ['name', 'finance_user_id', 'remarks']));
                        Machine::query()->where('finance_group_id', $record->id)->update(['finance_group_id' => null]);
                        if ($ids !== []) {
                            Machine::query()->whereIn('id', $ids)->update(['finance_group_id' => $record->id]);
                        }
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No finance groups yet')
            ->emptyStateDescription('Organize machines into billing groups for revenue tracking.')
            ->emptyStateIcon(Heroicon::OutlinedBanknotes);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFinanceGroups::route('/'),
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
