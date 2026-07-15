<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Advertisements;

use App\Enums\AdvertisementType;
use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\AuthorizesUserFeature;
use App\Filament\Admin\Resources\Advertisements\Pages\ManageAdvertisements;
use App\Models\Advertisement;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\View as LayoutView;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

final class AdvertisementResource extends Resource
{
    use AuthorizesUserFeature;

    protected static ?string $model = Advertisement::class;

    protected static string|UnitEnum|null $navigationGroup = 'Advertising';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'advertisement';

    protected static ?string $pluralModelLabel = 'advertisements';

    protected static ?string $navigationLabel = 'Advertisement list';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->label('Advertisement title')
                            ->placeholder('Please enter advertisement title')
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => 'Please enter advertisement title',
                            ])
                            ->columnSpanFull(),
                        Select::make('type')
                            ->label('Advertisement type')
                            ->placeholder('Please select type')
                            ->options(AdvertisementType::class)
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->required(),
                        FileUpload::make('media_path')
                            ->label('Upload advertisement')
                            ->disk('public')
                            ->directory('advertisements/media')
                            ->extraAttributes(['class' => 'fi-ad-file-upload'])
                            ->required()
                            ->acceptedFileTypes(fn (Get $get): array => match ($get('type')) {
                                AdvertisementType::Image->value => ['image/*'],
                                AdvertisementType::Video->value => ['video/*'],
                                default => ['image/*', 'video/*'],
                            })
                            ->maxSize(102400)
                            ->helperText(new HtmlString(
                                '<span class="text-sm text-gray-600 dark:text-gray-400">'
                                .'Portrait: 1080×1920 (full), 1080×440 (top). '
                                .'Landscape: 1920×1080 (full), 823×1080 (list page). '
                                .'Images &lt; 3MB, videos &lt; 100MB.'
                                .'</span>'
                            ))
                            ->columnSpanFull(),
                        Select::make('tags')
                            ->label('Tags')
                            ->placeholder('Optional')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ]),
                Toggle::make('_ad_additional_open')
                    ->label('Additional information')
                    ->inline(false)
                    ->live()
                    ->dehydrated(false)
                    ->default(false)
                    ->afterStateHydrated(function (Toggle $component, mixed $state): void {
                        $record = $component->getRecord();
                        if ($record instanceof Advertisement && $record->exists) {
                            $component->state(self::additionalSectionShouldDefaultOpen($record));
                        }
                    })
                    ->columnSpanFull(),
                Section::make('Additional information')
                    ->schema([
                        TextInput::make('link_url')
                            ->label('Image link address')
                            ->placeholder('https://')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                        TextInput::make('advertiser_name')
                            ->label('Advertiser name')
                            ->maxLength(255),
                        TextInput::make('cost')
                            ->label('Advertisement cost')
                            ->numeric()
                            ->suffix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->nullable(),
                        Textarea::make('remarks')
                            ->label('Advertisement remarks')
                            ->placeholder('Please enter advertisement remarks')
                            ->maxLength(300)
                            ->rows(3)
                            ->helperText(fn (?string $state): string => strlen((string) $state).'/300'),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => (bool) $get('_ad_additional_open')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    LayoutView::make('filament.tables.columns.advertisement-media'),
                    TextColumn::make('title')
                        ->label('Title')
                        ->weight(FontWeight::Bold)
                        ->searchable(),
                    TextColumn::make('type')
                        ->label('Type')
                        ->badge()
                        ->formatStateUsing(fn (?AdvertisementType $state): string => $state?->getLabel() ?? ''),
                    TextColumn::make('advertiser_name')
                        ->label('Advertiser')
                        ->searchable()
                        ->formatStateUsing(fn (?string $state): string => filled($state) ? $state : '—'),
                    TextColumn::make('created_at')
                        ->label('Created')
                        ->dateTime()
                        ->sortable(),
                ]),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(AdvertisementType::class),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit advertisement')
                    ->mutateFormDataUsing(fn (array $data): array => self::mutateAdvertisementFormData($data)),
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
            'index' => ManageAdvertisements::route('/'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateAdvertisementFormData(array $data): array
    {
        if (! (bool) ($data['_ad_additional_open'] ?? false)) {
            $data['link_url'] = null;
            $data['advertiser_name'] = null;
            $data['cost'] = null;
            $data['remarks'] = null;
        }

        unset($data['_ad_additional_open']);

        return $data;
    }

    private static function additionalSectionShouldDefaultOpen(Advertisement $record): bool
    {
        return filled($record->link_url)
            || filled($record->advertiser_name)
            || $record->cost !== null
            || filled($record->remarks);
    }

    protected static function requiredUserFeature(): UserFeature
    {
        return UserFeature::Advertising;
    }
}
