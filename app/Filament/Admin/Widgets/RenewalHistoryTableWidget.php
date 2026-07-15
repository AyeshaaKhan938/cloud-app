<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\RenewalPayType;
use App\Enums\RenewalProgress;
use App\Models\RenewalHistory;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class RenewalHistoryTableWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Historical records')
            ->query(RenewalHistory::query())
            ->columns([
                TextColumn::make('user_account')
                    ->label('User account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user_name')
                    ->label('User name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('renewal_account')
                    ->label('Renewal account')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? $state : '—'),
                TextColumn::make('renewal_number')
                    ->label('Renewal number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Renewal amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('renew_equipment')
                    ->label('Renew equipment')
                    ->limit(36)
                    ->tooltip(fn (?string $state): ?string => filled($state) ? $state : null),
                TextColumn::make('renewal_progress')
                    ->label('Renewal progress')
                    ->badge()
                    ->formatStateUsing(fn (?RenewalProgress $state): string => $state?->getLabel() ?? '—'),
                TextColumn::make('pay_type')
                    ->label('Pay type')
                    ->badge()
                    ->formatStateUsing(fn (?RenewalPayType $state): string => $state?->getLabel() ?? '—'),
                TextColumn::make('application_time')
                    ->label('Application time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('application_time', 'desc')
            ->filters([
                Filter::make('application_between')
                    ->label('Application time')
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
                                fn (Builder $q): Builder => $q->whereDate('application_time', '>=', $data['start'])
                            )
                            ->when(
                                filled($data['end'] ?? null),
                                fn (Builder $q): Builder => $q->whereDate('application_time', '<=', $data['end'])
                            );
                    }),
                Filter::make('user_account')
                    ->label('User account')
                    ->form([
                        TextInput::make('value')
                            ->label('User account')
                            ->placeholder('User account'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('user_account', 'like', '%'.$data['value'].'%')
                        );
                    }),
                Filter::make('order_number')
                    ->label('Order number')
                    ->form([
                        TextInput::make('value')
                            ->label('Order number')
                            ->placeholder('Order number'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('order_number', 'like', '%'.$data['value'].'%')
                        );
                    }),
                Filter::make('renewal_account')
                    ->label('Renewal account')
                    ->form([
                        TextInput::make('value')
                            ->label('Renewal account')
                            ->placeholder('Renewal account'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('renewal_account', 'like', '%'.$data['value'].'%')
                        );
                    }),
                SelectFilter::make('renewal_schedule')
                    ->label('Renewal schedule')
                    ->options([
                        '1 year' => '1 year',
                        '2 years' => '2 years',
                    ])
                    ->native(false)
                    ->searchable(),
                SelectFilter::make('pay_type')
                    ->label('Pay type')
                    ->options(RenewalPayType::class)
                    ->native(false)
                    ->searchable(),
            ])
            ->deferFilters(false)
            ->toolbarActions([
                Action::make('exportHistory')
                    ->label('Export')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn () => Notification::make()
                        ->title('Export')
                        ->body('Export will be available when the export pipeline is configured.')
                        ->warning()
                        ->send()),
            ])
            ->recordActions([
                ViewAction::make('renewalDetails')
                    ->label('Renewal details')
                    ->modalHeading('Renewal details')
                    ->color('primary')
                    ->schema([
                        TextInput::make('user_account')
                            ->label('User account')
                            ->disabled(),
                        TextInput::make('user_name')
                            ->label('User name')
                            ->disabled(),
                        TextInput::make('renewal_account')
                            ->label('Renewal account')
                            ->disabled(),
                        TextInput::make('renewal_number')
                            ->label('Renewal number')
                            ->disabled(),
                        TextInput::make('order_number')
                            ->label('Order number')
                            ->disabled(),
                        TextInput::make('amount')
                            ->label('Renewal amount')
                            ->disabled()
                            ->prefix('$'),
                        TextInput::make('renew_equipment')
                            ->label('Renew equipment')
                            ->disabled()
                            ->columnSpanFull(),
                        TextInput::make('renewal_schedule')
                            ->label('Renewal schedule')
                            ->disabled(),
                        TextInput::make('renewal_progress')
                            ->label('Renewal progress')
                            ->disabled()
                            ->formatStateUsing(fn ($state): string => $state instanceof RenewalProgress ? (string) $state->getLabel() : (string) $state),
                        TextInput::make('pay_type')
                            ->label('Pay type')
                            ->disabled()
                            ->formatStateUsing(fn ($state): string => $state instanceof RenewalPayType ? (string) $state->getLabel() : (string) $state),
                        TextInput::make('application_time')
                            ->label('Application time')
                            ->disabled(),
                    ])
                    ->modalWidth('2xl'),
            ])
            ->paginationMode(PaginationMode::Default)
            ->emptyStateHeading('No data');
    }
}
