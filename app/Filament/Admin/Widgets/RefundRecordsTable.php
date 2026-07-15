<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class RefundRecordsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Refund Records')
            ->description('All orders with status: Refunded')
            ->query(
                Order::query()
                    ->where('status', 'refunded')
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('machine_no')
                    ->label('Machine')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('product_name')
                    ->label('Product')
                    ->limit(30)
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('line_number')
                    ->label('Slot')
                    ->prefix('#')
                    ->placeholder('—'),
                TextColumn::make('prize_amount')
                    ->label('Refunded amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'card' => 'info',
                        'cash' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('notes')
                    ->label('Reason')
                    ->limit(40)
                    ->placeholder('—')
                    ->tooltip(fn (Order $record): ?string => $record->notes),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn (Order $record): string => $record->created_at->format('M j, Y · g:i A')),
            ])
            ->filters([
                Filter::make('machine_no')
                    ->label('Machine')
                    ->form([
                        TextInput::make('machine_no')
                            ->label('Machine number')
                            ->placeholder('Search…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['machine_no'] ?? null),
                        fn (Builder $q): Builder => $q->where('machine_no', 'like', '%'.$data['machine_no'].'%'),
                    )),
                Filter::make('date_range')
                    ->label('Date range')
                    ->form([
                        DatePicker::make('from')->label('From')->native(false),
                        DatePicker::make('until')->label('Until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['from'] ?? null), fn (Builder $q): Builder => $q->whereDate('created_at', '>=', $data['from']))
                        ->when(filled($data['until'] ?? null), fn (Builder $q): Builder => $q->whereDate('created_at', '<=', $data['until']))
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->striped();
    }
}
