<style>
    /* Memperbaiki konflik Leaflet vs Tailwind CSS yang menyembunyikan garis polygon */
    .leaflet-pane svg path,
    .leaflet-interactive {
        stroke: #ef4444 !important;
        stroke-width: 3px !important;
        stroke-opacity: 1 !important;
        fill: #ef4444 !important;
        fill-opacity: 0.2 !important;
    }
    
    /* Memastikan layer svg berada di posisinya */
    .leaflet-overlay-pane svg {
        pointer-events: none;
    }
    
    .leaflet-pane svg path.leaflet-interactive {
        pointer-events: visiblePainted;
        pointer-events: auto;
    }
</style>
