<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products;

use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\AuthorizesUserFeature;
use App\Filament\Admin\Concerns\EnrichesGlobalSearch;
use App\Filament\Admin\Resources\Products\Pages\ManageProducts;
use App\Filament\Admin\Resources\Products\Pages\ViewProduct;
use App\Filament\Admin\Support\AccessibleTable;
use App\Models\Product;
use App\Services\Filament\InterconnectedEntityService;
use App\Services\Users\UserCloudScope;
use App\Support\PayPalCurrencyOptions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use UnitEnum;

final class ProductResource extends Resource
{
    use AuthorizesUserFeature;
    use EnrichesGlobalSearch;

    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'product';

    protected static ?string $pluralModelLabel = 'products';

    protected static ?string $navigationLabel = 'All products';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Product name')
                                    ->placeholder('Please Input product name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('cost')
                                    ->label('Cost price')
                                    ->placeholder('Please Input cost price')
                                    ->required()
                                    ->numeric()
                                    ->suffix('$')
                                    ->step(0.01)
                                    ->minValue(0),
                                Select::make('specification_id')
                                    ->label('Category')
                                    ->placeholder('Please select a category')
                                    ->relationship('specification', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->columnSpanFull(),
                                FileUpload::make('main_image')
                                    ->label('Product picture')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products/main')
                                    ->maxSize(5120)
                                    ->required()
                                    ->columnSpanFull(),
                                TextInput::make('price')
                                    ->label('Sale price')
                                    ->numeric()
                                    ->suffix('$')
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->default(0),
                                TextInput::make('barcode')
                                    ->label('Barcode')
                                    ->maxLength(100),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false),
                                Toggle::make('requires_age_verification')
                                    ->label('Requires age verification')
                                    ->helperText('When enabled on a machine with age verification, customers must scan ID before dispense.')
                                    ->default(false)
                                    ->inline(false),
                                TextInput::make('minimum_age')
                                    ->label('Minimum age')
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(99)
                                    ->nullable()
                                    ->placeholder('Use machine default')
                                    ->visible(fn (Get $get): bool => (bool) $get('requires_age_verification')),
                            ]),
                    ]),
                Toggle::make('_library_expansion_open')
                    ->label('Product expansion settings')
                    ->inline(false)
                    ->live()
                    ->dehydrated(false)
                    ->default(false)
                    ->afterStateHydrated(function (Toggle $component, mixed $state): void {
                        $record = $component->getRecord();
                        if ($record instanceof Product && $record->exists) {
                            $component->state(self::libraryExpansionShouldDefaultOpen($record));
                        }
                    })
                    ->columnSpanFull(),
                Section::make('Product expansion settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('paypal_currency')
                                    ->label('PayPal currency symbol')
                                    ->placeholder('Please select')
                                    ->options(PayPalCurrencyOptions::selectOptions())
                                    ->searchable(),
                                Select::make('product_tag_id')
                                    ->label('Product tag')
                                    ->placeholder('Please selected')
                                    ->relationship('productTag', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                TextInput::make('brand')
                                    ->label('Brand')
                                    ->placeholder('Please input brand')
                                    ->maxLength(255),
                                TextInput::make('product_number')
                                    ->label('Product number')
                                    ->placeholder('Please input product number')
                                    ->maxLength(100),
                            ]),
                        Repeater::make('media_expansions')
                            ->label('Image or video expansion')
                            ->addActionLabel('+ newly added')
                            ->deleteAction(
                                fn (Action $action): Action => $action
                                    ->label('delete')
                                    ->color('danger')
                            )
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        FileUpload::make('media_path')
                                            ->label('File')
                                            ->disk('public')
                                            ->directory('products/expansions')
                                            ->acceptedFileTypes([
                                                'image/*',
                                                'video/*',
                                            ])
                                            ->maxSize(10240)
                                            ->required()
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->label('Description')
                                            ->placeholder('Please enter a description of the image or video on the left.')
                                            ->maxLength(500)
                                            ->rows(4)
                                            ->helperText('0/500 characters max.')
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                            ->helperText('Tip: images or videos in the expansion should not exceed 5MB, and videos should not exceed 10MB.')
                            ->columnSpanFull(),
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('product_icon')
                                    ->label('Product icon')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products/icons')
                                    ->maxSize(5120)
                                    ->nullable(),
                                FileUpload::make('model_3d_path')
                                    ->label('3D file')
                                    ->disk('public')
                                    ->directory('products/3d')
                                    ->acceptedFileTypes([
                                        '.gltf',
                                        '.glb',
                                        '.fbx',
                                    ])
                                    ->maxSize(10240)
                                    ->helperText(new HtmlString(
                                        '<span class="text-danger-600 dark:text-danger-400">Supported: gltf, glb, fbx</span>'
                                    )),
                            ]),
                        Textarea::make('product_remarks')
                            ->label('Product remarks')
                            ->placeholder('Product remarks')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Product description')
                            ->placeholder('Please enter the product description')
                            ->rows(4)
                            ->columnSpanFull(),
                        RichEditor::make('product_details')
                            ->label('Product details')
                            ->helperText('Characters shown in editor. No more than 5000 words.')
                            ->rules([
                                fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                                    $plain = trim(strip_tags((string) $value));
                                    if ($plain === '') {
                                        return;
                                    }
                                    $words = preg_split('/\s+/', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                                    if (count($words) > 5000) {
                                        $fail('Product details must not exceed 5000 words.');
                                    }
                                },
                            ])
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Get $get): bool => (bool) $get('_library_expansion_open')),
                Hidden::make('sku'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('main_image')
                    ->label('Image')
                    ->disk('public')
                    ->circular()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->url(fn (Product $record): string => app(InterconnectedEntityService::class)->productViewUrl($record))
                    ->color('primary'),
                TextColumn::make('product_number')
                    ->label('Product number')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('productTag.name')
                    ->label('Product tag')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('specification.name')
                    ->label('Category')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('cost')
                    ->label('Cost')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Retail')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                IconColumn::make('requires_age_verification')
                    ->label('Age check')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('name')
                    ->label('Product name')
                    ->form([
                        TextInput::make('value')->label('Product name')->placeholder('Search name…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $q): Builder => $q->where('name', 'like', '%'.$data['value'].'%'),
                    )),
                Filter::make('sku')
                    ->label('SKU')
                    ->form([
                        TextInput::make('value')->label('SKU')->placeholder('Search SKU…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $q): Builder => $q->where('sku', 'like', '%'.$data['value'].'%'),
                    )),
                SelectFilter::make('is_active')
                    ->label('Active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Open')
                    ->url(fn (Product $record): string => ViewProduct::getUrl(['record' => $record]))
                    ->icon(Heroicon::OutlinedEye),
                EditAction::make()
                    ->modalHeading('Edit product'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);

        return AccessibleTable::apply($table, 'Search products by name, SKU, or number…');
    }

    /**
     * @return list<string>
     */
    protected static function globalSearchAttributes(): array
    {
        return ['name', 'sku', 'product_number', 'barcode'];
    }

    /**
     * @return array<string, string>
     */
    protected static function globalSearchDetails(Model $record): array
    {
        if (! $record instanceof Product) {
            return [];
        }

        return array_filter([
            'SKU' => $record->sku,
            'Category' => $record->specification?->name,
            'Price' => '$'.number_format((float) $record->price, 2),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return app(UserCloudScope::class)->scopeProducts(parent::getEloquentQuery());
    }

    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        if (! $record instanceof Product) {
            return null;
        }

        return ViewProduct::getUrl(['record' => $record]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProducts::route('/'),
            'view' => ViewProduct::route('/{record}'),
        ];
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Product && (auth()->user()?->can('view', $record) ?? false);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', Product::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Product && (auth()->user()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Product && (auth()->user()?->can('delete', $record) ?? false);
    }

    protected static function requiredUserFeature(): UserFeature
    {
        return UserFeature::Products;
    }

    private static function libraryExpansionShouldDefaultOpen(Product $record): bool
    {
        return filled($record->paypal_currency)
            || $record->specification_id !== null
            || $record->product_tag_id !== null
            || filled($record->brand)
            || filled($record->product_number)
            || (is_array($record->media_expansions) && count($record->media_expansions) > 0)
            || filled($record->product_icon)
            || filled($record->model_3d_path)
            || filled($record->product_remarks)
            || filled($record->description)
            || filled($record->product_details)
            || filled($record->barcode)
            || (is_array($record->product_tones) && count($record->product_tones) > 0);
    }
}
