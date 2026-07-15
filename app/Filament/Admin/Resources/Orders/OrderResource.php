<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders;

use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\AuthorizesUserFeature;
use App\Filament\Admin\Concerns\EnrichesGlobalSearch;
use App\Filament\Admin\Resources\Orders\Pages\ManageOrders;
use App\Filament\Admin\Support\AccessibleTable;
use App\Models\Order;
use App\Models\Product;
use App\Services\Filament\InterconnectedEntityService;
use App\Services\Users\UserCloudScope;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class OrderResource extends Resource
{
    use AuthorizesUserFeature;
    use EnrichesGlobalSearch;

    protected static ?string $model = Order::class;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'order';

    protected static ?string $pluralModelLabel = 'orders';

    protected static ?string $navigationLabel = 'Order list';

    protected static ?string $recordTitleAttribute = 'machine_no';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('machine_no')
                                    ->label('Machine')
                                    ->disabled(),
                                TextInput::make('line_number')
                                    ->label('Slot #')
                                    ->disabled(),
                                TextInput::make('product_name')
                                    ->label('Product')
                                    ->disabled()
                                    ->columnSpanFull(),
                                TextInput::make('prize_name')
                                    ->label('Prize tier')
                                    ->disabled(),
                                TextInput::make('prize_amount')
                                    ->label('Amount (USD)')
                                    ->disabled()
                                    ->prefix('$'),
                                TextInput::make('payment_method')
                                    ->label('Payment method')
                                    ->disabled()
                                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '')),
                                TextInput::make('payment_reference')
                                    ->label('Payment reference')
                                    ->disabled(),
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'completed' => 'Completed',
                                        'failed' => 'Failed',
                                        'refunded' => 'Refunded',
                                    ])
                                    ->native(false),
                                DateTimePicker::make('completed_at')
                                    ->label('Completed at')
                                    ->disabled()
                                    ->seconds(false),
                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('machine_no')
                    ->label('Machine')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->url(function (Order $record): ?string {
                        $machine = app(InterconnectedEntityService::class)->findMachineByNumber($record->machine_no);

                        return $machine ? app(InterconnectedEntityService::class)->machineViewUrl($machine, 'related') : null;
                    }),
                TextColumn::make('product_name')
                    ->label('Product')
                    ->limit(30)
                    ->searchable()
                    ->placeholder('—')
                    ->url(function (Order $record): ?string {
                        if (! filled($record->product_name)) {
                            return null;
                        }

                        $product = Product::query()
                            ->where('name', $record->product_name)
                            ->first();

                        return $product instanceof Product
                            ? app(InterconnectedEntityService::class)->productViewUrl($product)
                            : null;
                    }),
                TextColumn::make('line_number')
                    ->label('Slot')
                    ->prefix('#')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('prize_name')
                    ->label('Prize')
                    ->badge()
                    ->color('success')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('prize_amount')
                    ->label('Amount')
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
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn (Order $record): string => $record->created_at->format('M j, Y · g:i A')),
            ])
            ->defaultSort('created_at', 'desc')
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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->native(false),
                SelectFilter::make('payment_method')
                    ->label('Payment method')
                    ->options([
                        'cash' => 'Cash',
                        'card' => 'Card',
                        'other' => 'Other',
                    ])
                    ->native(false),
                Filter::make('created_between')
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
            ->recordActions([
                ViewAction::make()->modalHeading('Order details'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);

        return AccessibleTable::apply($table, 'Search orders by machine, product, or prize…');
    }

    /**
     * @return list<string>
     */
    protected static function globalSearchAttributes(): array
    {
        return ['machine_no', 'product_name', 'prize_name', 'line_number'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        if (! $record instanceof Order) {
            return (string) parent::getGlobalSearchResultTitle($record);
        }

        return trim($record->machine_no.' · '.($record->product_name ?: 'Order'));
    }

    /**
     * @return array<string, string>
     */
    protected static function globalSearchDetails(Model $record): array
    {
        if (! $record instanceof Order) {
            return [];
        }

        return array_filter([
            'Product' => $record->product_name,
            'Amount' => '$'.number_format((float) $record->prize_amount, 2),
            'Status' => ucfirst((string) $record->status),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrders::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return app(UserCloudScope::class)->scopeOrders(parent::getEloquentQuery());
    }

    protected static function requiredUserFeature(): UserFeature
    {
        return UserFeature::Sales;
    }
}
