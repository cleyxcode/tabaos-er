@php
    /** @var \App\Models\ZonaRawanBencana $zona */
    $coords = $zona->polygonCoordsNormalized();
    $color = $zona->polygonRisikoColor();
@endphp

<div class="space-y-4">
    <div class="grid gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-2">
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400">Nama Zona</p>
            <p class="font-medium text-gray-800 dark:text-gray-100">{{ $zona->nama_zona }}</p>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400">Tingkat Risiko</p>
            <p class="font-medium text-gray-800 dark:text-gray-100">{{ ucfirst($zona->tingkat_risiko) }}</p>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400">Wilayah</p>
            <p class="font-medium text-gray-800 dark:text-gray-100">{{ $zona->wilayah?->nama ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400">Titik Polygon</p>
            <p class="font-medium text-gray-800 dark:text-gray-100">{{ $zona->polygonTitikCount() }} titik</p>
        </div>
    </div>

    @if ($zona->memilikiPolygon())
        <div
            x-data="zonaPolygonViewMap(@js($coords), @js($color), @js($zona->nama_zona))"
            x-init="init()"
            wire:ignore
        >
            <div
                x-ref="mapEl"
                class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700"
                style="height: 420px; width: 100%;"
            ></div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">#</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Latitude</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Longitude</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-950">
                    @foreach ($coords as $index => $point)
                        <tr>
                            <td class="px-4 py-2 font-medium text-gray-700 dark:text-gray-200">{{ $index + 1 }}</td>
                            <td class="px-4 py-2 font-mono text-gray-800 dark:text-gray-100">{{ number_format($point['lat'], 6) }}</td>
                            <td class="px-4 py-2 font-mono text-gray-800 dark:text-gray-100">{{ number_format($point['lng'], 6) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
            Polygon belum digambar untuk zona ini.
        </div>
    @endif
</div>

@once
    <script>
        function zonaPolygonViewMap(coords, color, label) {
            return {
                map: null,

                init() {
                    if (!coords?.length) return;
                    this.loadLeaflet(() => this.bootMap(coords, color, label));
                },

                loadLeaflet(callback) {
                    if (window.L) {
                        callback();
                        return;
                    }

                    const head = document.head;
                    const css = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    if (!document.querySelector(`link[href="${css}"]`)) {
                        const link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = css;
                        head.appendChild(link);
                    }

                    const js = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    if (document.querySelector(`script[src="${js}"]`)) {
                        const poll = setInterval(() => {
                            if (window.L) {
                                clearInterval(poll);
                                callback();
                            }
                        }, 50);
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = js;
                    script.onload = callback;
                    head.appendChild(script);
                },

                bootMap(coords, color, label) {
                    this.map = L.map(this.$refs.mapEl).setView([-3.6954, 128.1814], 12);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap',
                    }).addTo(this.map);

                    const latlngs = coords.map(p => [p.lat, p.lng]);
                    const poly = L.polygon(latlngs, {
                        color,
                        weight: 3,
                        opacity: 1,
                        fillColor: color,
                        fillOpacity: 0.25,
                    }).addTo(this.map);

                    poly.bindPopup(`<strong>${label}</strong><br>${coords.length} titik polygon`);
                    this.map.fitBounds(poly.getBounds(), { padding: [40, 40] });

                    setTimeout(() => this.map?.invalidateSize(), 350);
                },
            };
        }
    </script>
@endonce
