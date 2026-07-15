<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Coupons;

use App\Enums\CouponDiscountType;
use App\Enums\CouponDistributionRule;
use App\Enums\CouponGenerationRule;
use App\Filament\Admin\Resources\Coupons\Pages\ListCouponCodes;
use App\Filament\Admin\Resources\Coupons\Pages\ManageCoupons;
use App\Models\Coupon;
use App\Services\Coupons\CouponCodeGenerator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 12;

    protected static ?string $modelLabel = 'coupon';

    protected static ?string $pluralModelLabel = 'coupons';

    protected static ?string $navigationLabel = 'Coupons';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Coupon details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                CheckboxList::make('machineGroups')
                                    ->label('Machine groups')
                                    ->relationship('machineGroups', 'name')
                                    ->helperText('Leave empty to apply the coupon to all groups.')
                                    ->bulkToggleable()
                                    ->columnSpanFull(),
                                TextInput::make('name')
                                    ->label('Coupon name')
                                    ->placeholder('Enter the coupon name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('purchase_amount')
                                    ->label('Purchase amount')
                                    ->placeholder('Get a discount when spending certain amount.')
                                    ->hint('(If enter 0, no limitation for the purchase amount.)')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('$'),
                                Select::make('coupon_type')
                                    ->label('Coupon type')
                                    ->placeholder('Please choose the coupon type.')
                                    ->options(CouponDiscountType::class)
                                    ->native(false)
                                    ->searchable()
                                    ->live()
                                    ->required(),
                                TextInput::make('discount_value')
                                    ->label(fn (Get $get): string => match ($get('coupon_type')) {
                                        CouponDiscountType::Percentage->value => 'Discount percentage value (%)',
                                        CouponDiscountType::FixedAmount->value => 'Discount amount ($)',
                                        default => 'Discount amount',
                                    })
                                    ->placeholder('Enter the discount amount')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01),
                                Select::make('generation_rule')
                                    ->label('Generate rules')
                                    ->placeholder('Please select the generation rule')
                                    ->options(CouponGenerationRule::class)
                                    ->native(false)
                                    ->searchable()
                                    ->required(),
                                Select::make('distribution_rule')
                                    ->label('Distribution rules')
                                    ->placeholder('Please select distribution rules')
                                    ->options(CouponDistributionRule::class)
                                    ->native(false)
                                    ->searchable()
                                    ->required(),
                                TextInput::make('usage_frequency')
                                    ->label('Usage frequency')
                                    ->placeholder('Please enter the number of uses')
                                    ->required()
                                    ->integer()
                                    ->minValue(1)
                                    ->validationMessages([
                                        'integer' => 'Please enter an integer greater than 0',
                                        'min' => 'Please enter an integer greater than 0',
                                    ]),
                                DateTimePicker::make('valid_from')
                                    ->label('Valid from')
                                    ->placeholder('Choose date and time')
                                    ->native(false)
                                    ->seconds(false)
                                    ->required(),
                                DateTimePicker::make('valid_until')
                                    ->label('Valid until')
                                    ->placeholder('Choose date and time')
                                    ->native(false)
                                    ->seconds(false)
                                    ->required()
                                    ->after('valid_from'),
                                TextInput::make('quantity')
                                    ->label('Coupon quantity')
                                    ->placeholder('Please enter the coupon quantity.')
                                    ->helperText('Applies when codes or QR codes are generated for this coupon.')
                                    ->required()
                                    ->integer()
                                    ->minValue(1)
                                    ->validationMessages([
                                        'integer' => 'Please enter an integer greater than 0',
                                        'min' => 'Please enter an integer greater than 0',
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('machineGroups.name')
                    ->label('Machine groups')
                    ->badge()
                    ->color('info')
                    ->placeholder('All groups')
                    ->toggleable(),
                TextColumn::make('purchase_amount')
                    ->label('Min. purchase')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('coupon_type')
                    ->label('Type')
                    ->formatStateUsing(fn (?CouponDiscountType $state): string => $state?->getLabel() ?? '')
                    ->sortable(),
                TextColumn::make('discount_value')
                    ->label('Discount')
                    ->formatStateUsing(function (mixed $state, TextColumn $column): string {
                        $record = $column->getRecord();

                        if (! $record instanceof Coupon) {
                            return '';
                        }

                        return $record->formattedDiscount();
                    }),
                TextColumn::make('distribution_rule')
                    ->label('Distribution')
                    ->formatStateUsing(fn (?CouponDistributionRule $state): string => $state?->getLabel() ?? '')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('valid_until')
                    ->label('Valid until')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('remaining')
                    ->label('Remaining')
                    ->formatStateUsing(function (mixed $state, TextColumn $column): int {
                        $record = $column->getRecord();

                        if (! $record instanceof Coupon) {
                            return 0;
                        }

                        return $record->remainingCodesCount();
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('viewCodes')
                    ->label('View codes')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->url(fn (Coupon $record): string => self::getUrl('codes', ['record' => $record]))
                    ->visible(fn (Coupon $record): bool => $record->distribution_rule->requiresGeneratedCouponCodes()),
                EditAction::make()
                    ->modalHeading('Edit coupon')
                    ->after(function (Coupon $record): void {
                        app(CouponCodeGenerator::class)->generateIfNeeded($record->fresh());
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCoupons::route('/'),
            'codes' => ListCouponCodes::route('/{record}/codes'),
        ];
    }
}
