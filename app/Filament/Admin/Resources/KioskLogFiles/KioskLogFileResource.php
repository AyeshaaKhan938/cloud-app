<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KioskLogFiles;

use App\Filament\Admin\Resources\KioskLogFiles\Pages\ManageKioskLogFiles;
use App\Models\KioskLogFile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Admin → System → Kiosk Logs.
 *
 * Browses log files uploaded from kiosks via the in-app "Send to
 * vms-cloud" button. Each row is one upload; download action streams
 * the .log file to the operator's browser.
 *
 * Files live on the public disk at storage/app/kiosk-logs/{machineNo}/.
 */
final class KioskLogFileResource extends Resource
{
    protected static ?string $model = KioskLogFile::class;

    protected static string|UnitEnum|null $navigationGroup = 'System maintenance';

    protected static ?int $navigationSort = 95;

    protected static ?string $modelLabel = 'kiosk log file';

    protected static ?string $pluralModelLabel = 'kiosk logs';

    protected static ?string $navigationLabel = 'Kiosk Logs';

    protected static ?string $recordTitleAttribute = 'original_filename';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Upload')
                ->schema([
                    TextInput::make('machine_number')
                        ->label('Machine number')
                        ->disabled(),
                    TextInput::make('original_filename')
                        ->label('Filename')
                        ->disabled(),
                    TextInput::make('size_bytes')
                        ->label('Size (bytes)')
                        ->disabled(),
                    TextInput::make('app_version')
                        ->label('App version')
                        ->disabled(),
                    TextInput::make('sha256')
                        ->label('SHA-256')
                        ->disabled(),
                    Textarea::make('notes')
                        ->label('Notes')
                        ->helperText('Free-form notes. Add context the operator can use later.')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('machine_number')
                    ->label('Machine')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('original_filename')
                    ->label('File')
                    ->searchable(),
                TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn (int $state): string => match (true) {
                        $state < 1024 => $state.' B',
                        $state < 1024 * 1024 => round($state / 1024, 1).' KB',
                        default => round($state / (1024 * 1024), 1).' MB',
                    }
                    )
                    ->sortable(),
                TextColumn::make('app_version')
                    ->label('App ver')
                    ->toggleable(),
                TextColumn::make('sha256')
                    ->label('SHA-256')
                    ->limit(12)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('machine_number')
                    ->schema([
                        TextInput::make('value')
                            ->label('Machine number contains'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }

                        return $query->where('machine_number', 'like', "%{$value}%");
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('primary')
                    ->action(function (KioskLogFile $record): StreamedResponse {
                        return Storage::download(
                            $record->stored_path,
                            $record->original_filename,
                        );
                    }),
                ViewAction::make(),
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
            'index' => ManageKioskLogFiles::route('/'),
        ];
    }
}
