<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\RechargeRecords;

use App\Filament\Admin\Resources\RechargeRecords\Pages\ManageRechargeRecords;
use App\Models\RechargeRecord;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class RechargeRecordResource extends Resource
{
    protected static ?string $model = RechargeRecord::class;

    protected static ?string $slug = 'wallet-recharge-records';

    protected static string|UnitEnum|null $navigationGroup = 'Wallet';

    protected static ?int $navigationSort = 91;

    protected static ?string $navigationLabel = 'Recharge Record';

    protected static ?string $modelLabel = 'recharge record';

    protected static ?string $pluralModelLabel = 'recharge records';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_account')
                    ->label('User account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('machine_number')
                    ->label('Machine number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Money ($)')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('detail')
                    ->label('Detail')
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => filled($state) ? $state : null)
                    ->searchable(),
                TextColumn::make('service_type')
                    ->label('Service type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ordered_at')
                    ->label('Order time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label('Pay time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('ordered_at', 'desc')
            ->filters([
                Filter::make('ordered_between')
                    ->label('Order time')
                    ->form([
                        DatePicker::make('start')
                            ->label('Start time')
                            ->native(false),
                        DatePicker::make('end')
                            ->label('End time')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['start'] ?? null),
                                fn (Builder $q): Builder => $q->whereDate('ordered_at', '>=', $data['start'])
                            )
                            ->when(
                                filled($data['end'] ?? null),
                                fn (Builder $q): Builder => $q->whereDate('ordered_at', '<=', $data['end'])
                            );
                    }),
                Filter::make('machine_number')
                    ->label('Machine number')
                    ->form([
                        TextInput::make('value')
                            ->label('Machine number')
                            ->placeholder('Machine number'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('machine_number', 'like', '%'.$data['value'].'%')
                        );
                    }),
                SelectFilter::make('service_type')
                    ->label('Service type')
                    ->options([
                        'mail serve' => 'mail serve',
                        'Age ID Screen' => 'Age ID Screen',
                    ])
                    ->native(false)
                    ->searchable(),
            ])
            ->deferFilters(false)
            ->recordActions([])
            ->toolbarActions([])
            ->emptyStateHeading('No recharge records')
            ->emptyStateDescription('Wallet top-up history will show here after your first recharge.')
            ->emptyStateIcon(Heroicon::OutlinedBanknotes);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRechargeRecords::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
