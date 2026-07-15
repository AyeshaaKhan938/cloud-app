@php
    use App\Filament\Admin\Resources\Products\ProductLotteryResource;
    use App\Services\Filament\InterconnectedEntityService;

    $hub = app(InterconnectedEntityService::class);
    /** @var \App\Models\Product $product */
    $product = $this->getRecord();
    $deployments = $this->getMachineDeployments();
    $lotteries = $this->getLotteries();
    $machineCount = $deployments->pluck('machine_id')->unique()->count();
@endphp

<x-filament-panels::page>
    <div class="vms-hub">
        <div class="vms-hub-hero">
            <div class="vms-hub-hero-accent" aria-hidden="true"></div>

            <div class="vms-hub-hero-main">
                <div class="vms-hub-hero-icon">
                    <x-filament::icon icon="heroicon-o-shopping-bag" class="h-7 w-7" />
                </div>

                <div class="vms-hub-hero-copy">
                    <p class="vms-hub-hero-kicker">Product overview</p>
                    <p class="vms-hub-hero-title">{{ $product->name }}</p>
                    <p class="vms-hub-hero-subtitle">{{ $product->sku ? 'SKU ' . $product->sku : 'No SKU' }}</p>
                </div>
            </div>

            <div class="vms-hub-hero-meta">
                <span @class([
                    'vms-hub-pill',
                    'vms-hub-pill-success' => $product->is_active,
                    'vms-hub-pill-muted' => ! $product->is_active,
                ])>
                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                </span>

                <span class="vms-hub-pill vms-hub-pill-info">
                    ${{ number_format((float) $product->price, 2) }}
                </span>

                <span class="vms-hub-pill vms-hub-pill-muted">
                    {{ $machineCount }} machine{{ $machineCount === 1 ? '' : 's' }}
                </span>
            </div>
        </div>

        @include('filament.admin.resources.partials.entity-hub-tabs', [
            'tabs' => [
                'overview' => ['label' => 'Overview', 'icon' => 'heroicon-o-home'],
                'machines' => ['label' => 'On machines (' . $machineCount . ')', 'icon' => 'heroicon-o-cpu-chip'],
                'lotteries' => ['label' => 'Lotteries (' . $lotteries->count() . ')', 'icon' => 'heroicon-o-ticket'],
            ],
            'activeTab' => $activeTab,
        ])

        @if ($activeTab === 'overview')
            <div class="vms-hub-grid">
                <div class="vms-hub-card">
                    <div class="vms-hub-card-heading">
                        <span class="vms-hub-card-icon">
                            <x-filament::icon icon="heroicon-o-information-circle" class="h-5 w-5" />
                        </span>
                        <h3 class="vms-hub-card-title">Product details</h3>
                    </div>

                    <div class="vms-hub-fields">
                        <div class="vms-hub-field">
                            <span class="vms-hub-field-label">Name</span>
                            <span class="vms-hub-field-value">{{ $product->name }}</span>
                        </div>
                        <div class="vms-hub-field">
                            <span class="vms-hub-field-label">SKU</span>
                            <span class="vms-hub-field-value vms-hub-field-mono">{{ $product->sku ?: '—' }}</span>
                        </div>
                        <div class="vms-hub-field">
                            <span class="vms-hub-field-label">Category</span>
                            <span class="vms-hub-field-value">{{ $product->specification?->name ?? '—' }}</span>
                        </div>
                        <div class="vms-hub-field">
                            <span class="vms-hub-field-label">Retail price</span>
                            <span class="vms-hub-field-value">${{ number_format((float) $product->price, 2) }}</span>
                        </div>
                        <div class="vms-hub-field">
                            <span class="vms-hub-field-label">Cost</span>
                            <span class="vms-hub-field-value">${{ number_format((float) $product->cost, 2) }}</span>
                        </div>
                        <div class="vms-hub-field">
                            <span class="vms-hub-field-label">Status</span>
                            <span class="vms-hub-field-value">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                    </div>
                </div>

                <div class="vms-hub-card">
                    <div class="vms-hub-card-heading">
                        <span class="vms-hub-card-icon">
                            <x-filament::icon icon="heroicon-o-map-pin" class="h-5 w-5" />
                        </span>
                        <h3 class="vms-hub-card-title">Where this product lives</h3>
                    </div>

                    <p class="vms-hub-lead">
                        Loaded in <strong>{{ $deployments->count() }}</strong> slot(s) across
                        <strong>{{ $machineCount }}</strong> machine(s).
                    </p>

                    @if ($deployments->isNotEmpty())
                        <ul class="vms-hub-list">
                            @foreach ($deployments->take(5) as $slot)
                                <li>
                                    <a href="{{ $hub->machineViewUrl($slot->machine, 'slots') }}" class="vms-hub-row-link">
                                        {{ $slot->machine->machine_name }}
                                    </a>
                                    <span class="vms-hub-muted">slot #{{ $slot->line_number }} · stock {{ $slot->current_stock }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @if ($deployments->count() > 5)
                            <button type="button" wire:click="setActiveTab('machines')" class="vms-hub-inline-link">View all placements →</button>
                        @endif
                    @else
                        <div class="vms-hub-empty-state">
                            <span class="vms-hub-empty-icon">
                                <x-filament::icon icon="heroicon-o-cpu-chip" class="h-8 w-8" />
                            </span>
                            <p class="vms-hub-empty-title">Not on any machine yet</p>
                            <p class="vms-hub-empty">Assign this product to a machine slot from Manage slots.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if ($activeTab === 'machines')
            <div class="vms-hub-card">
                <div class="vms-hub-card-heading">
                    <span class="vms-hub-card-icon">
                        <x-filament::icon icon="heroicon-o-cpu-chip" class="h-5 w-5" />
                    </span>
                    <h3 class="vms-hub-card-title">Machine & slot placements</h3>
                </div>

                    <p class="vms-hub-hint vms-hub-hint-flush">
                    Every row links to the machine so you can see the full slot layout without hunting through menus.
                </p>

                @if ($deployments->isEmpty())
                    <p class="vms-hub-empty">This product is not loaded in any machine slot.</p>
                @else
                    <div class="vms-hub-table-wrap">
                        <table class="vms-hub-table">
                            <thead>
                                <tr>
                                    <th>Machine</th>
                                    <th>Machine #</th>
                                    <th>Slot</th>
                                    <th>Stock</th>
                                    <th>Slot price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($deployments as $slot)
                                    <tr>
                                        <td>
                                            <a href="{{ $hub->machineViewUrl($slot->machine, 'slots') }}" class="vms-hub-row-link">
                                                {{ $slot->machine->machine_name }}
                                            </a>
                                        </td>
                                        <td><span class="vms-hub-field-mono">{{ $slot->machine->machine_number }}</span></td>
                                        <td><span class="vms-hub-slot-number">#{{ $slot->line_number }}</span></td>
                                        <td>{{ $slot->current_stock }} / {{ $slot->max_stock }}</td>
                                        <td>${{ number_format((float) $slot->price, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        @if ($activeTab === 'lotteries')
            <div class="vms-hub-card">
                <div class="vms-hub-card-heading">
                    <span class="vms-hub-card-icon">
                        <x-filament::icon icon="heroicon-o-ticket" class="h-5 w-5" />
                    </span>
                    <h3 class="vms-hub-card-title">Linked lotteries</h3>
                </div>

                @forelse ($lotteries as $lottery)
                    <div class="vms-hub-related-row">
                        <a href="{{ ProductLotteryResource::getUrl(parameters: ['tableAction' => 'edit', 'tableActionRecord' => $lottery]) }}" class="vms-hub-row-link">{{ $lottery->name }}</a>
                        <span class="vms-hub-muted">{{ $lottery->machine_no ?: 'No machine' }}</span>
                    </div>
                @empty
                    <p class="vms-hub-empty">No lotteries linked to this product.</p>
                @endforelse
            </div>
        @endif
    </div>
</x-filament-panels::page>
