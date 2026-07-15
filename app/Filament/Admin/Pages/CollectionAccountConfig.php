<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\UserFeature;
use App\Filament\Admin\Concerns\AuthorizesFeaturePage;
use App\Models\PaymentCollectionConfig;
use App\Services\PaymentGatewayFormBuilder;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use UnitEnum;

final class CollectionAccountConfig extends Page
{
    use AuthorizesFeaturePage;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Collection account config';

    protected static string|UnitEnum|null $navigationGroup = 'Wallet';

    protected static ?int $navigationSort = 93;

    protected static ?string $title = 'Collection account config';

    protected static ?string $slug = 'collection-account-config';

    protected string $view = 'filament.admin.pages.collection-account-config';

    public function getSubheading(): ?string
    {
        return 'Payment gateway credentials used for wallet top-ups and collections.';
    }

    public string $search = '';

    public function getHeading(): string|Htmlable|null
    {
        return 'Collection account config';
    }

    /**
     * @return list<array{slug: string, title: string}>
     */
    public function getGatewayDefinitions(): array
    {
        /** @var list<array{slug: string, title: string}> $gateways */
        $gateways = config('payment_gateways', []);

        return $gateways;
    }

    /**
     * @return list<array{slug: string, title: string}>
     */
    public function getFilteredGateways(): array
    {
        $needle = mb_strtolower(trim($this->search));
        $all = $this->getGatewayDefinitions();

        if ($needle === '') {
            return $all;
        }

        return array_values(array_filter(
            $all,
            static fn (array $g): bool => str_contains(mb_strtolower($g['title']), $needle)
        ));
    }

    public function getNotConfiguredCount(): int
    {
        $count = 0;
        foreach ($this->getFilteredGateways() as $gateway) {
            if (! PaymentCollectionConfig::isConfigured($gateway['slug'])) {
                $count++;
            }
        }

        return $count;
    }

    public function isGatewayConfigured(string $slug): bool
    {
        return PaymentCollectionConfig::isConfigured($slug);
    }

    /**
     * Opens the configure modal for a gateway. Arguments are passed from PHP so
     * {@see mountAction} is not invoked via wire:click with embedded @js(), which breaks
     * HTML attribute quoting (JSON.parse('...') contains characters that terminate the attribute).
     */
    public function openGatewayConfiguration(string $slug): void
    {
        $this->assertValidGatewaySlug($slug);
        $this->mountAction('configureGateway', ['slug' => $slug]);
    }

    public function configureGatewayAction(): Action
    {
        return Action::make('configureGateway')
            ->modalHeading(function (Action $action): string {
                $slug = (string) ($action->getArguments()['slug'] ?? '');
                $title = $this->resolveGatewayTitle($slug);

                return $title.' — Configure';
            })
            ->modalSubmitActionLabel('Confirm')
            ->modalCancelActionLabel('Cancel')
            ->modalWidth('lg')
            ->schema(function (Action $action): array {
                $slug = (string) ($action->getArguments()['slug'] ?? '');
                $this->assertValidGatewaySlug($slug);

                return PaymentGatewayFormBuilder::schemaFor($slug);
            })
            ->fillForm(function (Action $action): array {
                $slug = (string) ($action->getArguments()['slug'] ?? '');
                $this->assertValidGatewaySlug($slug);
                $row = PaymentCollectionConfig::query()->where('gateway_slug', $slug)->first();

                return is_array($row?->payload) ? $row->payload : [];
            })
            ->action(function (array $data, Action $action): void {
                $slug = (string) ($action->getArguments()['slug'] ?? '');
                $this->assertValidGatewaySlug($slug);

                PaymentCollectionConfig::query()->updateOrCreate(
                    ['gateway_slug' => $slug],
                    ['payload' => $data]
                );

                Notification::make()
                    ->success()
                    ->title('Configuration saved')
                    ->send();
            });
    }

    private function resolveGatewayTitle(string $slug): string
    {
        foreach ($this->getGatewayDefinitions() as $gateway) {
            if ($gateway['slug'] === $slug) {
                return $gateway['title'];
            }
        }

        return 'Payment gateway';
    }

    private function assertValidGatewaySlug(string $slug): void
    {
        $valid = array_column($this->getGatewayDefinitions(), 'slug');

        if (! in_array($slug, $valid, true)) {
            throw new InvalidArgumentException('Invalid gateway slug.');
        }
    }

    protected static function requiredUserFeature(): UserFeature
    {
        return UserFeature::Wallet;
    }
}
