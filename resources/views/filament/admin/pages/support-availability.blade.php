<x-filament-panels::page>
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex items-center gap-3">
            <span @class([
                'inline-flex h-3 w-3 rounded-full',
                'bg-emerald-500' => $this->available,
                'bg-gray-400' => ! $this->available,
            ])></span>
            <div>
                <p class="font-medium text-gray-950 dark:text-white">
                    {{ $this->available ? 'You are available for live chat' : 'You are offline for live chat' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Operators can request live chat only when at least one support agent is online.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
