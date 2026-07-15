<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\InformationStorageRecords;

use App\Enums\InformationStorageCollectionMethod;
use App\Enums\InformationStorageRuleType;
use App\Filament\Admin\Concerns\RegistersForPlatformAdmins;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Filament\Admin\Resources\InformationStorageRecords\Pages\ManageInformationStorageRecords;
use App\Models\InformationStorageRecord;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

final class InformationStorageRecordResource extends Resource
{
    use RegistersForPlatformAdmins;

    protected static ?string $model = InformationStorageRecord::class;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::PlatformOps;

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'information record';

    protected static ?string $pluralModelLabel = 'information records';

    protected static ?string $navigationLabel = 'Information storage';

    protected static ?string $recordTitleAttribute = 'user_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('collection_method')
                                    ->label('Collection method')
                                    ->placeholder('Please select')
                                    ->options(InformationStorageCollectionMethod::class)
                                    ->native(false)
                                    ->searchable()
                                    ->required()
                                    ->default(InformationStorageCollectionMethod::MemberCard),
                                TextInput::make('promotion_plan')
                                    ->label('Promotion plan')
                                    ->placeholder('Optional')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('ic_card_number')
                                    ->label('IC card number')
                                    ->placeholder('Please enter IC card number')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true)
                                    ->columnSpanFull(),
                                TextInput::make('user_name')
                                    ->label('User name')
                                    ->placeholder('Please enter user name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('account')
                                    ->label('Account')
                                    ->placeholder('Optional')
                                    ->maxLength(255),
                                TextInput::make('mobile_number')
                                    ->label('Mobile number')
                                    ->tel()
                                    ->maxLength(50),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                        ToggleButtons::make('rule_type')
                            ->label('Rule settings')
                            ->options(InformationStorageRuleType::class)
                            ->inline()
                            ->live()
                            ->required()
                            ->default(InformationStorageRuleType::Points),
                        TextInput::make('points')
                            ->label('Points')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01)
                            ->required(fn (Get $get): bool => $get('rule_type') === InformationStorageRuleType::Points->value)
                            ->visible(fn (Get $get): bool => $get('rule_type') === InformationStorageRuleType::Points->value),
                        TextInput::make('available_times_in_cycle')
                            ->label('Available times in cycle')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->required(fn (Get $get): bool => $get('rule_type') === InformationStorageRuleType::Times->value)
                            ->visible(fn (Get $get): bool => $get('rule_type') === InformationStorageRuleType::Times->value),
                        TextInput::make('used_times_in_cycle')
                            ->label('Used times in cycle')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->required(fn (Get $get): bool => $get('rule_type') === InformationStorageRuleType::Times->value)
                            ->visible(fn (Get $get): bool => $get('rule_type') === InformationStorageRuleType::Times->value),
                        Textarea::make('remarks')
                            ->label('Remark')
                            ->placeholder('Optional remarks')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_name')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account')
                    ->label('Account')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? $state : '—')
                    ->toggleable(),
                TextColumn::make('ic_card_number')
                    ->label('IC card no.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mobile_number')
                    ->label('Mobile')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? $state : '—'),
                TextColumn::make('points')
                    ->label('Points')
                    ->sortable()
                    ->formatStateUsing(function (TextColumn $column, $state): string {
                        $record = $column->getRecord();
                        if (! $record instanceof InformationStorageRecord) {
                            return '';
                        }

                        return $record->rule_type === InformationStorageRuleType::Points
                            ? number_format((float) $state, 2)
                            : '—';
                    }),
                TextColumn::make('available_times_in_cycle')
                    ->label('Available times in cycle')
                    ->sortable()
                    ->formatStateUsing(function (TextColumn $column, $state): string {
                        $record = $column->getRecord();
                        if (! $record instanceof InformationStorageRecord) {
                            return '';
                        }

                        return $record->rule_type === InformationStorageRuleType::Times
                            ? (string) $state
                            : '—';
                    }),
                TextColumn::make('used_times_in_cycle')
                    ->label('Used times in cycle')
                    ->sortable()
                    ->formatStateUsing(function (TextColumn $column, $state): string {
                        $record = $column->getRecord();
                        if (! $record instanceof InformationStorageRecord) {
                            return '';
                        }

                        return $record->rule_type === InformationStorageRuleType::Times
                            ? (string) $state
                            : '—';
                    }),
                TextColumn::make('promotion_plan')
                    ->label('Promotion plan')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Entry time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('remarks')
                    ->label('Remarks')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('promotion_plan')
                    ->label('Promotion plan')
                    ->options(fn (): array => InformationStorageRecord::query()
                        ->whereNotNull('promotion_plan')
                        ->where('promotion_plan', '!=', '')
                        ->distinct()
                        ->orderBy('promotion_plan')
                        ->pluck('promotion_plan', 'promotion_plan')
                        ->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('rule_type')
                    ->label('Rule type')
                    ->options(InformationStorageRuleType::class),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit information')
                    ->modalSubmitActionLabel('Submit')
                    ->mutateFormDataUsing(fn (array $data): array => self::normalizeRuleFields($data)),
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
            'index' => ManageInformationStorageRecords::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return self::shouldRegisterNavigation();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeRuleFields(array $data): array
    {
        $rule = $data['rule_type'] ?? null;
        $ruleValue = $rule instanceof InformationStorageRuleType ? $rule->value : $rule;

        if ($ruleValue === InformationStorageRuleType::Points->value) {
            $data['available_times_in_cycle'] = null;
            $data['used_times_in_cycle'] = 0;
        }

        if ($ruleValue === InformationStorageRuleType::Times->value) {
            $data['points'] = null;
        }

        return $data;
    }
}
