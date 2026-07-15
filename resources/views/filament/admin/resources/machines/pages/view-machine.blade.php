@php
    use App\Filament\Admin\Resources\Machines\MachineResource;
    use App\Services\Filament\InterconnectedEntityService;

    $hub = app(InterconnectedEntityService::class);
    /** @var \App\Models\Machine $machine */
    $machine = $this->getRecord();
    $summary = $this->getSlotSummary();
    $slotRows = $this->getSlotRows();
    $related = $this->getRelatedRecords();
    $isOnline = $machine->isOnline();
@endphp

<x-filament-panels::page>
    <div class="vms-hub">
        <div class="vms-hub-hero">
            <div class="vms-hub-hero-accent" aria-hidden="true"></div>

            <div class="vms-hub-hero-main">
                <div class="vms-hub-hero-icon">
                    <x-filament::icon icon="heroicon-o-cpu-chip" class="h-7 w-7" />
                </div>

                <div class="vms-hub-hero-copy">
                    <p class="vms-hub-hero-kicker">Machine overview</p>
                    <p class="vms-hub-hero-title">{{ $machine->machine_name }}</p>
                    <p class="vms-hub-hero-subtitle">#{{ $machine->machine_number }}</p>
                </div>
            </div>

            <div class="vms-hub-hero-meta">
                <span @class([
                    'vms-hub-pill',
                    'vms-hub-pill-success' => $isOnline,
                    'vms-hub-pill-muted' => ! $isOnline,
                ])>
                    <span @class(['vms-hub-pill-dot', 'vms-hub-pill-dot-success' => $isOnline])></span>
                    {{ $isOnline ? 'Online' : 'Offline' }}
                </span>

                <span @class([
                    'vms-hub-pill',
                    'vms-hub-pill-info' => $machine->is_enabled,
                    'vms-hub-pill-muted' => ! $machine->is_enabled,
                ])>
                    {{ $machine->is_enabled ? 'Enabled' : 'Disabled' }}
                </span>

                <span class="vms-hub-pill vms-hub-pill-muted">
                    {{ $summary['total'] }} slot{{ $summary['total'] === 1 ? '' : 's' }}
                </span>
            </div>
        </div>

        @include('filament.admin.resources.partials.entity-hub-tabs', [
            'tabs' => [
                'overview' => ['label' => 'Overview', 'icon' => 'heroicon-o-home'],
                'slots' => ['label' => 'Slots & products (' . $summary['total'] . ')', 'icon' => 'heroicon-o-squares-2x2'],
                'related' => ['label' => 'Related records', 'icon' => 'heroicon-o-link'],
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
                        <h3 class="vms-hub-card-title">Machine details</h3>
                    </div>

                    <div class="vms-hub-fields">
                        <div class="vms-hub-field">
                            <span class="vms-hub-field-label">Number</span>
                            <span class="vms-hub-field-value vms-hub-field-mono">{{ $machine->machine_number }}</span>
                        </div>
                        <div class="vms-hub-field">
                            <span class="vms-hub-field-label">Status</span>
                            <span class="vms-hub-field-value">{{ $isOnline ? 'Online' : 'Offline' }} · {{ $machine->is_enabled ? 'Enabled' : 'Disabled' }}</span>
                        </div>
                        <div class="vms-hub-field">
                            <span class="vms-hub-field-label">Group</span>
                            <span class="vms-hub-field-value">{{ $machine->machineGroup?->name ?? '—' }}</span>
                        </div>
                        <div class="vms-hub-field">
                            <span class="vms-hub-field-label">Owner account</span>
                            <span class="vms-hub-field-value">{{ $machine->user?->account ?? '—' }}</span>
                        </div>
                        <div class="vms-hub-field">
                            <span class="vms-hub-field-label">Address</span>
                            <span class="vms-hub-field-value">{{ $machine->detailed_address ?: '—' }}</span>
                        </div>
                        <div class="vms-hub-field">
                            <span class="vms-hub-field-label">Last seen</span>
                            <span class="vms-hub-field-value">{{ $machine->last_seen_at?->diffForHumans() ?? 'Never' }}</span>
                        </div>
                    </div>
                </div>

                <div class="vms-hub-card">
                    <div class="vms-hub-card-heading">
                        <span class="vms-hub-card-icon">
                            <x-filament::icon icon="heroicon-o-chart-bar-square" class="h-5 w-5" />
                        </span>
                        <h3 class="vms-hub-card-title">Slot inventory at a glance</h3>
                    </div>

                    <div class="vms-hub-stats">
                        <div class="vms-hub-stat">
                            <span class="vms-hub-stat-value">{{ $summary['total'] }}</span>
                            <span class="vms-hub-stat-label">Total slots</span>
                        </div>
                        <div class="vms-hub-stat vms-hub-stat-success">
                            <span class="vms-hub-stat-value">{{ $summary['stocked'] }}</span>
                            <span class="vms-hub-stat-label">Stocked</span>
                        </div>
                        <div class="vms-hub-stat vms-hub-stat-warning">
                            <span class="vms-hub-stat-value">{{ $summary['low_stock'] }}</span>
                            <span class="vms-hub-stat-label">Low stock</span>
                        </div>
                        <div class="vms-hub-stat vms-hub-stat-danger">
                            <span class="vms-hub-stat-value">{{ $summary['empty'] }}</span>
                            <span class="vms-hub-stat-label">Empty</span>
                        </div>
                        <div class="vms-hub-stat">
                            <span class="vms-hub-stat-value">{{ $summary['unassigned'] }}</span>
                            <span class="vms-hub-stat-label">No product</span>
                        </div>
                        <div class="vms-hub-stat vms-hub-stat-danger">
                            <span class="vms-hub-stat-value">{{ $summary['fault'] }}</span>
                            <span class="vms-hub-stat-label">Fault</span>
                        </div>
                    </div>

                    <p class="vms-hub-hint">
                        See which product sits in each slot on the
                        <button type="button" wire:click="setActiveTab('slots')" class="vms-hub-inline-link">Slots & products</button>
                        tab — no need to leave this machine.
                    </p>
                </div>
            </div>
        @endif

        @if ($activeTab === 'slots')
            <div class="vms-hub-card">
                <div class="vms-hub-card-header">
                    <div class="vms-hub-card-heading">
                        <span class="vms-hub-card-icon">
                            <x-filament::icon icon="heroicon-o-squares-2x2" class="h-5 w-5" />
                        </span>
                        <h3 class="vms-hub-card-title">Products in each slot</h3>
                    </div>
                    <a href="{{ MachineResource::getUrl('slots', ['record' => $machine]) }}" class="vms-hub-action-btn">
                        Manage slots
                        <x-filament::icon icon="heroicon-m-arrow-right" class="h-4 w-4" />
                    </a>
                </div>

                @if ($slotRows->isEmpty())
                    <div class="vms-hub-empty-state">
                        <span class="vms-hub-empty-icon">
                            <x-filament::icon icon="heroicon-o-squares-plus" class="h-8 w-8" />
                        </span>
                        <p class="vms-hub-empty-title">No slots configured yet</p>
                        <p class="vms-hub-empty">Add slots to assign products to this machine.</p>
                        <a href="{{ MachineResource::getUrl('slots', ['record' => $machine]) }}" class="vms-hub-action-btn vms-hub-action-btn-primary">
                            Add slots
                        </a>
                    </div>
                @else
                    <div class="vms-hub-table-wrap">
                        <table class="vms-hub-table">
                            <thead>
                                <tr>
                                    <th>Slot #</th>
                                    <th>Product</th>
                                    <th>Stock</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($slotRows as $slot)
                                    <tr>
                                        <td><span class="vms-hub-slot-number">#{{ $slot->line_number }}</span></td>
                                        <td>
                                            @if ($slot->product)
                                                <a href="{{ $hub->productViewUrl($slot->product, 'machines') }}" class="vms-hub-row-link">{{ $slot->product->name }}</a>
                                            @else
                                                <span class="vms-hub-muted">— empty —</span>
                                            @endif
                                        </td>
                                        <td>{{ $slot->current_stock }} / {{ $slot->max_stock }}</td>
                                        <td>${{ number_format((float) $slot->price, 2) }}</td>
                                        <td>
                                            @if ($slot->is_fault)
                                                <span class="vms-hub-badge vms-hub-badge-danger">Fault</span>
                                            @elseif (! $slot->is_active)
                                                <span class="vms-hub-badge">Inactive</span>
                                            @elseif ($slot->current_stock === 0)
                                                <span class="vms-hub-badge vms-hub-badge-danger">Empty</span>
                                            @elseif ($slot->isLowStock())
                                                <span class="vms-hub-badge vms-hub-badge-warning">Low</span>
                                            @else
                                                <span class="vms-hub-badge vms-hub-badge-success">OK</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        @if ($activeTab === 'related')
            <div class="vms-hub-stack">
                <div class="vms-hub-card">
                    <div class="vms-hub-card-heading">
                        <span class="vms-hub-card-icon">
                            <x-filament::icon icon="heroicon-o-shopping-cart" class="h-5 w-5" />
                        </span>
                        <h3 class="vms-hub-card-title">Recent orders on this machine</h3>
                    </div>
                    @forelse ($related['orders'] as $order)
                        <div class="vms-hub-related-row">
                            <a href="{{ $hub->orderViewUrl($order) }}" class="vms-hub-row-link">{{ $order->product_name ?: 'Order' }}</a>
                            <span class="vms-hub-muted">${{ number_format((float) $order->prize_amount, 2) }} · {{ $order->created_at?->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="vms-hub-empty">No orders yet for this machine.</p>
                    @endforelse
                </div>

                <div class="vms-hub-card">
                    <div class="vms-hub-card-heading">
                        <span class="vms-hub-card-icon">
                            <x-filament::icon icon="heroicon-o-bell-alert" class="h-5 w-5" />
                        </span>
                        <h3 class="vms-hub-card-title">Alarms</h3>
                    </div>
                    @forelse ($related['alarms'] as $alarm)
                        <div class="vms-hub-related-row">
                            <span>{{ $alarm->title }}</span>
                            <span class="vms-hub-muted">{{ $alarm->triggered_at?->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="vms-hub-empty">No alarms recorded.</p>
                    @endforelse
                </div>

                <div class="vms-hub-card">
                    <div class="vms-hub-card-heading">
                        <span class="vms-hub-card-icon">
                            <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="h-5 w-5" />
                        </span>
                        <h3 class="vms-hub-card-title">Support tickets</h3>
                    </div>
                    @forelse ($related['tickets'] as $ticket)
                        <div class="vms-hub-related-row">
                            <a href="{{ $hub->supportTicketViewUrl($ticket) }}" class="vms-hub-row-link">{{ $ticket->work_order_number }}</a>
                            <span class="vms-hub-muted">{{ $ticket->status?->getLabel() }} · {{ $ticket->submitted_at?->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="vms-hub-empty">No support tickets for this machine.</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
