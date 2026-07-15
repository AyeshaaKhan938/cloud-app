<x-filament-widgets::widget>
    <div class="vms-welcome-banner">
        <div class="vms-welcome-accent" aria-hidden="true"></div>

        <div class="vms-welcome-main">
            <div class="vms-welcome-icon">
                <x-filament::icon icon="heroicon-o-sparkles" class="h-6 w-6" />
            </div>

            <div class="vms-welcome-copy">
                <p class="vms-welcome-greeting">
                    {{ $this->getGreeting() }}, {{ $this->getUserName() }}
                </p>
                <p class="vms-welcome-message">
                    {{ $this->getMessage() }}
                </p>
            </div>
        </div>

        <div class="vms-welcome-meta">
            <span class="vms-welcome-pill">{{ $this->getRoleLabel() }}</span>
            <span class="vms-welcome-date">{{ now()->format('l, M j') }}</span>
        </div>
    </div>
</x-filament-widgets::widget>
