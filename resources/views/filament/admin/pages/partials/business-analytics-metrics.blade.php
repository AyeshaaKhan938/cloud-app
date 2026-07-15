@php
    $m = $metrics;
@endphp

@if (! empty($title))
    <h3 class="text-lg font-bold mb-4">{{ $title }}</h3>
@endif

{{-- Sales --}}
<p class="ba-section-title">Sales</p>
<div class="ba-grid ba-grid-4 mb-6">
    <div class="ba-card">
        <p class="ba-kpi-label">Revenue</p>
        <p class="ba-kpi-value">${{ number_format($m['sales']['revenue'], 2) }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Completed orders</p>
        <p class="ba-kpi-value">{{ number_format($m['sales']['orders']) }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Avg order value</p>
        <p class="ba-kpi-value">${{ number_format($m['sales']['avg_order_value'], 2) }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Forecast (30 days)</p>
        <p class="ba-kpi-value">${{ number_format($m['forecast']['next_30_days_revenue'], 2) }}</p>
        <p class="ba-kpi-sub">~{{ number_format($m['forecast']['next_30_days_orders']) }} orders</p>
    </div>
</div>

{{-- P&L / Costs --}}
<p class="ba-section-title">P&amp;L basics</p>
<div class="ba-grid ba-grid-4 mb-6">
    <div class="ba-card">
        <p class="ba-kpi-label">Gross revenue</p>
        <p class="ba-kpi-value">${{ number_format($m['pnl']['gross_revenue'], 2) }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">COGS (product cost)</p>
        <p class="ba-kpi-value">${{ number_format($m['pnl']['cogs'], 2) }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Gross profit</p>
        <p class="ba-kpi-value">${{ number_format($m['pnl']['gross_profit'], 2) }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Gross margin</p>
        <p class="ba-kpi-value">{{ $m['pnl']['gross_margin_label'] }}</p>
    </div>
</div>

{{-- Transactions --}}
<p class="ba-section-title">Transactions</p>
<div class="ba-grid ba-grid-4 mb-6">
    <div class="ba-card">
        <p class="ba-kpi-label">Total transactions</p>
        <p class="ba-kpi-value">{{ number_format($m['transactions']['total']) }}</p>
        <p class="ba-kpi-sub">{{ number_format($m['transactions']['success_rate'], 1) }}% success</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Completed</p>
        <p class="ba-kpi-value">{{ number_format($m['transactions']['completed']) }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Failed</p>
        <p class="ba-kpi-value">{{ number_format($m['transactions']['failed']) }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Cash / Card / Other</p>
        <p class="ba-kpi-value" style="font-size:1rem;">
            {{ $m['transactions']['cash'] }} / {{ $m['transactions']['card'] }} / {{ $m['transactions']['other'] }}
        </p>
    </div>
</div>

{{-- Errors --}}
<p class="ba-section-title">Errors</p>
<div class="ba-grid ba-grid-4 mb-6">
    <div class="ba-card">
        <p class="ba-kpi-label">Failed transactions</p>
        <p class="ba-kpi-value">{{ number_format($m['errors']['failed_transactions']) }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Active alarms</p>
        <p class="ba-kpi-value">{{ number_format($m['errors']['active_alarms']) }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Fault slots</p>
        <p class="ba-kpi-value">{{ number_format($m['errors']['fault_slots']) }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Daily avg revenue</p>
        <p class="ba-kpi-value">${{ number_format($m['forecast']['daily_avg_revenue'], 2) }}</p>
    </div>
</div>

{{-- Operations --}}
<p class="ba-section-title">Basic operations</p>
<div class="ba-grid ba-grid-4">
    <div class="ba-card">
        <p class="ba-kpi-label">Machines online</p>
        <p class="ba-kpi-value">{{ $m['operations']['machines_online'] }} / {{ $m['operations']['machines_total'] }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Slots in stock</p>
        <p class="ba-kpi-value">{{ $m['operations']['slots_in_stock'] }} / {{ $m['operations']['slots_total'] }}</p>
        <p class="ba-kpi-sub">{{ number_format($m['operations']['fill_rate_percent'], 1) }}% fill rate</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Low / empty slots</p>
        <p class="ba-kpi-value">{{ $m['operations']['slots_low_stock'] }} / {{ $m['operations']['slots_empty'] }}</p>
    </div>
    <div class="ba-card">
        <p class="ba-kpi-label">Fault slots</p>
        <p class="ba-kpi-value">{{ number_format($m['operations']['slots_fault']) }}</p>
    </div>
</div>
