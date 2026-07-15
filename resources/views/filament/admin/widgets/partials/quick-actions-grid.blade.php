<div class="vms-quick-actions-grid">
    @foreach ($actions as $action)
        <a href="{{ $action['url'] }}" class="vms-quick-action-card">
            <span class="vms-quick-action-icon">
                <x-filament::icon :icon="$action['icon']" class="h-6 w-6" />
            </span>
            <span class="vms-quick-action-copy">
                <span class="vms-quick-action-label">{{ $action['label'] }}</span>
                <span class="vms-quick-action-description">{{ $action['description'] }}</span>
            </span>
            <x-filament::icon icon="heroicon-m-chevron-right" class="vms-quick-action-chevron h-5 w-5" />
        </a>
    @endforeach
</div>
