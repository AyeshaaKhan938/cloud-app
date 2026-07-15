<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products;

use App\Enums\CouponGenerationRule;
use App\Filament\Admin\Resources\Products\Pages\ListProductLotteryCodes;
use App\Filament\Admin\Resources\Products\Pages\ManageProductLotteries;
use App\Models\Machine;
use App\Models\ProductLottery;
use App\Services\Products\ProductLotteryCodeGenerator;
use App\Services\Users\UserCloudScope;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class ProductLotteryResource extends Resource
{
    protected static ?string $model = ProductLottery::class;

    protected static string|UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 12;

    protected static ?string $modelLabel = 'product lottery';

    protected static ?string $pluralModelLabel = 'product lotteries';

    protected static ?string $navigationLabel = 'Lotteries';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lottery')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('name')
                                    ->label('Lottery name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false),
                                TextInput::make('quantity')
                                    ->label('Number of codes to generate')
                                    ->helperText('Unique codes are created once when you save. Each code is assigned a random prize tier by weight.')
                                    ->required()
                                    ->integer()
                                    ->minValue(1),
                                Select::make('generation_rule')
                                    ->label('Code format')
                                    ->options(CouponGenerationRule::class)
                                    ->native(false)
                                    ->searchable()
                                    ->required()
                                    ->default(CouponGenerationRule::LettersAndNumbers),
                                DateTimePicker::make('valid_from')
                                    ->label('Valid from')
                                    ->native(false)
                                    ->seconds(false)
                                    ->nullable(),
                                DateTimePicker::make('valid_until')
                                    ->label('Valid until')
                                    ->native(false)
                                    ->seconds(false)
                                    ->nullable()
                                    ->after('valid_from'),
                                TextInput::make('public_draw_token')
                                    ->label('Public draw token')
                                    ->helperText('Store in frontend config (.env). POST /api/v1/product-lottery-draw/{token} returns JSON { price, message, machineLineProductId, machineNo }. Not the internal DB id.')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->copyable()
                                    ->visibleOn('edit')
                                    ->columnSpanFull(),
                                Select::make('machine_no')
                                    ->label('Machine No.')
                                    ->helperText('Reyeah machineNo — número de serie de la vending machine (ej. 866902296600001). Se devuelve en el API draw para que la app sepa qué máquina despacha.')
                                    ->options(function (): array {
                                        $scope = app(UserCloudScope::class);
                                        $query = Machine::query()->orderBy('machine_number');

                                        if ($scope->requiresScoping()) {
                                            $scope->scopeMachines($query);
                                        }

                                        return $query
                                            ->pluck('machine_name', 'machine_number')
                                            ->map(fn (string $name, string $number): string => "{$name} ({$number})")
                                            ->all();
                                    })
                                    ->searchable()
                                    ->nullable()
                                    ->native(false)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Prize tiers')
                    ->description('Tier code (e.g. A, B, C). Weight controls how often each tier is assigned. After codes exist you cannot add new tiers; you may edit weights or tier codes—unredeemed codes are re-rolled to match. You cannot delete a tier that has any codes.')
                    ->schema([
                        Repeater::make('prizes')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->addable(function (Repeater $component): bool {
                                $record = $component->getRecord();

                                if (! $record instanceof ProductLottery || ! $record->exists) {
                                    return true;
                                }

                                return ! $record->codes()->exists();
                            })
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('tier_code')
                                            ->label('Tier code')
                                            ->required()
                                            ->maxLength(32)
                                            ->placeholder('A'),
                                        TextInput::make('name')
                                            ->label('Prize name')
                                            ->maxLength(255)
                                            ->placeholder('Grand prize'),
                                        TextInput::make('prize_amount')
                                            ->label('Prize value')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->minValue(0)
                                            ->step(0.01)
                                            ->suffix('$'),
                                        TextInput::make('weight')
                                            ->label('Weight')
                                            ->helperText('Relative chance (higher = more codes).')
                                            ->required()
                                            ->integer()
                                            ->minValue(1)
                                            ->default(1),
                                        TextInput::make('sort_order')
                                            ->label('Sort order')
                                            ->numeric()
                                            ->integer()
                                            ->default(0)
                                            ->minValue(0),
                                        TextInput::make('line_number')
                                            ->label('Slot / Line number')
                                            ->helperText('Número de slot físico de la máquina (1, 2, 3…). Se envía al Control Board para despachar el producto cuando el usuario gana este prize.')
                                            ->numeric()
                                            ->integer()
                                            ->nullable()
                                            ->minValue(1)
                                            ->placeholder('1')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columns(1)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Add prize tier')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('public_draw_token')
                    ->label('Draw token')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(14),
                TextColumn::make('quantity')
                    ->label('Codes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('remaining')
                    ->label('Unredeemed')
                    ->formatStateUsing(function (mixed $state, TextColumn $column): int {
                        $record = $column->getRecord();

                        if (! $record instanceof ProductLottery) {
                            return 0;
                        }

                        return $record->remainingCodesCount();
                    }),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('valid_until')
                    ->label('Valid until')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('viewCodes')
                    ->label('View codes')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->url(fn (ProductLottery $record): string => self::getUrl('codes', ['record' => $record]))
                    ->visible(fn (ProductLottery $record): bool => $record->codes()->exists()),
                EditAction::make()
                    ->modalHeading('Edit lottery')
                    ->after(function (ProductLottery $record): void {
                        app(ProductLotteryCodeGenerator::class)->generateIfNeeded($record->fresh());
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return app(UserCloudScope::class)->scopeProductLotteries(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProductLotteries::route('/'),
            'codes' => ListProductLotteryCodes::route('/{record}/codes'),
        ];
    }
}
