<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdvertisementTags;

use App\Filament\Admin\Resources\AdvertisementTags\Pages\CreateAdvertisementTag;
use App\Filament\Admin\Resources\AdvertisementTags\Pages\EditAdvertisementTag;
use App\Filament\Admin\Resources\AdvertisementTags\Pages\ListAdvertisementTags;
use App\Models\AdvertisementTag;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class AdvertisementTagResource extends Resource
{
    protected static ?string $model = AdvertisementTag::class;

    protected static string|UnitEnum|null $navigationGroup = 'Advertising';

    protected static ?int $navigationSort = 32;

    protected static ?string $modelLabel = 'advertisement tag';

    protected static ?string $pluralModelLabel = 'advertisement tags';

    protected static ?string $navigationLabel = 'Tag advertisement';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tag information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Tag name')
                            ->placeholder('Please enter tag name')
                            ->required()
                            ->maxLength(50)
                            ->live(onBlur: true)
                            ->helperText(fn (?string $state): string => strlen((string) $state).'/50')
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'required' => 'Please enter tag name',
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Tagged advertisements')
                    ->description('Choose which advertisements carry this tag. You can also set tags from each advertisement’s edit form.')
                    ->schema([
                        Select::make('advertisements')
                            ->label('Advertisements')
                            ->relationship('advertisements', 'title')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tag name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('advertisements_count')
                    ->label('Ads')
                    ->counts('advertisements')
                    ->sortable(),
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
                EditAction::make()
                    ->url(fn (AdvertisementTag $record): string => self::getUrl('edit', ['record' => $record])),
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
            'index' => ListAdvertisementTags::route('/'),
            'create' => CreateAdvertisementTag::route('/create'),
            'edit' => EditAdvertisementTag::route('/{record}/edit'),
        ];
    }
}
