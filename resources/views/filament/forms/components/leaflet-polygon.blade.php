<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    {{--
        Outer div owns the Alpine component so the map and the hidden input
        share the same `coords` state. wire:ignore is applied only to the
        map container so Livewire never re-renders the Leaflet DOM, but the
        hidden input (outside wire:ignore) is still synced on every request.
    --}}
    <div x-data="leafletPolygon({{ json_encode($getState() ?: []) }})" x-init="init()">

        {{-- Leaflet map – protected from Livewire re-renders --}}
        <div wire:ignore>
            <div
                x-ref="mapEl"
                style="height: 420px; width: 100%; border-radius: 8px; border: 1px solid #e5e7eb;"
            ></div>
        </div>

        {{-- Hidden input Livewire reads on form save; kept outside wire:ignore --}}
        <input
            type="hidden"
            x-bind:value="JSON.stringify(coords)"
            wire:model="{{ $getStatePath() }}"
        >

        {{-- Fix Tailwind/Leaflet z-index conflicts and polygon fill colour --}}
        <style>
            /* Z-index agar peta tidak tertimpa elemen Filament */
            .leaflet-pane                 { z-index: 10; }
            .leaflet-top, .leaflet-bottom { z-index: 20; }

            /* Ikon toolbar draw (sprite sheet) */
            .leaflet-draw-toolbar a {
                background-image: url('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/images/spritesheet.png') !important;
            }
            @media (-webkit-min-device-pixel-ratio: 1.5), (min-resolution: 144dpi) {
                .leaflet-draw-toolbar a {
                    background-image: url('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/images/spritesheet-2x.png') !important;
                    background-size: 300px 30px !important;
                }
            }

            /*
             * Tailwind base styles mereset SVG sehingga semua path/polyline tidak punya
             * stroke/fill. Selector di bawah ini mengembalikannya untuk semua elemen
             * Leaflet — baik yang sudah jadi maupun yang sedang digambar.
             */
            .leaflet-overlay-pane svg path,
            .leaflet-overlay-pane svg polyline,
            .leaflet-overlay-pane svg polygon,
            .leaflet-zoom-animated path,
            .leaflet-zoom-animated polyline,
            .leaflet-zoom-animated polygon {
                vector-effect: non-scaling-stroke;
            }

            /* Polygon / rectangle selesai digambar */
            .leaflet-interactive {
                stroke:         #ef4444 !important;
                stroke-width:   3       !important;
                stroke-opacity: 1       !important;
                fill:           #ef4444 !important;
                fill-opacity:   0.2     !important;
            }

            /* Garis panduan saat sedang menggambar (sebelum di-Finish) */
            .leaflet-draw-guide-dash {
                stroke:         #ef4444 !important;
                stroke-opacity: 0.8     !important;
            }

            /* Garis bantu (polyline sementara) Leaflet.Draw */
            .leaflet-overlay-pane svg polyline {
                stroke:         #ef4444 !important;
                stroke-width:   2px     !important;
                stroke-opacity: 1       !important;
                fill:           none    !important;
            }
        </style>

        <script>
            function leafletPolygon(initialCoords) {
                return {
                    coords:     Array.isArray(initialCoords) ? initialCoords : [],
                    map:        null,
                    drawnItems: null,

                    init() {
                        this.loadDeps(() => this.bootMap());
                    },

                    /**
                     * Dynamically load Leaflet + Leaflet.Draw from CDN.
                     * Guards against double-loading when multiple maps exist on one page.
                     */
                    loadDeps(callback) {
                        if (window.L && window.L.Control && window.L.Control.Draw) {
                            callback();
                            return;
                        }

                        const head = document.head;

                        const addLink = (href) => {
                            if (document.querySelector(`link[href="${href}"]`)) return;
                            const el = document.createElement('link');
                            el.rel   = 'stylesheet';
                            el.href  = href;
                            head.appendChild(el);
                        };

                        const addScript = (src, onload) => {
                            // If the tag already exists, poll until the library is ready
                            if (document.querySelector(`script[src="${src}"]`)) {
                                const poll = setInterval(() => {
                                    const ready = src.includes('draw')
                                        ? window.L && window.L.Control && window.L.Control.Draw
                                        : !!window.L;
                                    if (ready) { clearInterval(poll); onload(); }
                                }, 50);
                                return;
                            }
                            const el   = document.createElement('script');
                            el.src     = src;
                            el.onload  = onload;
                            head.appendChild(el);
                        };

                        addLink('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
                        addLink('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css');

                        const drawSrc = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js';

                        if (!window.L) {
                            addScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', () => {
                                addScript(drawSrc, callback);
                            });
                        } else {
                            addScript(drawSrc, callback);
                        }
                    },

                    bootMap() {
                        this.map = L.map(this.$refs.mapEl).setView([-3.2384, 130.1453], 7);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom:     19,
                            attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
                        }).addTo(this.map);

                        this.drawnItems = new L.FeatureGroup();
                        this.map.addLayer(this.drawnItems);

                        this.map.addControl(new L.Control.Draw({
                            draw: {
                                polyline:     false,
                                circle:       false,
                                marker:       false,
                                circlemarker: false,
                                polygon: {
                                    allowIntersection: false,
                                    showArea:          true,
                                    shapeOptions: {
                                        color:       '#ef4444',
                                        weight:      3,
                                        opacity:     1,
                                        fillColor:   '#ef4444',
                                        fillOpacity: 0.2,
                                    },
                                },
                                rectangle: {
                                    shapeOptions: {
                                        color:       '#ef4444',
                                        weight:      3,
                                        opacity:     1,
                                        fillColor:   '#ef4444',
                                        fillOpacity: 0.2,
                                    },
                                },
                            },
                            edit: { featureGroup: this.drawnItems, remove: true },
                        }));

                        // Restore saved polygon when editing an existing record
                        if (this.coords.length > 0) {
                            const latlngs = this.coords.map(p => [p.lat, p.lng]);
                            const poly    = L.polygon(latlngs, {
                                color:       '#ef4444',
                                weight:      3,
                                opacity:     1,
                                fillColor:   '#ef4444',
                                fillOpacity: 0.2,
                            });
                            this.drawnItems.addLayer(poly);
                            this.map.fitBounds(poly.getBounds(), { padding: [40, 40] });
                        }

                        // Only one shape at a time — clear before adding the new one
                        this.map.on(L.Draw.Event.CREATED, (e) => {
                            this.drawnItems.clearLayers();
                            this.drawnItems.addLayer(e.layer);
                            this.syncCoords();
                        });

                        this.map.on(L.Draw.Event.EDITED,  () => this.syncCoords());

                        this.map.on(L.Draw.Event.DELETED, () => {
                            this.coords = [];
                        });

                        // Leaflet needs a nudge when rendered inside hidden tabs/panels
                        setTimeout(() => this.map.invalidateSize(), 400);
                    },

                    syncCoords() {
                        const layers = this.drawnItems.getLayers();
                        if (!layers.length) { this.coords = []; return; }
                        // getLatLngs()[0] returns the outer ring for both Polygon and Rectangle
                        this.coords = layers[0].getLatLngs()[0].map(ll => ({ lat: ll.lat, lng: ll.lng }));
                    },
                };
            }
        </script>

    </div>{{-- /.x-data --}}
</x-dynamic-component>
