<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PushRecords;

use App\Enums\PushMethod;
use App\Filament\Admin\Concerns\RegistersForPlatformAdmins;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Filament\Admin\Resources\PushRecords\Pages\ManagePushRecords;
use App\Models\PushRecord;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class PushRecordResource extends Resource
{
    use RegistersForPlatformAdmins;

    protected static ?string $model = PushRecord::class;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::PlatformOps;

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'push record';

    protected static ?string $pluralModelLabel = 'push records';

    protected static ?string $navigationLabel = 'Push record';

    protected static ?string $recordTitleAttribute = 'message_title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('message_title')
                            ->label('Message title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('push_method')
                            ->label('Push method')
                            ->options(PushMethod::class)
                            ->native(false)
                            ->searchable()
                            ->required(),
                        TextInput::make('publisher_account')
                            ->label('Publisher account')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        DateTimePicker::make('pushed_at')
                            ->label('Push time')
                            ->required()
                            ->seconds(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('message_title')
                    ->label('Message title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('push_method')
                    ->label('Push method')
                    ->formatStateUsing(fn (?PushMethod $state): string => $state?->getLabel() ?? '')
                    ->sortable(),
                TextColumn::make('publisher_account')
                    ->label('Publisher account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pushed_at')
                    ->label('Push time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('message_title')
                    ->label('Message title')
                    ->form([
                        TextInput::make('title')
                            ->label('Message title')
                            ->placeholder('Message title'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['title'] ?? null),
                            fn (Builder $q): Builder => $q->where('message_title', 'like', '%'.$data['title'].'%')
                        );
                    }),
                SelectFilter::make('push_method')
                    ->label('Push method')
                    ->options(PushMethod::class),
            ])
            ->deferFilters(false)
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit push record'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No push records')
            ->emptyStateDescription('Remote content pushes to kiosks will be logged here.')
            ->emptyStateIcon(Heroicon::OutlinedPaperAirplane);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePushRecords::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return self::shouldRegisterNavigation();
    }
}
