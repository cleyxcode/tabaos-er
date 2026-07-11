<style>
    /*
     * Tailwind/Filament preflight mereset stroke & fill pada elemen SVG,
     * sehingga garis polygon Leaflet tidak terlihat saat menggambar maupun setelah disimpan.
     */
    .leaflet-container .leaflet-overlay-pane svg path,
    .leaflet-container .leaflet-overlay-pane svg polyline,
    .leaflet-container .leaflet-overlay-pane svg polygon,
    .leaflet-container .leaflet-zoom-animated path,
    .leaflet-container .leaflet-zoom-animated polyline,
    .leaflet-container .leaflet-zoom-animated polygon,
    .leaflet-container path.leaflet-interactive {
        vector-effect: non-scaling-stroke;
        stroke-opacity: 1 !important;
        fill-opacity: 0.2 !important;
    }

    .leaflet-container path.leaflet-interactive,
    .leaflet-container .leaflet-overlay-pane svg path,
    .leaflet-container .leaflet-overlay-pane svg polygon {
        stroke: #ef4444 !important;
        stroke-width: 3px !important;
        fill: #ef4444 !important;
    }

    .leaflet-container .leaflet-overlay-pane svg polyline {
        stroke: #ef4444 !important;
        stroke-width: 2px !important;
        fill: none !important;
    }

    /* Garis panduan saat menggambar polygon (sebelum Finish) */
    .leaflet-draw-guide-dash {
        stroke: #ef4444 !important;
        stroke-width: 4px !important;
        stroke-opacity: 0.85 !important;
        stroke-dasharray: 10, 10 !important;
        fill: none !important;
    }

    .leaflet-draw-toolbar a {
        background-image: url('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/images/spritesheet.png') !important;
    }

    @media (-webkit-min-device-pixel-ratio: 1.5), (min-resolution: 144dpi) {
        .leaflet-draw-toolbar a {
            background-image: url('https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/images/spritesheet-2x.png') !important;
            background-size: 300px 30px !important;
        }
    }

    .leaflet-pane { z-index: 10; }
    .leaflet-top, .leaflet-bottom { z-index: 20; }
</style>
