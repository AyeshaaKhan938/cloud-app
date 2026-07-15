<x-filament-widgets::widget>
    <x-filament::section
        heading="Operator alerts"
        description="Active kiosk alerts (from Notification Configuration)."
    >
        @php($alerts = $this->getAlerts())

        @if ($alerts === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No active alerts. Enable types under System → Notification Configuration.
            </p>
        @else
            <ul class="space-y-3">
                @foreach ($alerts as $alert)
                    @php($critical = $alert['severity'] === 'critical')
                    <li @class([
                        'rounded-lg border px-4 py-3',
                        'border-danger-300 bg-danger-50 dark:border-danger-500/30 dark:bg-danger-500/10' => $critical,
                        'border-warning-300 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10' => ! $critical,
                    ])>
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $alert['title'] }}
                            <span class="font-normal text-gray-500 dark:text-gray-400">
                                — {{ $alert['machine_name'] }} ({{ $alert['machine_number'] }})
                            </span>
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $alert['message'] }}
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
