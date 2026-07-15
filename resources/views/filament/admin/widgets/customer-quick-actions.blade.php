<x-filament-widgets::widget>
    <x-filament::section heading="Quick links" description="Jump to the pages you use most.">
        @include('filament.admin.widgets.partials.quick-actions-grid', ['actions' => $this->getActions()])
    </x-filament::section>
</x-filament-widgets::widget>
