@php
    /** @var \App\Models\LaporanBencana $laporan */
    $lat = $laporan->latitude;
    $lng = $laporan->longitude;
@endphp

<div class="space-y-4">
    <div class="grid gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-2">
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400">Alamat</p>
            <p class="font-medium text-gray-800 dark:text-gray-100">
                {{ $laporan->alamat_lokasi ?: '—' }}
            </p>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400">Wilayah</p>
            <p class="font-medium text-gray-800 dark:text-gray-100">
                {{ $laporan->wilayah?->nama ?? '—' }}
            </p>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400">Koordinat</p>
            <p class="font-mono text-sm font-semibold text-gray-800 dark:text-gray-100">
                {{ $laporan->koordinatLabel() }}
            </p>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400">Di lokasi kejadian</p>
            <p class="font-medium text-gray-800 dark:text-gray-100">
                {{ $laporan->di_lokasi_kejadian ? 'Ya, pelapor di lokasi' : 'Tidak / tidak diketahui' }}
            </p>
        </div>
    </div>

    <div
        x-data="laporanLokasiMap({{ $lat }}, {{ $lng }}, @js($laporan->jenis_kejadian), @js($laporan->nama_pelapor))"
        x-init="init()"
        wire:ignore
    >
        <div
            x-ref="mapEl"
            class="overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-gray-700"
            style="height: 360px; width: 100%;"
        ></div>
    </div>
</div>

@once
    <script>
        function laporanLokasiMap(lat, lng, jenis, pelapor) {
            return {
                map: null,

                init() {
                    this.loadLeaflet(() => this.bootMap(lat, lng, jenis, pelapor));
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

                bootMap(lat, lng, jenis, pelapor) {
                    this.map = L.map(this.$refs.mapEl).setView([lat, lng], 16);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap',
                    }).addTo(this.map);

                    const icon = L.divIcon({
                        html: `<div style="
                            background:#dc2626;width:34px;height:34px;border-radius:50% 50% 50% 0;
                            transform:rotate(-45deg);border:3px solid white;
                            box-shadow:0 4px 12px rgba(0,0,0,.35);
                            display:flex;align-items:center;justify-content:center;">
                            <span style="transform:rotate(45deg);font-size:16px;">⚠️</span>
                        </div>`,
                        className: '',
                        iconSize: [34, 34],
                        iconAnchor: [17, 34],
                        popupAnchor: [0, -34],
                    });

                    L.marker([lat, lng], { icon })
                        .addTo(this.map)
                        .bindPopup(`<strong>${jenis}</strong><br>Pelapor: ${pelapor}<br><small>${lat.toFixed(5)}, ${lng.toFixed(5)}</small>`)
                        .openPopup();

                    setTimeout(() => this.map?.invalidateSize(), 350);
                },
            };
        }
    </script>
@endonce
