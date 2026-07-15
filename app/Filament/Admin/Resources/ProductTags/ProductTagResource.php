<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ProductTags;

use App\Filament\Admin\Resources\ProductTags\Pages\ManageProductTags;
use App\Models\ProductTag;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class ProductTagResource extends Resource
{
    protected static ?string $model = ProductTag::class;

    protected static string|UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 14;

    protected static ?string $modelLabel = 'product tag';

    protected static ?string $pluralModelLabel = 'product tags';

    protected static ?string $navigationLabel = 'Product tags';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label('Tag name')
                            ->placeholder('E.g. Nicotine-free, Sugar-free, New arrival')
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('name')
                    ->label('Name')
                    ->form([
                        TextInput::make('name')
                            ->label('Tag name')
                            ->placeholder('Search…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['name'] ?? null),
                        fn (Builder $q): Builder => $q->where('name', 'like', '%'.$data['name'].'%'),
                    )),
            ])
            ->recordActions([
                EditAction::make()->modalHeading('Edit product tag'),
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
            'index' => ManageProductTags::route('/'),
        ];
    }
}
