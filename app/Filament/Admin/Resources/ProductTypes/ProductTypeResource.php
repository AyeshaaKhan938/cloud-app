<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ProductTypes;

use App\Filament\Admin\Resources\ProductTypes\Pages\ManageProductTypes;
use App\Models\SpecificationType;
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

final class ProductTypeResource extends Resource
{
    protected static ?string $model = SpecificationType::class;

    protected static string|UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 13;

    protected static ?string $modelLabel = 'product type';

    protected static ?string $pluralModelLabel = 'product types';

    protected static ?string $navigationLabel = 'Product types';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label('Type name')
                            ->placeholder('E.g. Vape, Beverage, Snack')
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
                            ->label('Type name')
                            ->placeholder('Search…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['name'] ?? null),
                        fn (Builder $q): Builder => $q->where('name', 'like', '%'.$data['name'].'%'),
                    )),
            ])
            ->recordActions([
                EditAction::make()->modalHeading('Edit product type'),
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
            'index' => ManageProductTypes::route('/'),
        ];
    }
}
