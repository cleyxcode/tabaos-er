@php
    /** @var \App\Models\ZonaRawanBencana $zona */
    $coords = $zona->polygonCoordsNormalized();
    $mapConfig = $zona->toMapPickerViewConfig();
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
            x-data="mapPicker($wire, @js($mapConfig))"
            x-init="async () => {
                do {
                    await (new Promise(resolve => setTimeout(resolve, 100)));
                } while (!$refs.map);
                attach($refs.map, $refs);
            }"
            wire:ignore
        >
            <div
                x-ref="map"
                class="w-full overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700"
                style="min-height: 420px; height: 420px;"
            ></div>
            <input type="text" x-ref="formRestorationInput" style="display:none" />
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
