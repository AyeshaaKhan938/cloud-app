<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\RegistersForPlatformAdmins;
use App\Filament\Admin\Navigation\AdminNavigationGroups;
use App\Models\BrandSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
final class BrandSettings extends Page
{
    use CanUseDatabaseTransactions;
    use RegistersForPlatformAdmins;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Brand & appearance';

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroups::Brand;

    protected static ?int $navigationSort = 100;

    protected static ?string $title = 'Brand';

    protected static ?string $slug = 'brand';

    public function getSubheading(): ?string
    {
        return 'Logo, colors, and kiosk-facing content for the VMFS brand.';
    }

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);

        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $this->form->fill(BrandSetting::current()->attributesToArray());
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            BrandSetting::current()->update($data);
            BrandSetting::forgetCurrentCache();
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        Notification::make()
            ->success()
            ->title('Saved')
            ->send();

        $this->fillForm();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model(BrandSetting::current())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand images')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                FileUpload::make('homepage_logo_path')
                                    ->label('Homepage logo')
                                    ->disk('public')
                                    ->directory('brand')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(5120)
                                    ->helperText('Suggest size: 200 × 52 px.')
                                    ->nullable(),
                                FileUpload::make('homepage_icon_path')
                                    ->label('Homepage ICON')
                                    ->disk('public')
                                    ->directory('brand')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(2048)
                                    ->helperText('Suggest size: 52 × 52 px.')
                                    ->nullable(),
                                FileUpload::make('homepage_promotion_image_path')
                                    ->label('Homepage promotion image')
                                    ->disk('public')
                                    ->directory('brand')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(10240)
                                    ->helperText('Suggest size: 500 × 318 px.')
                                    ->nullable(),
                                FileUpload::make('homepage_background_image_path')
                                    ->label('Homepage background image')
                                    ->disk('public')
                                    ->directory('brand')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(15360)
                                    ->helperText('Suggest size: 1920 × 1080 px.')
                                    ->nullable(),
                                FileUpload::make('device_startup_animation_path')
                                    ->label('Device startup animation')
                                    ->disk('public')
                                    ->directory('brand/animations')
                                    ->acceptedFileTypes([
                                        'application/zip',
                                        'application/x-zip-compressed',
                                    ])
                                    ->maxSize(51200)
                                    ->helperText('Only .zip format files can be uploaded.')
                                    ->nullable(),
                                FileUpload::make('homepage_bottom_logo_path')
                                    ->label('LOGO at the bottom of Home Page')
                                    ->disk('public')
                                    ->directory('brand')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(5120)
                                    ->helperText('Suggest size: 130 × 52 px.')
                                    ->nullable(),
                                FileUpload::make('device_bottom_logo_path')
                                    ->label('Device bottom LOGO')
                                    ->disk('public')
                                    ->directory('brand')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(10240)
                                    ->helperText('Suggest size: 160 × 227 px.')
                                    ->nullable(),
                            ]),
                    ]),
                Section::make('General')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('default_webpage_title')
                                    ->label('Default webpage title')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('homepage_logo_jump_link')
                                    ->label('Homepage logo jump link')
                                    ->maxLength(2048)
                                    ->nullable(),
                                Select::make('device_default_ad_eliminates_logo')
                                    ->label('Device default ad eliminates logo')
                                    ->boolean('Yes', 'No')
                                    ->native(false)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Homepage footer')
                    ->schema([
                        RichEditor::make('homepage_footer_html')
                            ->label('Text at the bottom of the Homepage')
                            ->columnSpanFull()
                            ->maxLength(50_000)
                            ->nullable(),
                    ]),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Brand';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Start;
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }
}
