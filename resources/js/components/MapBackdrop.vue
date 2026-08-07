<template>
    <div
        ref="mapEl"
        class="map-backdrop-bg"
        :class="{ 'map-backdrop-bg--static': staticBackground }"
        :style="staticBackdropStyle"
        aria-hidden="true"
    ></div>
    <div class="map-backdrop-veil" aria-hidden="true"></div>
</template>

<script setup lang="ts">
// Type-only: Leaflet touches `window` merely by being imported, which
// crashes Node SSR, so the runtime module is loaded lazily in onMounted()
// below instead of via a static import. Any page using this component must
// avoid a static top-level `import 'leaflet'` for the same reason.
import type L from 'leaflet';
import { computed, onMounted, onUnmounted, ref } from 'vue';
// A single Mapbox Static Images API snapshot of the same Kyiv view the live
// map opens on (one HTTP request instead of ~15 tile requests), downscaled,
// Gaussian-blurred, and re-encoded to WebP (~2.8KB — regenerate by fetching
// https://api.mapbox.com/styles/v1/rotting/claqrpplh000g14mmffvd0767/static/30.5234,50.4501,13,0,0/640x400@2x
// and running it through PIL's GaussianBlur(6) before saving as WebP q40).
// Small enough that Vite inlines it as a data: URI at build time (default
// assetsInlineLimit is 4096 bytes), so the static backdrop costs zero extra
// network requests on top of the page's own CSS.
import staticBackdropUrl from '@/assets/repair-request-map-backdrop.webp';

// Purely decorative backdrop (locked to Kyiv, no interaction) — same tile
// provider as the public guest map (resources/js/composables/useGuestMap.ts)
// for visual consistency across the master portal and public pages that use
// this component.
const props = withDefaults(
    defineProps<{
        /**
         * Skips the live Leaflet/Mapbox tile map and renders a plain CSS
         * gradient instead. For PageSpeed-sensitive public pages (e.g. the
         * repair-request lead form): the live map is aria-hidden decoration
         * with no interaction, yet it pulls in the Leaflet bundle, ~15
         * Mapbox tile requests (~500KB), and — since those tiles load after
         * JS parses and Leaflet initializes — ends up being picked as the
         * page's LCP element, tanking LCP even though it's invisible detail
         * behind a 6px blur.
         */
        staticBackground?: boolean;
    }>(),
    { staticBackground: false },
);

const KYIV_CENTER: [number, number] = [50.4501, 30.5234];
const mapEl = ref<HTMLElement | null>(null);
let map: L.Map | null = null;

const staticBackdropStyle = computed(() =>
    props.staticBackground
        ? { backgroundImage: `url(${staticBackdropUrl})` }
        : undefined,
);

onMounted(async () => {
    if (props.staticBackground || !mapEl.value) return;

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

/* backgroundImage itself comes from the inline :style binding (the
   imported, pre-blurred WebP) — no live map, no tile requests, no JS. */
.map-backdrop-bg--static {
    background-color: #f4f6fa;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
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
