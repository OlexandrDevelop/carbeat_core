<template>
    <div ref="mapEl" class="map-backdrop-bg" aria-hidden="true"></div>
    <div class="map-backdrop-veil" aria-hidden="true"></div>
</template>

<script setup lang="ts">
// Type-only: Leaflet touches `window` merely by being imported, which
// crashes Node SSR, so the runtime module is loaded lazily in onMounted()
// below instead of via a static import. Any page using this component must
// avoid a static top-level `import 'leaflet'` for the same reason.
import type L from 'leaflet';
import { onMounted, onUnmounted, ref } from 'vue';

// Purely decorative backdrop (locked to Kyiv, no interaction) — same tile
// provider as the public guest map (resources/js/composables/useGuestMap.ts)
// for visual consistency across the master portal and public pages that use
// this component.
const KYIV_CENTER: [number, number] = [50.4501, 30.5234];
const mapEl = ref<HTMLElement | null>(null);
let map: L.Map | null = null;

onMounted(async () => {
    if (!mapEl.value) return;

    const Leaflet = (await import('leaflet')).default;
    await import('leaflet/dist/leaflet.css');
    if (!mapEl.value || map) return; // unmounted or already initialized while awaiting

    map = Leaflet.map(mapEl.value, {
        center: KYIV_CENTER,
        zoom: 13,
        zoomControl: false,
        dragging: false,
        touchZoom: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        keyboard: false,
        attributionControl: false,
    });

    Leaflet.tileLayer(
        `https://api.mapbox.com/styles/v1/rotting/claqrpplh000g14mmffvd0767/tiles/256/{z}/{x}/{y}@2x?access_token=${import.meta.env.VITE_MAPBOX_TOKEN}`,
        { maxZoom: 18, crossOrigin: true },
    ).addTo(map);
});

onUnmounted(() => {
    map?.remove();
    map = null;
});
</script>

<style scoped>
.map-backdrop-bg {
    position: fixed;
    inset: 0;
    z-index: 0;
    /* Purely decorative — blur it so it reads as texture behind the glass
       panels rather than a legible, distracting map. Scaled up slightly so
       the blur doesn't reveal the container's edges. */
    filter: blur(6px);
    transform: scale(1.08);
}

/* A light brand tint only — legibility comes from each GlassPanel's own
   backdrop-filter (see GlassPanel.vue), not from dimming the map itself. */
.map-backdrop-veil {
    position: fixed;
    inset: 0;
    z-index: 1;
    pointer-events: none;
    background: radial-gradient(
        circle at top left,
        rgba(var(--brand-primary-rgb), 0.14),
        transparent 55%
    );
}
</style>
