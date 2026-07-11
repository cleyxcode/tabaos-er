<div
    x-data="petaRealtimeMap(@js($mapData), @js($radiusFilter))"
    x-init="init()"
    wire:ignore.self
    class="space-y-4"
>
    <div id="peta-realtime-data" wire:key="map-{{ $mapData['updated_at'] }}" class="hidden">@json($mapData)</div>
    <div id="peta-realtime-radius" wire:key="radius-{{ $radiusFilter['lat'] }}-{{ $radiusFilter['lng'] }}-{{ $radiusFilter['km'] }}" class="hidden">@json($radiusFilter)</div>
    {{-- Panel Filter --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Filter Lokasi</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Perbarui otomatis setiap 10 detik · Terakhir: <span x-text="lastUpdated"></span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="fi-btn fi-btn-size-sm inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Reset Filter
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Wilayah</label>
                <select wire:model.live="wilayahId" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option value="">Semua Wilayah</option>
                    @foreach ($wilayahOptions as $id => $nama)
                        <option value="{{ $id }}">{{ $nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Jenis Kejadian</label>
                <select wire:model.live="jenisKejadian" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option value="">Semua Jenis</option>
                    @foreach ($jenisKejadianOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Status Laporan</label>
                <select wire:model.live="statusLaporan" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option value="">Semua Status</option>
                    @foreach ($statusLaporanOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Status Penanganan</label>
                <select wire:model.live="statusPenanganan" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option value="">Semua Penanganan</option>
                    @foreach ($statusPenangananOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Latitude Pusat</label>
                <input type="number" step="any" wire:model.live.debounce.500ms="centerLat" placeholder="-3.6954" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Longitude Pusat</label>
                <input type="number" step="any" wire:model.live.debounce.500ms="centerLng" placeholder="128.1814" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Radius (km)</label>
                <input type="number" step="any" min="1" wire:model.live.debounce.500ms="radiusKm" placeholder="10" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Relawan Aktif (menit)</label>
                <input type="number" min="1" max="120" wire:model.live.debounce.500ms="relawanStaleMinutes" class="fi-input block w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-4 text-sm">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" wire:model.live="tampilkanLaporan" class="rounded border-gray-300 text-primary-600">
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-red-500"></span> Laporan ({{ $mapData['counts']['laporan'] }})</span>
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" wire:model.live="tampilkanRelawan" class="rounded border-gray-300 text-primary-600">
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-blue-500"></span> Relawan ({{ $mapData['counts']['relawan'] }})</span>
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" wire:model.live="tampilkanFaskes" class="rounded border-gray-300 text-primary-600">
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-green-600"></span> Faskes ({{ $mapData['counts']['faskes'] }})</span>
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" wire:model.live="tampilkanEvakuasi" class="rounded border-gray-300 text-primary-600">
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-purple-500"></span> Titik Evakuasi ({{ $mapData['counts']['evakuasi'] }})</span>
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" wire:model.live="tampilkanPetugas" class="rounded border-gray-300 text-primary-600">
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-amber-500"></span> Petugas ({{ $mapData['counts']['petugas'] }})</span>
            </label>
        </div>
    </div>

    {{-- Peta --}}
    <div wire:ignore class="relative">
        <div
            x-ref="mapEl"
            class="rounded-xl border border-gray-200 shadow-sm dark:border-gray-700"
            style="height: 620px; width: 100%;"
        ></div>
        <div class="absolute bottom-3 left-3 z-[1000] rounded-lg bg-white/95 px-3 py-2 text-xs shadow dark:bg-gray-900/95">
            <div class="font-semibold text-gray-800 dark:text-gray-100">Legenda</div>
            <div class="mt-1 space-y-0.5 text-gray-600 dark:text-gray-300">
                <div>🔴 Laporan Kejadian</div>
                <div>🔵 Relawan (posisi realtime)</div>
                <div>🟢 Faskes</div>
                <div>🟣 Titik Evakuasi</div>
                <div>🟡 Petugas Emergency</div>
            </div>
        </div>
    </div>

    <style>
        .leaflet-pane { z-index: 10; }
        .leaflet-top, .leaflet-bottom { z-index: 20; }
        .marker-pulse {
            border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            animation: markerPulse 2s infinite;
        }
        @keyframes markerPulse {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            70% { box-shadow: 0 0 0 12px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
    </style>

    <script>
        function petaRealtimeMap(initialData, initialRadius) {
            return {
                map: null,
                layerGroups: {},
                radiusCircle: null,
                mapData: initialData,
                radiusFilter: initialRadius,
                lastUpdated: '',

                init() {
                    this.lastUpdated = this.formatTime(this.mapData.updated_at);
                    this.loadLeaflet(() => this.bootMap());

                    Livewire.hook('morph.updated', ({ component }) => {
                        if (!component.el.contains(this.$root)) return;
                        this.syncFromDom();
                    });
                },

                syncFromDom() {
                    const dataEl = document.getElementById('peta-realtime-data');
                    const radiusEl = document.getElementById('peta-realtime-radius');
                    if (dataEl) {
                        this.applyMapData(JSON.parse(dataEl.textContent));
                    }
                    if (radiusEl) {
                        this.radiusFilter = JSON.parse(radiusEl.textContent);
                        if (this.map) this.renderMarkers();
                    }
                },

                loadLeaflet(callback) {
                    if (window.L) { callback(); return; }

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
                            if (window.L) { clearInterval(poll); callback(); }
                        }, 50);
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = js;
                    script.onload = callback;
                    head.appendChild(script);
                },

                bootMap() {
                    this.map = L.map(this.$refs.mapEl).setView([-3.6954, 128.1814], 12);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap',
                    }).addTo(this.map);

                    this.layerGroups = {
                        laporan: L.layerGroup().addTo(this.map),
                        relawan: L.layerGroup().addTo(this.map),
                        faskes: L.layerGroup().addTo(this.map),
                        evakuasi: L.layerGroup().addTo(this.map),
                        petugas: L.layerGroup().addTo(this.map),
                    };

                    this.renderMarkers();
                    setTimeout(() => this.map.invalidateSize(), 400);
                },

                applyMapData(data) {
                    this.mapData = data;
                    this.lastUpdated = this.formatTime(data.updated_at);
                    if (this.map) this.renderMarkers();
                },

                renderMarkers() {
                    Object.values(this.layerGroups).forEach(g => g.clearLayers());
                    if (this.radiusCircle) {
                        this.map.removeLayer(this.radiusCircle);
                        this.radiusCircle = null;
                    }

                    const groups = {
                        laporan: this.mapData.laporan ?? [],
                        relawan: this.mapData.relawan ?? [],
                        faskes: this.mapData.faskes ?? [],
                        evakuasi: this.mapData.evakuasi ?? [],
                        petugas: this.mapData.petugas ?? [],
                    };

                    Object.entries(groups).forEach(([type, items]) => {
                        items.forEach(item => {
                            const marker = this.createMarker(type, item);
                            if (marker) this.layerGroups[type].addLayer(marker);
                        });
                    });

                    this.drawRadiusIfNeeded();
                    this.fitBoundsIfNeeded(groups);
                },

                createMarker(type, item) {
                    const colors = {
                        laporan: '#ef4444',
                        relawan: '#3b82f6',
                        faskes: '#16a34a',
                        evakuasi: '#9333ea',
                        petugas: '#f59e0b',
                    };

                    const icons = {
                        laporan: '⚠️',
                        relawan: '🧑‍🚒',
                        faskes: '🏥',
                        evakuasi: '🏕️',
                        petugas: '🦺',
                    };

                    const isRelawan = type === 'relawan';
                    const html = `
                        <div class="${isRelawan ? 'marker-pulse' : ''}" style="
                            background:${colors[type]};
                            width:28px;height:28px;border-radius:50%;
                            display:flex;align-items:center;justify-content:center;
                            border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.3);
                            font-size:14px;
                        ">${icons[type]}</div>`;

                    const icon = L.divIcon({
                        html,
                        className: '',
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                    });

                    const marker = L.marker([item.latitude, item.longitude], { icon });
                    marker.bindPopup(this.buildPopup(type, item));
                    return marker;
                },

                buildPopup(type, item) {
                    let rows = `<strong>${item.title ?? item.label}</strong>`;
                    if (item.subtitle) rows += `<br><span>${item.subtitle}</span>`;
                    if (item.status_penanganan) rows += `<br>Penanganan: <b>${item.status_penanganan}</b>`;
                    if (item.status) rows += `<br>Status: <b>${item.status}</b>`;
                    if (item.wilayah) rows += `<br>Wilayah: ${item.wilayah}`;
                    if (item.relawan) rows += `<br>Relawan: ${item.relawan}`;
                    if (item.tanggal) rows += `<br>Waktu: ${item.tanggal}`;
                    if (item.lokasi_updated_at) rows += `<br>Update lokasi: ${this.formatTime(item.lokasi_updated_at)}`;
                    if (item.jarak_km != null) rows += `<br>Jarak: ${item.jarak_km} km`;
                    if (item.telepon) rows += `<br>Tel: ${item.telepon}`;
                    if (item.kapasitas) rows += `<br>Kapasitas: ${item.kapasitas}`;
                    return rows;
                },

                drawRadiusIfNeeded() {
                    const lat = parseFloat(this.radiusFilter?.lat);
                    const lng = parseFloat(this.radiusFilter?.lng);
                    const radius = parseFloat(this.radiusFilter?.km);

                    if (!isNaN(lat) && !isNaN(lng) && !isNaN(radius) && radius > 0) {
                        this.radiusCircle = L.circle([lat, lng], {
                            radius: radius * 1000,
                            color: '#2563eb',
                            fillColor: '#3b82f6',
                            fillOpacity: 0.08,
                            weight: 2,
                            dashArray: '6 4',
                        }).addTo(this.map);
                    }
                },

                fitBoundsIfNeeded(groups) {
                    const all = Object.values(groups).flat();
                    if (all.length === 0) return;

                    const bounds = L.latLngBounds(all.map(i => [i.latitude, i.longitude]));
                    if (bounds.isValid()) {
                        this.map.fitBounds(bounds.pad(0.12));
                    }
                },

                formatTime(iso) {
                    if (!iso) return '-';
                    const d = new Date(iso);
                    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                },
            };
        }
    </script>
</div>
