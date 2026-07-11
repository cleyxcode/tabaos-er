<div
    x-data="petaRealtimeMap(@js($mapData), @js($radiusFilter))"
    x-init="init()"
    wire:ignore.self
    class="space-y-3"
>
    <div id="peta-realtime-data" wire:key="map-{{ $mapData['updated_at'] }}" class="hidden">@json($mapData)</div>
    <div id="peta-realtime-radius" wire:key="radius-{{ $radiusFilter['lat'] }}-{{ $radiusFilter['lng'] }}-{{ $radiusFilter['km'] }}" class="hidden">@json($radiusFilter)</div>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="text-sm text-gray-600 dark:text-gray-300">
            <span class="font-medium text-gray-900 dark:text-white">Peta interaktif</span>
            · Terakhir diperbarui: <span class="font-semibold" x-text="lastUpdated"></span>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($areaAktif)
                <button
                    type="button"
                    x-on:click="gunakanPusatPeta()"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-primary-500"
                >
                    📍 Gunakan posisi tengah peta
                </button>
            @endif
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
                <div>🔴 Laporan ({{ $mapData['counts']['laporan'] }})</div>
                <div>🔵 Relawan ({{ $mapData['counts']['relawan'] }})</div>
                <div>🟢 Faskes ({{ $mapData['counts']['faskes'] }})</div>
                <div>🟣 Titik Evakuasi ({{ $mapData['counts']['evakuasi'] }})</div>
                <div>🟡 Petugas ({{ $mapData['counts']['petugas'] }})</div>
            </div>
        </div>
    </div>

    @once
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
    @endonce

    <script>
        function petaRealtimeMap(initialData, initialRadius) {
            return {
                map: null,
                layerGroups: {},
                markerRegistry: {},
                radiusCircle: null,
                mapData: initialData,
                radiusFilter: initialRadius,
                lastUpdated: '',
                hasInitialFit: false,
                lastDataHash: '',
                lastRadiusHash: '',

                init() {
                    this.lastUpdated = this.formatTime(this.mapData.updated_at);
                    this.lastRadiusHash = this.radiusHash(this.radiusFilter);
                    this.loadLeaflet(() => this.bootMap());

                    Livewire.hook('morph.updated', ({ component }) => {
                        if (!component.el.contains(this.$root)) return;
                        this.syncFromDom();
                    });
                },

                gunakanPusatPeta() {
                    if (!this.map) return;
                    const center = this.map.getCenter();
                    this.$wire.setPusatDariPeta(center.lat, center.lng);
                },

                radiusHash(radius) {
                    return `${radius?.lat ?? ''}|${radius?.lng ?? ''}|${radius?.km ?? ''}`;
                },

                dataHash(data) {
                    const groups = {
                        laporan: data.laporan ?? [],
                        relawan: data.relawan ?? [],
                        faskes: data.faskes ?? [],
                        evakuasi: data.evakuasi ?? [],
                        petugas: data.petugas ?? [],
                    };
                    return JSON.stringify(groups);
                },

                syncFromDom() {
                    const dataEl = document.getElementById('peta-realtime-data');
                    const radiusEl = document.getElementById('peta-realtime-radius');
                    let radiusChanged = false;

                    if (radiusEl) {
                        const nextRadius = JSON.parse(radiusEl.textContent);
                        const nextRadiusHash = this.radiusHash(nextRadius);
                        radiusChanged = nextRadiusHash !== this.lastRadiusHash;
                        this.radiusFilter = nextRadius;
                        this.lastRadiusHash = nextRadiusHash;
                    }

                    if (dataEl) {
                        const nextData = JSON.parse(dataEl.textContent);
                        const nextHash = this.dataHash(nextData);
                        if (nextHash !== this.lastDataHash) {
                            this.applyMapData(nextData, radiusChanged);
                        } else if (radiusChanged && this.map) {
                            this.renderMarkers(true);
                        }
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

                    this.renderMarkers(true);
                    this.lastDataHash = this.dataHash(this.mapData);
                    setTimeout(() => this.map.invalidateSize(), 400);
                },

                applyMapData(data, radiusChanged = false) {
                    this.mapData = data;
                    this.lastUpdated = this.formatTime(data.updated_at);
                    this.lastDataHash = this.dataHash(data);
                    if (this.map) this.renderMarkers(radiusChanged);
                },

                renderMarkers(shouldFitBounds = false) {
                    if (!this.map) return;

                    const groups = {
                        laporan: this.mapData.laporan ?? [],
                        relawan: this.mapData.relawan ?? [],
                        faskes: this.mapData.faskes ?? [],
                        evakuasi: this.mapData.evakuasi ?? [],
                        petugas: this.mapData.petugas ?? [],
                    };

                    Object.entries(groups).forEach(([type, items]) => {
                        const activeIds = new Set(items.map(item => `${type}-${item.id}`));

                        Object.keys(this.markerRegistry).forEach(key => {
                            if (!key.startsWith(`${type}-`)) return;
                            if (!activeIds.has(key)) {
                                this.layerGroups[type]?.removeLayer(this.markerRegistry[key]);
                                delete this.markerRegistry[key];
                            }
                        });

                        items.forEach(item => {
                            const key = `${type}-${item.id}`;
                            const existing = this.markerRegistry[key];

                            if (existing) {
                                this.animateMarker(existing, [item.latitude, item.longitude]);
                                existing.setPopupContent(this.buildPopup(type, item));
                                return;
                            }

                            const marker = this.createMarker(type, item);
                            if (marker) {
                                this.layerGroups[type].addLayer(marker);
                                this.markerRegistry[key] = marker;
                            }
                        });
                    });

                    this.drawRadiusIfNeeded();

                    if (shouldFitBounds || !this.hasInitialFit) {
                        this.fitBoundsIfNeeded(groups);
                        this.hasInitialFit = true;
                    }
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

                    if (this.radiusCircle) {
                        this.map.removeLayer(this.radiusCircle);
                        this.radiusCircle = null;
                    }

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

                animateMarker(marker, targetLatLng, duration = 900) {
                    if (marker._animFrame) cancelAnimationFrame(marker._animFrame);
                    const start = marker.getLatLng();
                    const startTime = performance.now();
                    const step = (now) => {
                        const t = Math.min(1, (now - startTime) / duration);
                        const eased = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
                        const lat = start.lat + (targetLatLng[0] - start.lat) * eased;
                        const lng = start.lng + (targetLatLng[1] - start.lng) * eased;
                        marker.setLatLng([lat, lng]);
                        if (t < 1) {
                            marker._animFrame = requestAnimationFrame(step);
                        } else {
                            marker._animFrame = null;
                        }
                    };
                    marker._animFrame = requestAnimationFrame(step);
                },
            };
        }
    </script>
</div>
