<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use App\Services\Users\UserCloudScope;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Recent completed orders — live from the orders table.
 */
final class RecentDemoOrdersTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?int $sort = -28;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Orders')
            ->description(app(UserCloudScope::class)->hasFullCloudAccess()
                ? 'Last 10 transactions across all machines'
                : 'Last 10 transactions on your machines')
            ->query(
                app(UserCloudScope::class)
                    ->scopeOrders(Order::query())
                    ->latest('created_at')
            )
            ->columns([
                // Machine + slot
                TextColumn::make('machine_no')
                    ->label('Machine')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('line_number')
                    ->label('Slot')
                    ->prefix('#')
                    ->fontFamily(FontFamily::Mono)
                    ->placeholder('—'),

                // Product
                TextColumn::make('product_name')
                    ->label('Product')
                    ->limit(28)
                    ->placeholder('—'),

                // Prize & amount
                TextColumn::make('prize_name')
                    ->label('Prize')
                    ->badge()
                    ->color('success')
                    ->placeholder('—'),

                TextColumn::make('prize_amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),

                // Payment
                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'card' => 'info',
                        'cash' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                // Status
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                // Timestamp
                TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (Order $record): string => $record->created_at->format('M j, Y · g:i A')
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->striped();
    }
}
