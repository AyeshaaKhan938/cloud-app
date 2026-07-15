<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Specifications;

use App\Enums\SpecificationSellingType;
use App\Filament\Admin\Resources\Specifications\Pages\ManageSpecifications;
use App\Models\Specification;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use UnitEnum;

final class SpecificationResource extends Resource
{
    protected static ?string $model = Specification::class;

    protected static string|UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'category';

    protected static ?string $pluralModelLabel = 'categories';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Category name')
                                    ->placeholder('Please enter a category name')
                                    ->validationAttribute('category name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('specification_type')
                                    ->label('Selling type')
                                    ->placeholder('Please select a selling type')
                                    ->validationAttribute('selling type')
                                    ->options(SpecificationSellingType::class)
                                    ->native(false)
                                    ->searchable()
                                    ->required(),
                                TextInput::make('value')
                                    ->label('Value')
                                    ->placeholder('Optional')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Textarea::make('remarks')
                                    ->label('Remarks')
                                    ->placeholder('Enter remarks')
                                    ->rows(4)
                                    ->columnSpanFull(),
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
                TextColumn::make('specification_type')
                    ->label('Selling type')
                    ->formatStateUsing(fn (?SpecificationSellingType $state): string => $state?->getLabel() ?? '')
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Value')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? $state : '—'),
                TextColumn::make('remarks')
                    ->label('Remarks')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('name')
                    ->label('Name')
                    ->form([
                        TextInput::make('name')
                            ->label('Category name')
                            ->placeholder('Search…'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['name'] ?? null),
                            fn (Builder $q): Builder => $q->where('name', 'like', '%'.$data['name'].'%'),
                        );
                    }),
                SelectFilter::make('specification_type')
                    ->label('Selling type')
                    ->options(SpecificationSellingType::class),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit category'),
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
            'index' => ManageSpecifications::route('/'),
        ];
    }
}
