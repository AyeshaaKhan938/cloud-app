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

final class ProductIncomeTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Revenue by Product')
            ->query(
                Order::query()
                    ->completed()
                    ->whereNotNull('product_name')
                    ->select(
                        'product_name',
                        DB::raw('COUNT(id) as total_orders'),
                        DB::raw('SUM(prize_amount) as total_revenue'),
                        DB::raw('AVG(prize_amount) as avg_prize'),
                    )
                    ->groupBy('product_name')
                    ->orderByDesc('total_revenue')
            )
            ->columns([
                TextColumn::make('product_name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_orders')
                    ->label('Orders')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('total_revenue')
                    ->label('Total revenue')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('avg_prize')
                    ->label('Avg. prize')
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
                        ->when(filled($data['from'] ?? null), fn (Builder $q): Builder => $q->whereDate('created_at', '>=', $data['from']))
                        ->when(filled($data['until'] ?? null), fn (Builder $q): Builder => $q->whereDate('created_at', '<=', $data['until']))
                    ),
            ])
            ->defaultSort('total_revenue', 'desc')
            ->paginated([25, 50, 100])
            ->striped();
    }
}
