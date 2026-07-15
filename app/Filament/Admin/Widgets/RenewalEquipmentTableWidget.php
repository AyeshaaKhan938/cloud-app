<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\RenewalEquipment;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class RenewalEquipmentTableWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Renewal list')
            ->description(fn (): string => $this->renewalSelectionSummary())
            ->query(
                RenewalEquipment::query()
                    ->where('user_id', auth()->id())
            )
            ->columns([
                TextColumn::make('device_name')
                    ->label('Device name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('equipment_number')
                    ->label('Equipment number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expiration time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('yearly_renewal_amount')
                    ->label('Renewal amount')
                    ->money('USD')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('device_name')
                    ->label('Equipment name')
                    ->form([
                        TextInput::make('value')
                            ->label('Equipment name')
                            ->placeholder('Equipment name'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('device_name', 'like', '%'.$data['value'].'%')
                        );
                    }),
                Filter::make('equipment_number')
                    ->label('Equipment number')
                    ->form([
                        TextInput::make('value')
                            ->label('Equipment number')
                            ->placeholder('Equipment number'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $q): Builder => $q->where('equipment_number', 'like', '%'.$data['value'].'%')
                        );
                    }),
            ])
            ->deferFilters(false)
            ->selectable()
            ->toolbarActions([
                Action::make('paypalPayment')
                    ->label('PayPal payment')
                    ->color('info')
                    ->action(fn () => $this->handlePaymentPlaceholder('PayPal')),
                Action::make('stripePayment')
                    ->label('Stripe payment')
                    ->color('primary')
                    ->action(fn () => $this->handlePaymentPlaceholder('Stripe')),
                Action::make('offlineRemittance')
                    ->label('Offline remittance authentication')
                    ->color('success')
                    ->action(fn () => $this->handlePaymentPlaceholder('Offline remittance')),
                Action::make('balanceDeduction')
                    ->label('Balance deduction')
                    ->color('warning')
                    ->action(fn () => $this->handlePaymentPlaceholder('Balance deduction')),
            ])
            ->paginationMode(PaginationMode::Default);
    }

    protected function renewalSelectionSummary(): string
    {
        $records = $this->getSelectedTableRecords();
        $count = $records->count();
        $sum = $records->sum(fn (RenewalEquipment $e): float => (float) $e->yearly_renewal_amount);

        return sprintf(
            'Selected: %d Devices are renewed for one year. Here are the fees... $%s',
            $count,
            number_format($sum, 2)
        );
    }

    protected function handlePaymentPlaceholder(string $channel): void
    {
        $records = $this->getSelectedTableRecords();
        if ($records->isEmpty()) {
            Notification::make()
                ->title('No devices selected')
                ->body('Select one or more devices in the table before choosing a payment option.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title($channel)
            ->body('Payment flow is not connected yet. Selected devices: '.$records->count().'.')
            ->info()
            ->send();
    }
}
