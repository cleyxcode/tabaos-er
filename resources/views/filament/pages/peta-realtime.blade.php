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
                <div x-text="`🔴 Laporan (${mapData.counts?.laporan ?? 0})`"></div>
                <div x-text="`🔵 Relawan (${mapData.counts?.relawan ?? 0})`"></div>
                <div x-text="`🟢 Faskes (${mapData.counts?.faskes ?? 0})`"></div>
                <div x-text="`🟣 Titik Evakuasi (${mapData.counts?.evakuasi ?? 0})`"></div>
                <div x-text="`🟡 Petugas (${mapData.counts?.petugas ?? 0})`"></div>
            </div>
        </div>
    </div>

    {{-- Daftar laporan di peta --}}
    <div
        x-show="(mapData.laporan ?? []).length > 0"
        x-cloak
        class="rounded-xl border border-red-200 bg-red-50/60 dark:border-red-900 dark:bg-red-950/40"
    >
        <div class="flex items-center justify-between border-b border-red-200 px-4 py-3 dark:border-red-900">
            <div>
                <p class="text-sm font-semibold text-red-900 dark:text-red-100">Laporan di Peta</p>
                <p class="text-xs text-red-700/80 dark:text-red-300/80">Klik untuk zoom ke lokasi kejadian</p>
            </div>
            <span
                class="rounded-full bg-red-600 px-2.5 py-0.5 text-xs font-bold text-white"
                x-text="`${(mapData.laporan ?? []).length} laporan`"
            ></span>
        </div>
        <div class="grid gap-2 p-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <template x-for="laporan in (mapData.laporan ?? [])" :key="`laporan-list-${laporan.id}`">
                <button
                    type="button"
                    x-on:click="focusMarker('laporan', laporan.id)"
                    class="flex items-start gap-2 rounded-lg border px-3 py-2 text-left text-sm shadow-sm transition dark:bg-gray-900"
                    :class="activeMarkerKey === `laporan-${laporan.id}`
                        ? 'border-red-500 bg-red-100 dark:border-red-500 dark:bg-red-950'
                        : 'border-red-200 bg-white hover:border-red-400 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-950'"
                >
                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-600 text-sm text-white">⚠️</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-gray-900 dark:text-gray-100" x-text="laporan.label"></span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="laporan.title"></span>
                        <span class="mt-0.5 block truncate text-[11px] text-red-700 dark:text-red-300" x-text="laporan.subtitle || laporan.wilayah || '—'"></span>
                    </span>
                </button>
            </template>
        </div>
    </div>

    {{-- Daftar relawan aktif di peta --}}
    <div
        x-show="(mapData.relawan ?? []).length > 0"
        x-cloak
        class="rounded-xl border border-blue-200 bg-blue-50/60 dark:border-blue-900 dark:bg-blue-950/40"
    >
        <div class="flex items-center justify-between border-b border-blue-200 px-4 py-3 dark:border-blue-900">
            <div>
                <p class="text-sm font-semibold text-blue-900 dark:text-blue-100">Relawan di Peta</p>
                <p class="text-xs text-blue-700/80 dark:text-blue-300/80">Klik nama untuk fokus ke lokasi dan lihat detail</p>
            </div>
            <span
                class="rounded-full bg-blue-600 px-2.5 py-0.5 text-xs font-bold text-white"
                x-text="`${(mapData.relawan ?? []).length} orang`"
            ></span>
        </div>
        <div class="grid gap-2 p-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <template x-for="relawan in (mapData.relawan ?? [])" :key="`relawan-list-${relawan.id}`">
                <button
                    type="button"
                    x-on:click="focusMarker('relawan', relawan.id)"
                    class="flex items-start gap-2 rounded-lg border px-3 py-2 text-left text-sm shadow-sm transition dark:bg-gray-900"
                    :class="activeMarkerKey === `relawan-${relawan.id}`
                        ? 'border-blue-500 bg-blue-100 dark:border-blue-500 dark:bg-blue-950'
                        : 'border-blue-200 bg-white hover:border-blue-400 hover:bg-blue-50 dark:border-blue-800 dark:hover:bg-blue-950'"
                >
                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white" x-text="relawanInitials(relawan.title)"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-gray-900 dark:text-gray-100" x-text="relawan.title"></span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="relawan.keahlian || relawan.subtitle || 'Relawan'"></span>
                        <span class="mt-0.5 block text-[11px] text-blue-600 dark:text-blue-300" x-text="`Update: ${formatTime(relawan.lokasi_updated_at)}`"></span>
                    </span>
                </button>
            </template>
        </div>
    </div>

    {{-- Daftar faskes di peta --}}
    <div
        x-show="(mapData.faskes ?? []).length > 0"
        x-cloak
        class="rounded-xl border border-green-200 bg-green-50/60 dark:border-green-900 dark:bg-green-950/40"
    >
        <div class="flex items-center justify-between border-b border-green-200 px-4 py-3 dark:border-green-900">
            <div>
                <p class="text-sm font-semibold text-green-900 dark:text-green-100">Faskes di Peta</p>
                <p class="text-xs text-green-700/80 dark:text-green-300/80">Klik nama untuk fokus ke lokasi dan lihat detail</p>
            </div>
            <span
                class="rounded-full bg-green-600 px-2.5 py-0.5 text-xs font-bold text-white"
                x-text="`${(mapData.faskes ?? []).length} faskes`"
            ></span>
        </div>
        <div class="grid gap-2 p-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <template x-for="faskes in (mapData.faskes ?? [])" :key="`faskes-list-${faskes.id}`">
                <button
                    type="button"
                    x-on:click="focusMarker('faskes', faskes.id)"
                    class="flex items-start gap-2 rounded-lg border px-3 py-2 text-left text-sm shadow-sm transition dark:bg-gray-900"
                    :class="activeMarkerKey === `faskes-${faskes.id}`
                        ? 'border-green-500 bg-green-100 dark:border-green-500 dark:bg-green-950'
                        : 'border-green-200 bg-white hover:border-green-400 hover:bg-green-50 dark:border-green-800 dark:hover:bg-green-950'"
                >
                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-600 text-xs font-bold text-white">🏥</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-gray-900 dark:text-gray-100" x-text="faskes.title"></span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="faskes.tipe_label || faskes.tipe || 'Faskes'"></span>
                        <span class="mt-0.5 block truncate text-[11px] text-green-700 dark:text-green-300" x-text="faskes.wilayah || faskes.subtitle || '—'"></span>
                    </span>
                </button>
            </template>
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
            .relawan-marker-label {
                background: rgba(255, 255, 255, 0.96);
                color: #1e40af;
                font-size: 10px;
                font-weight: 700;
                line-height: 1.2;
                padding: 2px 6px;
                border-radius: 6px;
                border: 1px solid #93c5fd;
                white-space: nowrap;
                max-width: 96px;
                overflow: hidden;
                text-overflow: ellipsis;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
            }
            .faskes-marker-label {
                background: rgba(255, 255, 255, 0.96);
                color: #166534;
                font-size: 10px;
                font-weight: 700;
                line-height: 1.2;
                padding: 2px 6px;
                border-radius: 6px;
                border: 1px solid #86efac;
                white-space: nowrap;
                max-width: 96px;
                overflow: hidden;
                text-overflow: ellipsis;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
            }
            .laporan-marker-label {
                background: rgba(255, 255, 255, 0.96);
                color: #b91c1c;
                font-size: 10px;
                font-weight: 700;
                line-height: 1.2;
                padding: 2px 6px;
                border-radius: 6px;
                border: 1px solid #fca5a5;
                white-space: nowrap;
                max-width: 96px;
                overflow: hidden;
                text-overflow: ellipsis;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
            }
            .named-map-marker {
                cursor: pointer;
            }
            .relawan-popup,
            .faskes-popup,
            .laporan-popup {
                min-width: 200px;
                line-height: 1.45;
            }
            .relawan-popup-name {
                font-size: 15px;
                font-weight: 800;
                color: #1e40af;
                margin-bottom: 4px;
            }
            .faskes-popup-name {
                font-size: 15px;
                font-weight: 800;
                color: #166534;
                margin-bottom: 4px;
            }
            .laporan-popup-name {
                font-size: 15px;
                font-weight: 800;
                color: #b91c1c;
                margin-bottom: 4px;
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
                pollTimer: null,
                activeMarkerKey: null,

                init() {
                    this.lastUpdated = this.formatTime(this.mapData.updated_at);
                    this.lastRadiusHash = this.radiusHash(this.radiusFilter);
                    this.loadLeaflet(() => this.bootMap());

                    Livewire.hook('morph.updated', ({ component }) => {
                        if (!component.el.contains(this.$root)) return;
                        this.syncFromDom();
                    });

                    this.pollTimer = setInterval(() => this.pollMapData(), 5000);
                    document.addEventListener('livewire:navigating', () => this.destroy(), { once: true });
                },

                destroy() {
                    if (this.pollTimer) clearInterval(this.pollTimer);
                },

                async pollMapData() {
                    if (!this.$wire) return;
                    try {
                        const data = await this.$wire.refreshMapData();
                        if (!data) return;
                        const nextHash = this.dataHash(data);
                        if (nextHash !== this.lastDataHash) {
                            this.applyMapData(data);
                        } else {
                            this.lastUpdated = this.formatTime(data.updated_at);
                        }
                    } catch (e) {
                        console.warn('[peta-realtime] gagal memuat data peta', e);
                    }
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
                                if (type === 'laporan' || type === 'relawan' || type === 'faskes') {
                                    existing.setIcon(this.createMarkerIcon(type, item));
                                }
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
                    const icon = this.createMarkerIcon(type, item);
                    const marker = L.marker([item.latitude, item.longitude], { icon });
                    marker.bindPopup(this.buildPopup(type, item));

                    if (type === 'laporan' || type === 'relawan' || type === 'faskes') {
                        marker.on('click', () => this.focusMarker(type, item.id));
                    }

                    return marker;
                },

                createNamedMarkerIcon(color, emoji, labelClass, labelText, pulse = false) {
                    const safeText = this.escapeHtml(labelText);
                    const shortName = safeText.length > 16 ? `${safeText.slice(0, 14)}…` : safeText;

                    const html = `
                        <div class="named-map-marker" style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                            <div class="${pulse ? 'marker-pulse' : ''}" style="
                                background:${color};
                                width:30px;height:30px;border-radius:50%;
                                display:flex;align-items:center;justify-content:center;
                                border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.3);
                                font-size:14px;
                            ">${emoji}</div>
                            <div class="${labelClass}" title="${safeText}">${shortName}</div>
                        </div>`;

                    return L.divIcon({
                        html,
                        className: '',
                        iconSize: [96, 48],
                        iconAnchor: [48, 24],
                    });
                },

                createMarkerIcon(type, item) {
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

                    if (type === 'laporan') {
                        return this.createNamedMarkerIcon(
                            colors.laporan,
                            icons.laporan,
                            'laporan-marker-label',
                            item.label ?? item.title ?? 'Laporan',
                        );
                    }

                    if (type === 'relawan') {
                        return this.createNamedMarkerIcon(
                            colors.relawan,
                            icons.relawan,
                            'relawan-marker-label',
                            item.title ?? item.label ?? 'Relawan',
                            true,
                        );
                    }

                    if (type === 'faskes') {
                        return this.createNamedMarkerIcon(
                            colors.faskes,
                            icons.faskes,
                            'faskes-marker-label',
                            item.title ?? item.label ?? 'Faskes',
                        );
                    }

                    const html = `
                        <div style="
                            background:${colors[type]};
                            width:28px;height:28px;border-radius:50%;
                            display:flex;align-items:center;justify-content:center;
                            border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.3);
                            font-size:14px;
                        ">${icons[type]}</div>`;

                    return L.divIcon({
                        html,
                        className: '',
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                    });
                },

                buildPopup(type, item) {
                    if (type === 'laporan') {
                        const jenis = this.escapeHtml(item.label ?? 'Laporan');
                        const pelapor = this.escapeHtml(item.title ?? '-');
                        let rows = `<div class="laporan-popup"><div class="laporan-popup-name">⚠️ ${jenis}</div>`;
                        rows += `<div><b>Pelapor:</b> ${pelapor}</div>`;
                        if (item.subtitle) rows += `<div><b>Alamat:</b> ${this.escapeHtml(item.subtitle)}</div>`;
                        if (item.wilayah) rows += `<div><b>Wilayah:</b> ${this.escapeHtml(item.wilayah)}</div>`;
                        if (item.status_penanganan) rows += `<div><b>Penanganan:</b> ${this.escapeHtml(item.status_penanganan)}</div>`;
                        if (item.status) rows += `<div><b>Status:</b> ${this.escapeHtml(item.status)}</div>`;
                        if (item.relawan) rows += `<div><b>Relawan ditugaskan:</b> ${this.escapeHtml(item.relawan)}</div>`;
                        if (item.tanggal) rows += `<div><b>Waktu kejadian:</b> ${this.escapeHtml(item.tanggal)}</div>`;
                        if (item.jarak_km != null) rows += `<div><b>Jarak dari pusat:</b> ${item.jarak_km} km</div>`;
                        rows += `<div style="margin-top:4px;font-size:11px;color:#64748b">${item.latitude?.toFixed(5)}, ${item.longitude?.toFixed(5)}</div></div>`;
                        return rows;
                    }

                    if (type === 'relawan') {
                        const nama = this.escapeHtml(item.title ?? item.label ?? 'Relawan');
                        let rows = `<div class="relawan-popup"><div class="relawan-popup-name">🧑‍🚒 ${nama}</div>`;
                        if (item.keahlian) rows += `<div><b>Keahlian:</b> ${this.escapeHtml(item.keahlian)}</div>`;
                        if (item.organisasi) rows += `<div><b>Organisasi:</b> ${this.escapeHtml(item.organisasi)}</div>`;
                        else if (item.subtitle) rows += `<div><b>Organisasi:</b> ${this.escapeHtml(item.subtitle)}</div>`;
                        if (item.telepon) rows += `<div><b>Telepon:</b> ${this.escapeHtml(item.telepon)}</div>`;
                        if (item.email) rows += `<div><b>Email akun:</b> ${this.escapeHtml(item.email)}</div>`;
                        if (item.lokasi_updated_at) rows += `<div><b>Update lokasi:</b> ${this.formatTime(item.lokasi_updated_at)}</div>`;
                        if (item.jarak_km != null) rows += `<div><b>Jarak dari pusat:</b> ${item.jarak_km} km</div>`;
                        rows += `<div style="margin-top:4px;font-size:11px;color:#64748b">${item.latitude?.toFixed(5)}, ${item.longitude?.toFixed(5)}</div></div>`;
                        return rows;
                    }

                    if (type === 'faskes') {
                        const nama = this.escapeHtml(item.title ?? item.label ?? 'Faskes');
                        let rows = `<div class="faskes-popup"><div class="faskes-popup-name">🏥 ${nama}</div>`;
                        if (item.tipe_label || item.tipe) rows += `<div><b>Tipe:</b> ${this.escapeHtml(item.tipe_label || item.tipe)}</div>`;
                        if (item.alamat || item.subtitle) rows += `<div><b>Alamat:</b> ${this.escapeHtml(item.alamat || item.subtitle)}</div>`;
                        if (item.wilayah) rows += `<div><b>Wilayah:</b> ${this.escapeHtml(item.wilayah)}</div>`;
                        if (item.telepon) rows += `<div><b>Telepon:</b> ${this.escapeHtml(item.telepon)}</div>`;
                        if (item.jam_operasional) rows += `<div><b>Jam operasional:</b> ${this.escapeHtml(item.jam_operasional)}</div>`;
                        if (item.jarak_km != null) rows += `<div><b>Jarak dari pusat:</b> ${item.jarak_km} km</div>`;
                        rows += `<div style="margin-top:4px;font-size:11px;color:#64748b">${item.latitude?.toFixed(5)}, ${item.longitude?.toFixed(5)}</div></div>`;
                        return rows;
                    }

                    let rows = `<strong>${item.title ?? item.label}</strong>`;
                    if (item.subtitle) rows += `<br><span>${item.subtitle}</span>`;
                    if (item.status_penanganan) rows += `<br>Penanganan: <b>${item.status_penanganan}</b>`;
                    if (item.status) rows += `<br>Status: <b>${item.status}</b>`;
                    if (item.wilayah) rows += `<br>Wilayah: ${item.wilayah}`;
                    if (item.relawan) rows += `<br>Relawan ditugaskan: <b>${item.relawan}</b>`;
                    if (item.tanggal) rows += `<br>Waktu: ${item.tanggal}`;
                    if (item.lokasi_updated_at) rows += `<br>Update lokasi: ${this.formatTime(item.lokasi_updated_at)}`;
                    if (item.jarak_km != null) rows += `<br>Jarak: ${item.jarak_km} km`;
                    if (item.telepon) rows += `<br>Tel: ${item.telepon}`;
                    if (item.kapasitas) rows += `<br>Kapasitas: ${item.kapasitas}`;
                    return rows;
                },

                focusMarker(type, id) {
                    const key = `${type}-${id}`;
                    const marker = this.markerRegistry[key];
                    if (!marker || !this.map) return;

                    this.activeMarkerKey = key;
                    const targetZoom = Math.max(this.map.getZoom(), 16);
                    this.map.flyTo(marker.getLatLng(), targetZoom, { animate: true, duration: 0.75 });
                    marker.openPopup();
                },

                relawanInitials(name) {
                    if (!name) return 'R';
                    const parts = String(name).trim().split(/\s+/).filter(Boolean);
                    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
                    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
                },

                escapeHtml(value) {
                    return String(value ?? '')
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#39;');
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
