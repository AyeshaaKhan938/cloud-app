<x-filament-widgets::widget>
    <x-filament::section heading="Admin shortcuts" description="Jump straight to the tools you manage every day.">
        @include('filament.admin.widgets.partials.quick-actions-grid', ['actions' => $this->getActions()])
    </x-filament::section>
</x-filament-widgets::widget>
