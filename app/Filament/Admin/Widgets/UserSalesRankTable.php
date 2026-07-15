<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

/**
 * Top 10 users ranked by total completed sales revenue across all their machines.
 */
final class UserSalesRankTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = -30;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('User Sales Rank')
            ->description('Top 10 users by total revenue')
            ->query(
                User::query()
                    ->join('machines', 'machines.user_id', '=', 'users.id')
                    ->join('orders', 'orders.machine_no', '=', 'machines.machine_number')
                    ->where('orders.status', 'completed')
                    ->select(
                        'users.id',
                        'users.name',
                        'users.account',
                        DB::raw('SUM(orders.prize_amount) as total_revenue'),
                        DB::raw('COUNT(DISTINCT machines.machine_number) as machine_count'),
                        DB::raw('COUNT(orders.id) as order_count'),
                    )
                    ->groupBy('users.id', 'users.name', 'users.account')
                    ->orderByDesc('total_revenue')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('account')
                    ->label('Account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->limit(24)
                    ->placeholder('—'),
                TextColumn::make('machine_count')
                    ->label('Machines')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('order_count')
                    ->label('Orders')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->money('USD')
                    ->sortable(),
            ])
            ->defaultSort('total_revenue', 'desc')
            ->paginated(false)
            ->striped();
    }
}
