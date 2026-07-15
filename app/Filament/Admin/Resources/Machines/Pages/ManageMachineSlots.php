<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Machines\Pages;

use App\Enums\UserFeature;
use App\Filament\Admin\Resources\Machines\MachineResource;
use App\Models\MachineSlot;
use App\Models\User;
use App\Services\Filament\InterconnectedEntityService;
use App\Services\Users\FeatureAccess;
use App\Services\Users\UserCloudScope;
use App\Support\VendingSlotLayout;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable as InteractsWithTableConcern;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

final class ManageMachineSlots extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTableConcern;

    protected static string $resource = MachineResource::class;

    protected static ?string $breadcrumb = 'Slots';

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
        $this->mountInteractsWithTable();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(self::getResource()::canView($this->getRecord()), 403);
    }

    private function canManageSlots(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if (! app(FeatureAccess::class)->can($user, UserFeature::MachineSlots)) {
            return false;
        }

        return self::getResource()::canView($this->getRecord());
    }

    public function getTitle(): string|Htmlable
    {
        return 'Slots — '.$this->getRecord()->machine_name.' ('.$this->getRecord()->machine_number.')';
    }

    // ── Formulario de slot (compartido entre Create y Edit) ───────────────

    private function slotForm(): array
    {
        return [
            TextInput::make('line_number')
                ->label('Line / Slot #')
                ->helperText('Número físico del slot en la máquina (1, 2, 3…).')
                ->required()
                ->integer()
                ->minValue(1)
                ->maxValue(999),

            Select::make('product_id')
                ->label('Product')
                ->relationship(
                    'product',
                    'name',
                    fn (Builder $query): Builder => app(UserCloudScope::class)->scopeProducts($query),
                )
                ->searchable()
                ->preload()
                ->nullable()
                ->helperText('Leave empty if this slot has no product assigned yet.'),

            TextInput::make('price')
                ->label('Price ($)')
                ->helperText('Precio de venta en este slot. Puede diferir del precio base del producto.')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->required()
                ->default(0),

            TextInput::make('max_stock')
                ->label('Max stock')
                ->integer()
                ->minValue(1)
                ->default(10)
                ->required(),

            TextInput::make('current_stock')
                ->label('Current stock')
                ->integer()
                ->minValue(0)
                ->default(0)
                ->required(),

            TextInput::make('stock_alarm_threshold')
                ->label('Low stock alert at')
                ->helperText('Alerta cuando el stock baja de este número.')
                ->integer()
                ->minValue(0)
                ->default(3)
                ->required(),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->inline(false),

            Toggle::make('is_fault')
                ->label('Fault')
                ->helperText('Marca si el slot tiene una falla física.')
                ->default(false)
                ->inline(false),
        ];
    }

    // ── Header actions ────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add slot')
                ->icon(Heroicon::OutlinedPlus)
                ->model(MachineSlot::class)
                ->authorize(fn (): bool => $this->canManageSlots())
                ->form($this->slotForm())
                ->mutateFormDataUsing(function (array $data): array {
                    $data['machine_id'] = $this->getRecord()->getKey();

                    return $data;
                }),

            Action::make('back')
                ->label('Machine overview')
                ->url(fn (): string => ViewMachine::getUrl(['record' => $this->getRecord()]))
                ->color('gray'),
        ];
    }

    // ── Content ───────────────────────────────────────────────────────────

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    // ── Table ─────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('line_number')
                    ->label('Line #')
                    ->sortable()
                    ->width('80px'),

                TextColumn::make('client_number')
                    ->label('Client #')
                    ->state(
                        fn (MachineSlot $record): string => (string) (VendingSlotLayout::hardwareLineToClientNumber($record->line_number) ?? '—'),
                    )
                    ->width('80px'),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->placeholder('— empty —')
                    ->url(fn (MachineSlot $record): ?string => $record->product
                        ? app(InterconnectedEntityService::class)->productViewUrl($record->product, 'machines')
                        : null)
                    ->color(fn (MachineSlot $record): ?string => $record->product ? 'primary' : null),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('current_stock')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn (MachineSlot $record): string => match (true) {
                        $record->current_stock === 0 => 'danger',
                        $record->isLowStock() => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(
                        fn (MachineSlot $record): string => "{$record->current_stock} / {$record->max_stock}"
                    ),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                IconColumn::make('is_fault')
                    ->label('Fault')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => $this->canManageSlots())
                    ->form($this->slotForm()),

                DeleteAction::make()
                    ->authorize(fn (): bool => $this->canManageSlots()),
            ])
            ->defaultSort('line_number')
            ->paginated([25, 50, 100]);
    }

    protected function getTableQuery(): Builder
    {
        return MachineSlot::query()
            ->where('machine_id', $this->getRecord()->getKey())
            ->with('product');
    }
}
