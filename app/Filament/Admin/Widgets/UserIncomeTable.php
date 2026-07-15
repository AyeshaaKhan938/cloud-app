<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class UserIncomeTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Revenue by User')
            ->query(
                User::query()
                    ->join('machines', 'machines.user_id', '=', 'users.id')
                    ->join('orders', 'orders.machine_no', '=', 'machines.machine_number')
                    ->where('orders.status', 'completed')
                    ->select(
                        'users.id',
                        'users.account',
                        'users.name',
                        DB::raw('COUNT(DISTINCT machines.machine_number) as machine_count'),
                        DB::raw('COUNT(orders.id) as total_orders'),
                        DB::raw('SUM(orders.prize_amount) as total_revenue'),
                    )
                    ->groupBy('users.id', 'users.account', 'users.name')
                    ->orderByDesc('total_revenue')
            )
            ->columns([
                TextColumn::make('account')
                    ->label('Account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->placeholder('—')
                    ->limit(28),
                TextColumn::make('machine_count')
                    ->label('Machines')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('total_orders')
                    ->label('Orders')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('total_revenue')
                    ->label('Total revenue')
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
