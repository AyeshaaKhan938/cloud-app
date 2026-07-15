<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\MachineSlot;
use App\Models\Product;
use App\Models\ProductLottery;
use App\Services\Filament\InterconnectedEntityService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

final class ViewProduct extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProductResource::class;

    protected static ?string $title = 'Product';

    protected static ?string $breadcrumb = 'Overview';

    protected string $view = 'filament.admin.resources.products.pages.view-product';

    public string $activeTab = 'overview';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(ProductResource::canView($this->getRecord()), 403);

        $tab = request()->query('tab');

        if (is_string($tab) && in_array($tab, ['overview', 'machines', 'lotteries'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function getTitle(): string|Htmlable
    {
        /** @var Product $product */
        $product = $this->getRecord();

        return $product->name;
    }

    public function getSubheading(): ?string
    {
        /** @var Product $product */
        $product = $this->getRecord();

        $sku = filled($product->sku) ? 'SKU '.$product->sku.' · ' : '';

        return $sku.$this->tabLabel($this->activeTab);
    }

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['overview', 'machines', 'lotteries'], true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    /**
     * @return Collection<int, MachineSlot>
     */
    public function getMachineDeployments()
    {
        /** @var Product $product */
        $product = $this->getRecord();

        return app(InterconnectedEntityService::class)->productMachineDeployments($product);
    }

    /**
     * @return Collection<int, ProductLottery>
     */
    public function getLotteries()
    {
        /** @var Product $product */
        $product = $this->getRecord();

        return app(InterconnectedEntityService::class)->productLotteries($product);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('All products')
                ->url(ProductResource::getUrl())
                ->color('gray'),
        ];
    }

    private function tabLabel(string $tab): string
    {
        return match ($tab) {
            'machines' => 'Machine placements',
            'lotteries' => 'Lotteries',
            default => 'Overview',
        };
    }
}
