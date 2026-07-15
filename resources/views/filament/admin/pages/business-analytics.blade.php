@php
    $report = $this->getReport();
    $viewMode = $this->getViewMode();
    $portfolio = $report['portfolio'];
    $perUnit = $report['per_unit'];
    $period = $report['period'];
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            {{ $this->form }}
        </div>

        <div class="ba-period-strip">
            <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4" />
            <span>
                Period: <strong>{{ $period['from'] }}</strong> → <strong>{{ $period['to'] }}</strong>
                ({{ $period['days'] }} days) ·
                <strong>{{ $report['machine_count'] }}</strong> VM(s) in scope
            </span>
        </div>

        @if ($viewMode === 'portfolio')
            @include('filament.admin.pages.partials.business-analytics-metrics', ['metrics' => $portfolio, 'title' => 'Combined portfolio'])
        @else
            @foreach ($perUnit as $unit)
                <div class="ba-unit-card">
                    <div class="ba-unit-header">
                        <div>
                            <div class="ba-unit-title">{{ $unit['machine']['name'] }}</div>
                            <div class="text-sm text-gray-500">{{ $unit['machine']['number'] }}</div>
                        </div>
                        <span class="ba-badge {{ $unit['machine']['is_enabled'] ? 'ba-badge-green' : 'ba-badge-gray' }}">
                            {{ $unit['machine']['is_enabled'] ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                    @include('filament.admin.pages.partials.business-analytics-metrics', ['metrics' => $unit['metrics'], 'title' => null])
                </div>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
