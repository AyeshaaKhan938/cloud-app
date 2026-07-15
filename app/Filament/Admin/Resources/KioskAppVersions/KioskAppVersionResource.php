<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KioskAppVersions;

use App\Filament\Admin\Resources\KioskAppVersions\Pages\ManageKioskAppVersions;
use App\Models\KioskAppVersion;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class KioskAppVersionResource extends Resource
{
    protected static ?string $model = KioskAppVersion::class;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 90;

    protected static ?string $modelLabel = 'kiosk app version';

    protected static ?string $pluralModelLabel = 'kiosk app versions';

    protected static ?string $navigationLabel = 'Kiosk Updates';

    protected static ?string $recordTitleAttribute = 'version_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Version')
                ->description('Upload the APK Codemagic built, then bump the version code so kiosks know to update.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('version_code')
                            ->label('Version code')
                            ->helperText('Integer. Must increase with every release (1, 2, 3, …). Matches versionCode in build.gradle.')
                            ->required()
                            ->integer()
                            ->minValue(1),
                        TextInput::make('version_name')
                            ->label('Version name')
                            ->helperText('Human-readable, e.g. "1.2.0". Shown in admin "what\'s new".')
                            ->required()
                            ->maxLength(32),
                    ]),
                    FileUpload::make('apk_url')
                        ->label('APK file')
                        ->helperText('Upload the .apk built by Codemagic. Max 100 MB.')
                        ->disk('public')
                        ->directory('kiosk-updates')
                        ->acceptedFileTypes(['application/vnd.android.package-archive'])
                        ->maxSize(100 * 1024)
                        ->preserveFilenames()
                        ->visibility('public')
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('apk_sha256')
                        ->label('SHA-256 (optional)')
                        ->helperText('Optional integrity check. Compute with: certutil -hashfile vmfs-kiosk.apk SHA256')
                        ->maxLength(64)
                        ->columnSpanFull(),
                    Textarea::make('release_notes')
                        ->label('Release notes')
                        ->helperText('What changed in this version. Shown to admins in the kiosk Update screen.')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),

            Section::make('Rollout')
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Only an active version is served to kiosks. Activate when ready to roll out.')
                            ->default(false),
                        Toggle::make('mandatory')
                            ->label('Mandatory')
                            ->helperText('Auto-install without waiting for the admin to tap "Install". Use for critical fixes.')
                            ->default(false),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('version_code')
                    ->label('Code')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('version_name')
                    ->label('Version')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                IconColumn::make('mandatory')
                    ->label('Mandatory')
                    ->boolean()
                    ->trueColor('warning'),
                TextColumn::make('apk_size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state): string => $state === null
                        ? '—'
                        : number_format($state / 1024 / 1024, 1).' MB'),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('version_code', 'desc')
            ->recordActions([
                EditAction::make(),
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
            'index' => ManageKioskAppVersions::route('/'),
        ];
    }
}
