<x-filament-panels::page>
    <div class="fi-machine-map space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Machines with latitude and longitude appear as markers. Pan and zoom the map to explore.
        </p>

        <div
            wire:ignore
            id="machine-map-canvas"
            class="h-[min(70vh,560px)] w-full overflow-hidden rounded-xl border border-gray-200 bg-gray-100 dark:border-white/10 dark:bg-gray-900"
        ></div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Machines on map</h3>
            <ul class="mt-2 max-h-48 space-y-1 overflow-y-auto text-sm text-gray-600 dark:text-gray-300">
                @forelse ($this->getMachinesForMap() as $m)
                    <li>
                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ $m['number'] }}</span>
                        — {{ $m['label'] }}
                    </li>
                @empty
                    <li class="text-gray-500 dark:text-gray-400">No machines with coordinates yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    @push('styles')
        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
            crossorigin=""
        />
    @endpush

    @push('scripts')
        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""
        ></script>
        <script>
            (function () {
                const points = @json($this->getMachinesForMap());

                function renderMap() {
                    const el = document.getElementById('machine-map-canvas');
                    if (!el || typeof L === 'undefined') {
                        return;
                    }

                    el.innerHTML = '';
                    const map = L.map(el, { scrollWheelZoom: true });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap',
                    }).addTo(map);

                    if (!points.length) {
                        map.setView([20, 0], 2);
                        return;
                    }

                    const bounds = L.latLngBounds(points.map((p) => [p.lat, p.lng]));
                    points.forEach((p) => {
                        L.marker([p.lat, p.lng])
                            .addTo(map)
                            .bindPopup(
                                '<strong>' +
                                    String(p.number).replace(/</g, '&lt;') +
                                    '</strong><br>' +
                                    String(p.label).replace(/</g, '&lt;'),
                            );
                    });
                    map.fitBounds(bounds.pad(0.15));
                }

                document.addEventListener('DOMContentLoaded', renderMap);
                document.addEventListener('livewire:navigated', renderMap);
            })();
        </script>
    @endpush
</x-filament-panels::page>
