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
        <div class="peta-map-legend" aria-label="Legenda peta">
            <span class="peta-legend-item peta-legend-item--laporan" x-text="`Laporan (${mapData.counts?.laporan ?? 0})`"></span>
            <span class="peta-legend-item peta-legend-item--relawan" x-text="`Relawan (${mapData.counts?.relawan ?? 0})`"></span>
            <span class="peta-legend-item peta-legend-item--faskes" x-text="`Faskes (${mapData.counts?.faskes ?? 0})`"></span>
            <span class="peta-legend-item peta-legend-item--evakuasi" x-text="`Evakuasi (${mapData.counts?.evakuasi ?? 0})`"></span>
            <span class="peta-legend-item peta-legend-item--petugas" x-text="`Petugas (${mapData.counts?.petugas ?? 0})`"></span>
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
    <div wire:ignore class="peta-map-shell">
        <div
            x-ref="mapEl"
            class="peta-map-canvas rounded-xl border border-gray-200 shadow-sm dark:border-gray-700"
            style="height: 620px; width: 100%;"
        ></div>
    </div>

    {{-- Panel data di bawah peta --}}
    <div class="peta-data-panel">
        <div class="peta-data-panel__header">
            <div>
                <h3 class="peta-data-panel__title">Data Lokasi di Peta</h3>
                <p class="peta-data-panel__subtitle">Klik kartu untuk zoom otomatis ke titik lokasi</p>
            </div>
            <div class="peta-data-panel__stats">
                <span class="peta-stat peta-stat--laporan" x-text="`${mapData.counts?.laporan ?? 0} Laporan`"></span>
                <span class="peta-stat peta-stat--relawan" x-text="`${mapData.counts?.relawan ?? 0} Relawan`"></span>
                <span class="peta-stat peta-stat--faskes" x-text="`${mapData.counts?.faskes ?? 0} Faskes`"></span>
            </div>
        </div>

        <div class="peta-data-tabs">
            <button type="button" class="peta-data-tab" :class="{ 'is-active': activeTab === 'laporan' }" x-on:click="activeTab = 'laporan'">
                <span>🔴 Laporan</span>
                <span class="peta-data-tab__count" x-text="mapData.counts?.laporan ?? 0"></span>
            </button>
            <button type="button" class="peta-data-tab" :class="{ 'is-active': activeTab === 'relawan' }" x-on:click="activeTab = 'relawan'">
                <span>🔵 Relawan</span>
                <span class="peta-data-tab__count" x-text="mapData.counts?.relawan ?? 0"></span>
            </button>
            <button type="button" class="peta-data-tab" :class="{ 'is-active': activeTab === 'faskes' }" x-on:click="activeTab = 'faskes'">
                <span>🟢 Faskes</span>
                <span class="peta-data-tab__count" x-text="mapData.counts?.faskes ?? 0"></span>
            </button>
        </div>

        {{-- Laporan --}}
        <div x-show="activeTab === 'laporan'" class="peta-data-tab-panel">
            <template x-if="(mapData.laporan ?? []).length === 0">
                <div class="peta-data-empty">Belum ada laporan aktif yang ditampilkan di peta.</div>
            </template>
            <div class="peta-data-grid">
                <template x-for="laporan in (mapData.laporan ?? [])" :key="`laporan-list-${laporan.id}`">
                    <button
                        type="button"
                        x-on:click="focusMarker('laporan', laporan.id)"
                        class="peta-data-card peta-data-card--laporan"
                        :class="{ 'is-active': activeMarkerKey === `laporan-${laporan.id}` }"
                    >
                        <div class="peta-data-card__top">
                            <span class="peta-data-card__icon">⚠️</span>
                            <div class="peta-data-card__main">
                                <span class="peta-data-card__title" x-text="laporan.label"></span>
                                <span class="peta-data-card__subtitle" x-text="`Pelapor: ${laporan.title}`"></span>
                            </div>
                            <span class="peta-badge peta-badge--red" x-text="formatStatusPenanganan(laporan.status_penanganan)"></span>
                        </div>
                        <div class="peta-data-card__meta">
                            <span x-show="laporan.wilayah">📍 <span x-text="laporan.wilayah"></span></span>
                            <span x-show="laporan.tanggal">🕒 <span x-text="laporan.tanggal"></span></span>
                        </div>
                        <div class="peta-data-card__address" x-show="laporan.subtitle" x-text="laporan.subtitle"></div>
                        <div class="peta-data-card__footer">
                            <span class="peta-data-card__coord" x-text="formatCoords(laporan)"></span>
                            <span class="peta-data-card__action">Lihat di peta →</span>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        {{-- Relawan --}}
        <div x-show="activeTab === 'relawan'" class="peta-data-tab-panel">
            <template x-if="(mapData.relawan ?? []).length === 0">
                <div class="peta-data-empty">Belum ada relawan aktif dengan lokasi terbaru di peta.</div>
            </template>
            <div class="peta-data-grid">
                <template x-for="relawan in (mapData.relawan ?? [])" :key="`relawan-list-${relawan.id}`">
                    <button
                        type="button"
                        x-on:click="focusMarker('relawan', relawan.id)"
                        class="peta-data-card peta-data-card--relawan"
                        :class="{ 'is-active': activeMarkerKey === `relawan-${relawan.id}` }"
                    >
                        <div class="peta-data-card__top">
                            <span class="peta-data-card__avatar" x-text="relawanInitials(relawan.title)"></span>
                            <div class="peta-data-card__main">
                                <span class="peta-data-card__title" x-text="relawan.title"></span>
                                <span class="peta-data-card__subtitle" x-text="relawan.keahlian || 'Keahlian belum diisi'"></span>
                            </div>
                            <span class="peta-badge peta-badge--blue">Aktif</span>
                        </div>
                        <div class="peta-data-card__meta">
                            <span x-show="relawan.organisasi || relawan.subtitle">🏢 <span x-text="relawan.organisasi || relawan.subtitle"></span></span>
                            <span x-show="relawan.telepon">📞 <span x-text="relawan.telepon"></span></span>
                            <span>🕒 Update <span x-text="formatTime(relawan.lokasi_updated_at)"></span></span>
                        </div>
                        <div class="peta-data-card__footer">
                            <span class="peta-data-card__coord" x-text="formatCoords(relawan)"></span>
                            <span class="peta-data-card__action">Lihat di peta →</span>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        {{-- Faskes --}}
        <div x-show="activeTab === 'faskes'" class="peta-data-tab-panel">
            <template x-if="(mapData.faskes ?? []).length === 0">
                <div class="peta-data-empty">Belum ada faskes yang ditampilkan di peta.</div>
            </template>
            <div class="peta-data-grid">
                <template x-for="faskes in (mapData.faskes ?? [])" :key="`faskes-list-${faskes.id}`">
                    <button
                        type="button"
                        x-on:click="focusMarker('faskes', faskes.id)"
                        class="peta-data-card peta-data-card--faskes"
                        :class="{ 'is-active': activeMarkerKey === `faskes-${faskes.id}` }"
                    >
                        <div class="peta-data-card__top">
                            <span class="peta-data-card__icon">🏥</span>
                            <div class="peta-data-card__main">
                                <span class="peta-data-card__title" x-text="faskes.title"></span>
                                <span class="peta-data-card__subtitle" x-text="faskes.tipe_label || faskes.tipe || 'Faskes'"></span>
                            </div>
                            <span class="peta-badge peta-badge--green" x-text="faskes.tipe_label || 'Faskes'"></span>
                        </div>
                        <div class="peta-data-card__meta">
                            <span x-show="faskes.wilayah">📍 <span x-text="faskes.wilayah"></span></span>
                            <span x-show="faskes.telepon">📞 <span x-text="faskes.telepon"></span></span>
                            <span x-show="faskes.jam_operasional">🕒 <span x-text="faskes.jam_operasional"></span></span>
                        </div>
                        <div class="peta-data-card__address" x-show="faskes.alamat || faskes.subtitle" x-text="faskes.alamat || faskes.subtitle"></div>
                        <div class="peta-data-card__footer">
                            <span class="peta-data-card__coord" x-text="formatCoords(faskes)"></span>
                            <span class="peta-data-card__action">Lihat di peta →</span>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        {{-- Detail item terpilih --}}
        <div x-show="selectedItem" class="peta-data-detail">
            <div class="peta-data-detail__header">
                <span class="peta-data-detail__label" x-text="selectedItem?.typeLabel"></span>
                <button type="button" class="peta-data-detail__close" x-on:click="clearSelection()">✕ Tutup</button>
            </div>
            <div class="peta-data-detail__title" x-text="selectedItem?.title"></div>
            <div class="peta-data-detail__rows">
                <template x-for="row in (selectedItem?.rows ?? [])" :key="row.label">
                    <div class="peta-data-detail__row" x-show="row.value">
                        <span class="peta-data-detail__key" x-text="row.label"></span>
                        <span class="peta-data-detail__value" x-text="row.value"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @once
        <style>
            .peta-map-shell { position: relative; }
            .peta-map-canvas { z-index: 0; }
            .peta-map-legend {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: center;
                gap: 6px;
                max-width: 100%;
            }
            .peta-legend-item {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 4px 10px;
                border-radius: 999px;
                font-size: 11px;
                font-weight: 700;
                line-height: 1.2;
                white-space: nowrap;
                border: 1px solid transparent;
            }
            .peta-legend-item::before {
                content: '';
                width: 8px;
                height: 8px;
                border-radius: 50%;
                flex-shrink: 0;
            }
            .peta-legend-item--laporan {
                background: #fee2e2;
                color: #b91c1c;
                border-color: #fecaca;
            }
            .peta-legend-item--laporan::before { background: #ef4444; }
            .peta-legend-item--relawan {
                background: #dbeafe;
                color: #1d4ed8;
                border-color: #bfdbfe;
            }
            .peta-legend-item--relawan::before { background: #3b82f6; }
            .peta-legend-item--faskes {
                background: #dcfce7;
                color: #15803d;
                border-color: #bbf7d0;
            }
            .peta-legend-item--faskes::before { background: #16a34a; }
            .peta-legend-item--evakuasi {
                background: #f3e8ff;
                color: #7e22ce;
                border-color: #e9d5ff;
            }
            .peta-legend-item--evakuasi::before { background: #9333ea; }
            .peta-legend-item--petugas {
                background: #fef3c7;
                color: #b45309;
                border-color: #fde68a;
            }
            .peta-legend-item--petugas::before { background: #f59e0b; }
            .leaflet-pane { z-index: 10; }
            .leaflet-top, .leaflet-bottom { z-index: 20; }
            .leaflet-bottom.leaflet-left,
            .leaflet-bottom.leaflet-right {
                margin-bottom: 8px;
            }
            .leaflet-bottom.leaflet-left { margin-left: 8px; }
            .leaflet-bottom.leaflet-right { margin-right: 8px; }
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

            /* Panel data bawah peta */
            .peta-data-panel {
                border: 1px solid #e5e7eb;
                border-radius: 16px;
                background: #fff;
                box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
                overflow: hidden;
            }
            .dark .peta-data-panel {
                border-color: #374151;
                background: #111827;
            }
            .peta-data-panel__header {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 16px 20px;
                border-bottom: 1px solid #f1f5f9;
                background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
            }
            .dark .peta-data-panel__header {
                border-bottom-color: #1f2937;
                background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
            }
            .peta-data-panel__title {
                margin: 0;
                font-size: 16px;
                font-weight: 800;
                color: #0f172a;
            }
            .dark .peta-data-panel__title { color: #f8fafc; }
            .peta-data-panel__subtitle {
                margin: 4px 0 0;
                font-size: 12px;
                color: #64748b;
            }
            .peta-data-panel__stats {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .peta-stat {
                padding: 4px 10px;
                border-radius: 999px;
                font-size: 11px;
                font-weight: 700;
            }
            .peta-stat--laporan { background: #fee2e2; color: #b91c1c; }
            .peta-stat--relawan { background: #dbeafe; color: #1d4ed8; }
            .peta-stat--faskes { background: #dcfce7; color: #15803d; }

            .peta-data-tabs {
                display: flex;
                gap: 4px;
                padding: 12px 16px 0;
                border-bottom: 1px solid #e2e8f0;
                background: #f8fafc;
            }
            .dark .peta-data-tabs {
                background: #0f172a;
                border-bottom-color: #334155;
            }
            .peta-data-tab {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 16px;
                border: none;
                border-bottom: 2px solid transparent;
                background: transparent;
                font-size: 13px;
                font-weight: 600;
                color: #64748b;
                cursor: pointer;
                transition: all 0.15s ease;
            }
            .peta-data-tab:hover { color: #0f172a; }
            .dark .peta-data-tab:hover { color: #e2e8f0; }
            .peta-data-tab.is-active {
                color: #0f172a;
                border-bottom-color: #2563eb;
                background: #fff;
                border-radius: 8px 8px 0 0;
            }
            .dark .peta-data-tab.is-active {
                color: #f8fafc;
                background: #111827;
            }
            .peta-data-tab__count {
                min-width: 22px;
                padding: 1px 7px;
                border-radius: 999px;
                background: #e2e8f0;
                font-size: 11px;
                text-align: center;
            }
            .peta-data-tab.is-active .peta-data-tab__count {
                background: #dbeafe;
                color: #1d4ed8;
            }

            .peta-data-tab-panel { padding: 16px; }
            .peta-data-empty {
                padding: 32px 16px;
                text-align: center;
                font-size: 13px;
                color: #94a3b8;
                border: 1px dashed #cbd5e1;
                border-radius: 12px;
                background: #f8fafc;
            }
            .dark .peta-data-empty {
                background: #1e293b;
                border-color: #475569;
                color: #94a3b8;
            }
            .peta-data-grid {
                display: grid;
                gap: 12px;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
            .peta-data-card {
                display: block;
                width: 100%;
                text-align: left;
                padding: 14px;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                background: #fff;
                cursor: pointer;
                transition: all 0.18s ease;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            }
            .dark .peta-data-card {
                background: #1e293b;
                border-color: #334155;
            }
            .peta-data-card:hover {
                transform: translateY(-1px);
                box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            }
            .peta-data-card.is-active {
                border-width: 2px;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            }
            .peta-data-card--laporan.is-active { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15); }
            .peta-data-card--relawan.is-active { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
            .peta-data-card--faskes.is-active { border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15); }

            .peta-data-card__top {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                margin-bottom: 10px;
            }
            .peta-data-card__icon,
            .peta-data-card__avatar {
                flex-shrink: 0;
                width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 800;
                color: #fff;
            }
            .peta-data-card--laporan .peta-data-card__icon { background: #ef4444; }
            .peta-data-card--relawan .peta-data-card__avatar { background: #3b82f6; font-size: 12px; }
            .peta-data-card--faskes .peta-data-card__icon { background: #22c55e; }

            .peta-data-card__main { flex: 1; min-width: 0; }
            .peta-data-card__title {
                display: block;
                font-size: 14px;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.3;
            }
            .dark .peta-data-card__title { color: #f1f5f9; }
            .peta-data-card__subtitle {
                display: block;
                margin-top: 2px;
                font-size: 12px;
                color: #64748b;
            }
            .peta-badge {
                flex-shrink: 0;
                padding: 3px 8px;
                border-radius: 999px;
                font-size: 10px;
                font-weight: 700;
                white-space: nowrap;
            }
            .peta-badge--red { background: #fee2e2; color: #b91c1c; }
            .peta-badge--blue { background: #dbeafe; color: #1d4ed8; }
            .peta-badge--green { background: #dcfce7; color: #15803d; }

            .peta-data-card__meta {
                display: flex;
                flex-wrap: wrap;
                gap: 8px 14px;
                margin-bottom: 8px;
                font-size: 11px;
                color: #475569;
            }
            .dark .peta-data-card__meta { color: #94a3b8; }
            .peta-data-card__address {
                margin-bottom: 10px;
                padding: 8px 10px;
                border-radius: 8px;
                background: #f8fafc;
                font-size: 11px;
                color: #334155;
                line-height: 1.45;
            }
            .dark .peta-data-card__address {
                background: #0f172a;
                color: #cbd5e1;
            }
            .peta-data-card__footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                padding-top: 10px;
                border-top: 1px solid #f1f5f9;
                font-size: 11px;
            }
            .dark .peta-data-card__footer { border-top-color: #334155; }
            .peta-data-card__coord {
                font-family: ui-monospace, monospace;
                color: #64748b;
            }
            .peta-data-card__action {
                font-weight: 700;
                color: #2563eb;
            }

            .peta-data-detail {
                margin: 0 16px 16px;
                padding: 16px;
                border-radius: 14px;
                border: 1px solid #bfdbfe;
                background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
            }
            .dark .peta-data-detail {
                border-color: #1e40af;
                background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            }
            .peta-data-detail__header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 8px;
            }
            .peta-data-detail__label {
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: #2563eb;
            }
            .peta-data-detail__close {
                border: none;
                background: transparent;
                font-size: 12px;
                font-weight: 600;
                color: #64748b;
                cursor: pointer;
            }
            .peta-data-detail__title {
                font-size: 18px;
                font-weight: 800;
                color: #0f172a;
                margin-bottom: 12px;
            }
            .dark .peta-data-detail__title { color: #f8fafc; }
            .peta-data-detail__rows {
                display: grid;
                gap: 8px;
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }
            .peta-data-detail__row {
                padding: 8px 10px;
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.7);
            }
            .dark .peta-data-detail__row { background: rgba(15, 23, 42, 0.5); }
            .peta-data-detail__key {
                display: block;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #64748b;
                margin-bottom: 2px;
            }
            .peta-data-detail__value {
                display: block;
                font-size: 13px;
                font-weight: 600;
                color: #0f172a;
                word-break: break-word;
            }
            .dark .peta-data-detail__value { color: #e2e8f0; }
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
                activeTab: 'laporan',
                selectedItem: null,

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
                    this.activeTab = type;
                    this.selectedItem = this.buildSelectedItem(type, id);

                    const targetZoom = Math.max(this.map.getZoom(), 16);
                    this.map.flyTo(marker.getLatLng(), targetZoom, { animate: true, duration: 0.75 });
                    marker.openPopup();
                },

                clearSelection() {
                    this.activeMarkerKey = null;
                    this.selectedItem = null;
                },

                buildSelectedItem(type, id) {
                    const list = this.mapData[type] ?? [];
                    const item = list.find(entry => entry.id === id);
                    if (!item) return null;

                    if (type === 'laporan') {
                        return {
                            typeLabel: 'Detail Laporan',
                            title: item.label,
                            rows: [
                                { label: 'Pelapor', value: item.title },
                                { label: 'Alamat', value: item.subtitle },
                                { label: 'Wilayah', value: item.wilayah },
                                { label: 'Status', value: item.status },
                                { label: 'Penanganan', value: this.formatStatusPenanganan(item.status_penanganan) },
                                { label: 'Relawan ditugaskan', value: item.relawan },
                                { label: 'Waktu kejadian', value: item.tanggal },
                                { label: 'Koordinat', value: this.formatCoords(item) },
                            ],
                        };
                    }

                    if (type === 'relawan') {
                        return {
                            typeLabel: 'Detail Relawan',
                            title: item.title,
                            rows: [
                                { label: 'Keahlian', value: item.keahlian },
                                { label: 'Organisasi', value: item.organisasi || item.subtitle },
                                { label: 'Telepon', value: item.telepon },
                                { label: 'Email akun', value: item.email },
                                { label: 'Update lokasi', value: this.formatTime(item.lokasi_updated_at) },
                                { label: 'Koordinat', value: this.formatCoords(item) },
                            ],
                        };
                    }

                    if (type === 'faskes') {
                        return {
                            typeLabel: 'Detail Faskes',
                            title: item.title,
                            rows: [
                                { label: 'Tipe', value: item.tipe_label || item.tipe },
                                { label: 'Alamat', value: item.alamat || item.subtitle },
                                { label: 'Wilayah', value: item.wilayah },
                                { label: 'Telepon', value: item.telepon },
                                { label: 'Jam operasional', value: item.jam_operasional },
                                { label: 'Koordinat', value: this.formatCoords(item) },
                            ],
                        };
                    }

                    return null;
                },

                formatCoords(item) {
                    if (item?.latitude == null || item?.longitude == null) return '-';
                    return `${item.latitude.toFixed(5)}, ${item.longitude.toFixed(5)}`;
                },

                formatStatusPenanganan(status) {
                    const labels = {
                        belum_ditangani: 'Belum Ditangani',
                        sedang_ditangani: 'Sedang Ditangani',
                        selesai_ditangani: 'Selesai Ditangani',
                    };
                    return labels[status] ?? status ?? '-';
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
