@props([
    'tabs' => [],
    'activeTab' => 'overview',
])

<nav {{ $attributes->class(['vms-hub-tabs']) }} aria-label="Entity sections">
    @foreach ($tabs as $key => $tab)
        <button
            type="button"
            wire:click="setActiveTab('{{ $key }}')"
            @class(['vms-hub-tab', 'vms-hub-tab-active' => $activeTab === $key])
        >
            @if (! empty($tab['icon']))
                <x-filament::icon :icon="$tab['icon']" class="vms-hub-tab-icon" />
            @endif
            <span>{{ $tab['label'] }}</span>
        </button>
    @endforeach
</nav>
