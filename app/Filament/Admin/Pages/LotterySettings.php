<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\LotterySetting;
use BackedEnum;
use Filament\Actions\Action;
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
final class LotterySettings extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $navigationLabel = 'Lottery Settings';

    protected static string|UnitEnum|null $navigationGroup = 'Products';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'lottery-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $this->form->fill(LotterySetting::current()->attributesToArray());
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            LotterySetting::current()->update($data);
            LotterySetting::forgetCurrentCache();
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
            ->model(LotterySetting::current())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tier A — rare prize')
                    ->description('The Grand Prize bucket. Slots marked "Tier A" in any machine fall into this group.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('tier_a_name')
                                ->label('Tier A name')
                                ->helperText('Shown to the customer on the win screen, e.g. "Grand Prize".')
                                ->required()
                                ->maxLength(64),

                            TextInput::make('tier_a_weight')
                                ->label('Tier A weight')
                                ->helperText('Higher weight = more likely. With weights 1 and 49, Tier A is drawn 1 time out of every 50.')
                                ->integer()
                                ->minValue(0)
                                ->maxValue(100000)
                                ->required(),
                        ]),
                    ]),

                Section::make('Tier B — common prize')
                    ->description('The Consolation bucket. Slots marked "Tier B" in any machine fall into this group.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('tier_b_name')
                                ->label('Tier B name')
                                ->helperText('Shown to the customer on the win screen, e.g. "Consolation".')
                                ->required()
                                ->maxLength(64),

                            TextInput::make('tier_b_weight')
                                ->label('Tier B weight')
                                ->helperText('Default 49. The customer\'s odds for this tier are tier_b_weight / (tier_a_weight + tier_b_weight).')
                                ->integer()
                                ->minValue(0)
                                ->maxValue(100000)
                                ->required(),
                        ]),
                    ]),

                Section::make('How the lottery works')
                    ->description(
                        'When a customer redeems a code, the kiosk picks a tier using these weights (default 1:49 = 2% Tier A, 98% Tier B), '.
                        'then picks a random in-stock slot from the chosen tier. To add products to a tier, edit the slots of any machine and set the "Lottery tier" field.'
                    )
                    ->schema([]),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Lottery Settings';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
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
