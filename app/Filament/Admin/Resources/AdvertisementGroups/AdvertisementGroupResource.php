<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdvertisementGroups;

use App\Enums\AdvertisementGroupSlot;
use App\Enums\AdvertisementType;
use App\Filament\Admin\Resources\AdvertisementGroups\Pages\CreateAdvertisementGroup;
use App\Filament\Admin\Resources\AdvertisementGroups\Pages\EditAdvertisementGroup;
use App\Filament\Admin\Resources\AdvertisementGroups\Pages\ListAdvertisementGroups;
use App\Models\Advertisement;
use App\Models\AdvertisementGroup;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class AdvertisementGroupResource extends Resource
{
    protected static ?string $model = AdvertisementGroup::class;

    protected static string|UnitEnum|null $navigationGroup = 'Advertising';

    protected static ?int $navigationSort = 31;

    protected static ?string $modelLabel = 'advertisement group';

    protected static ?string $pluralModelLabel = 'advertisement groups';

    protected static ?string $navigationLabel = 'Advertisement group';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Group information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Group name')
                            ->placeholder('Please enter group name')
                            ->required()
                            ->maxLength(50)
                            ->live(onBlur: true)
                            ->helperText(fn (?string $state): string => strlen((string) $state).'/50')
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'required' => 'Please enter group name',
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Ad configuration')
                    ->schema([
                        Tabs::make('slots')
                            ->tabs([
                                Tab::make('Screensaver slot')
                                    ->schema(self::slotTabSchema(AdvertisementGroupSlot::Screensaver)),
                                Tab::make('Top slot')
                                    ->schema(self::slotTabSchema(AdvertisementGroupSlot::Top)),
                                Tab::make('External screen')
                                    ->schema(self::slotTabSchema(AdvertisementGroupSlot::ExternalScreen)),
                            ]),
                    ]),
            ]);
    }

    /**
     * @return list<Select>
     */
    private static function slotTabSchema(AdvertisementGroupSlot $slot): array
    {
        $field = $slot->formFieldKey();
        $filterField = $slot->typeFilterFieldKey();

        return [
            Select::make($filterField)
                ->label('Type')
                ->options([
                    '' => 'All types',
                    AdvertisementType::Image->value => AdvertisementType::Image->getLabel(),
                    AdvertisementType::Video->value => AdvertisementType::Video->getLabel(),
                ])
                ->default('')
                ->live()
                ->dehydrated(false)
                ->native(false),
            Select::make($field)
                ->label('Ad list')
                ->multiple()
                ->searchable()
                ->getSearchResultsUsing(function (Select $component, ?string $search) use ($slot): array {
                    $livewire = $component->getLivewire();
                    $root = $livewire->data ?? [];
                    $typeFilter = $root[$slot->typeFilterFieldKey()] ?? '';

                    $query = Advertisement::query()
                        ->when(filled($search), fn ($q) => $q->where('title', 'like', '%'.$search.'%'))
                        ->when($typeFilter !== '' && $typeFilter !== null, fn ($q) => $q->where('type', $typeFilter))
                        ->orderBy('title')
                        ->limit(50);

                    return $query->pluck('title', 'id')->all();
                })
                ->getOptionLabelsUsing(fn (array $values): array => Advertisement::query()
                    ->whereIn('id', $values)
                    ->pluck('title', 'id')
                    ->all())
                ->helperText(function (Get $get) use ($field): string {
                    $n = count((array) ($get($field) ?? []));

                    return "Selected {$n} advert(s). Search by ad name; use type filter if needed.";
                })
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('advertisements_count')
                    ->label('Assignments')
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
                    ->url(fn (AdvertisementGroup $record): string => self::getUrl('edit', ['record' => $record])),
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
            'index' => ListAdvertisementGroups::route('/'),
            'create' => CreateAdvertisementGroup::route('/create'),
            'edit' => EditAdvertisementGroup::route('/{record}/edit'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{data: array<string, mixed>, slots: array<string, list<int>>}
     */
    public static function pullSlotSelectionsFromFormData(array $data): array
    {
        $slots = [];

        foreach (AdvertisementGroupSlot::cases() as $slot) {
            $key = $slot->formFieldKey();
            $raw = (array) ($data[$key] ?? []);
            $slots[$slot->value] = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $raw)));
            unset($data[$key]);
        }

        return ['data' => $data, 'slots' => $slots];
    }
}
