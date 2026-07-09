<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.entangle('{{ $getStatePath() }}'),
            map: null,
            drawnItems: null,
            initMap() {
                // Inisialisasi Peta
                this.map = L.map($refs.map).setView([-3.2384, 130.1453], 6);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(this.map);

                // Buat layer untuk item yang digambar
                this.drawnItems = new L.FeatureGroup();
                this.map.addLayer(this.drawnItems);

                // Tambahkan kontrol draw (Polygon & Rectangle saja)
                let drawControl = new L.Control.Draw({
                    draw: {
                        polyline: false,
                        circle: false,
                        marker: false,
                        circlemarker: false,
                        polygon: {
                            allowIntersection: false,
                            showArea: true,
                            drawError: {
                                color: '#e1e100',
                                message: 'Area tidak boleh tumpang tindih!'
                            },
                            shapeOptions: {
                                color: '#ef4444'
                            }
                        },
                        rectangle: {
                            shapeOptions: {
                                color: '#ef4444'
                            }
                        }
                    },
                    edit: {
                        featureGroup: this.drawnItems,
                        remove: true
                    }
                });
                this.map.addControl(drawControl);

                // Jika ada state awal (edit data)
                if (this.state && Array.isArray(this.state) && this.state.length > 0) {
                    let latlngs = this.state.map(point => [point.lat, point.lng]);
                    let polygon = L.polygon(latlngs, {color: '#ef4444'});
                    this.drawnItems.addLayer(polygon);
                    this.map.fitBounds(polygon.getBounds());
                }

                // Event ketika selesai menggambar
                this.map.on(L.Draw.Event.CREATED, (e) => {
                    let layer = e.layer;
                    
                    // Hapus layer lama (hanya izinkan 1 polygon)
                    this.drawnItems.clearLayers();
                    this.drawnItems.addLayer(layer);
                    
                    this.updateState();
                });

                // Event ketika diedit atau dihapus
                this.map.on(L.Draw.Event.EDITED, () => this.updateState());
                this.map.on(L.Draw.Event.DELETED, () => {
                    this.state = [];
                });
                
                // Fix map render issue in tabs/modals
                setTimeout(() => {
                    this.map.invalidateSize();
                }, 500);
            },
            updateState() {
                let layers = this.drawnItems.getLayers();
                if (layers.length > 0) {
                    let layer = layers[0];
                    let latlngs = layer.getLatLngs()[0]; // Ambil array koordinat
                    
                    // Format ke [{lat, lng}]
                    let formatted = latlngs.map(ll => ({
                        lat: ll.lat,
                        lng: ll.lng
                    }));
                    
                    this.state = formatted;
                } else {
                    this.state = [];
                }
            }
        }"
        x-init="
            if (typeof L === 'undefined') {
                // Muat Leaflet & Leaflet Draw CSS/JS
                let link1 = document.createElement('link');
                link1.rel = 'stylesheet';
                link1.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(link1);

                let link2 = document.createElement('link');
                link2.rel = 'stylesheet';
                link2.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css';
                document.head.appendChild(link2);

                let script1 = document.createElement('script');
                script1.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                document.head.appendChild(script1);

                script1.onload = () => {
                    let script2 = document.createElement('script');
                    script2.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js';
                    document.head.appendChild(script2);
                    
                    script2.onload = () => {
                        initMap();
                    };
                };
            } else if (typeof L.Control.Draw === 'undefined') {
                let script2 = document.createElement('script');
                script2.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js';
                document.head.appendChild(script2);
                
                script2.onload = () => {
                    initMap();
                };
            } else {
                initMap();
            }
        "
        wire:ignore
    >
        <div x-ref="map" style="height: 400px; width: 100%; z-index: 10; border-radius: 8px; border: 1px solid #e5e7eb;"></div>
        
        <!-- Fix CSS khusus untuk Tailwind + Leaflet Draw -->
        <style>
            .leaflet-draw-toolbar a {
                background-image: url('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/images/spritesheet.png') !important;
            }
            .leaflet-draw-toolbar a.leaflet-draw-draw-polygon {
                background-position: -31px -2px !important;
            }
            .leaflet-draw-toolbar a.leaflet-draw-draw-rectangle {
                background-position: -62px -2px !important;
            }
            .leaflet-draw-toolbar a.leaflet-draw-edit-edit {
                background-position: -150px -2px !important;
            }
            .leaflet-draw-toolbar a.leaflet-draw-edit-remove {
                background-position: -211px -2px !important;
            }
            .leaflet-retina .leaflet-draw-toolbar a {
                background-image: url('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/images/spritesheet-2x.png') !important;
                background-size: 300px 30px !important;
            }
            .leaflet-pane {
                z-index: 10;
            }
            .leaflet-top, .leaflet-bottom {
                z-index: 20;
            }
            path.leaflet-interactive {
                stroke: #ef4444 !important;
                stroke-width: 3px !important;
            }
        </style>
    </div>
</x-dynamic-component>
