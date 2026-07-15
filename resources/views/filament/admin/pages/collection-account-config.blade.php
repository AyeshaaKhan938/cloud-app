@php
    use Illuminate\View\ComponentAttributeBag;
@endphp

<x-filament-panels::page>
    <div class="fi-collection-account-config space-y-6">
        <div class="max-w-md">
            <x-filament::input.wrapper>
                <x-filament::input
                    :attributes="
                        (new ComponentAttributeBag)->merge([
                            'autocomplete' => 'off',
                            'placeholder' => 'Search payment method',
                            'type' => 'search',
                            'wire:model.live.debounce.300ms' => 'search',
                        ], escape: false)
                    "
                />
            </x-filament::input.wrapper>
        </div>

        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between gap-4">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Not configured
                </span>
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                    {{ $this->getNotConfiguredCount() }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->getFilteredGateways() as $gateway)
                @php
                    $configured = $this->isGatewayConfigured($gateway['slug']);
                @endphp
                <div
                    class="flex flex-col justify-between gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900"
                >
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $gateway['title'] }}
                        </h3>
                        <span
                            @class([
                                'shrink-0 rounded px-2 py-0.5 text-xs font-medium',
                                'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300' => ! $configured,
                                'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $configured,
                            ])
                        >
                            {{ $configured ? 'Configured' : 'Not configured' }}
                        </span>
                    </div>
                    <div class="flex justify-end">
                        <x-filament::button
                            color="primary"
                            size="sm"
                            tag="button"
                            type="button"
                            wire:click="openGatewayConfiguration('{{ addslashes($gateway['slug']) }}')"
                        >
                            Configure now
                        </x-filament::button>
                    </div>
                </div>
            @endforeach
        </div>

        @if (count($this->getFilteredGateways()) === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No payment methods match your search.
            </p>
        @endif
    </div>
</x-filament-panels::page>
