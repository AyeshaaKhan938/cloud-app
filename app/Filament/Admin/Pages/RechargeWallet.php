<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\AuthorizesFeaturePage;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

final class RechargeWallet extends Page
{
    use AuthorizesFeaturePage;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?string $navigationLabel = 'Recharge Wallet';

    protected static string|UnitEnum|null $navigationGroup = 'Wallet';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Recharge Wallet';

    protected static ?string $slug = 'recharge-wallet';

    protected string $view = 'filament.admin.pages.recharge-wallet';

    public function getSubheading(): ?string
    {
        return 'Add funds to your wallet balance for renewals and platform services.';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Recharge Wallet';
    }

    public function walletTopUpAction(): Action
    {
        return Action::make('walletTopUp')
            ->label('Top up')
            ->modalHeading('Wallet topup')
            ->modalSubmitActionLabel('Next step')
            ->form([
                TextInput::make('amount')
                    ->label('Please enter the amount you want to top up ($)')
                    ->required()
                    ->numeric()
                    ->minValue(100)
                    ->default(0)
                    ->prefix('$')
                    ->validationMessages([
                        'min' => 'Minimum recharge amount: $100',
                        'amount.min' => 'Minimum recharge amount: $100',
                    ]),
            ])
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();
                $amount = round((float) $data['amount'], 2);
                $user->wallet_recharge_pending = round((float) $user->wallet_recharge_pending + $amount, 2);
                $user->save();

                Notification::make()
                    ->success()
                    ->title('Top-up request recorded')
                    ->body('The amount will appear under recharge pending until it is reviewed.')
                    ->send();
            });
    }

    protected static function requiredUserFeature(): UserFeature
    {
        return UserFeature::Wallet;
    }
}
