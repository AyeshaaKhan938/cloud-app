<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class DeviceIncomeTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Revenue by Machine')
            ->query(
                Order::query()
                    ->completed()
                    ->leftJoin('machines', 'machines.machine_number', '=', 'orders.machine_no')
                    ->select(
                        'orders.machine_no',
                        'machines.machine_name',
                        DB::raw('COUNT(orders.id) as total_orders'),
                        DB::raw('SUM(orders.prize_amount) as total_revenue'),
                        DB::raw('AVG(orders.prize_amount) as avg_order_value'),
                    )
                    ->groupBy('orders.machine_no', 'machines.machine_name')
                    ->orderByDesc('total_revenue')
            )
            ->columns([
                TextColumn::make('machine_no')
                    ->label('Machine #')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('machine_name')
                    ->label('Machine name')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('total_orders')
                    ->label('Orders')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('total_revenue')
                    ->label('Total revenue')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('avg_order_value')
                    ->label('Avg. order')
                    ->money('USD')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label('Date range')
                    ->form([
                        DatePicker::make('from')->label('From')->native(false),
                        DatePicker::make('until')->label('Until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['from'] ?? null), fn (Builder $q): Builder => $q->whereDate('orders.created_at', '>=', $data['from']))
                        ->when(filled($data['until'] ?? null), fn (Builder $q): Builder => $q->whereDate('orders.created_at', '<=', $data['until']))
                    ),
            ])
            ->defaultSort('total_revenue', 'desc')
            ->paginated([25, 50, 100])
            ->striped();
    }
}
