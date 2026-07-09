<template>
    <div :class="['master-shell min-h-screen', portalThemeClass]">
        <div ref="mapEl" class="master-map-bg" aria-hidden="true"></div>
        <div class="master-map-veil" aria-hidden="true"></div>

        <div
            class="glass-surface relative z-10 flex items-center justify-around gap-1 p-2 lg:hidden"
        >
            <Link
                v-for="item in navItems"
                :key="item.href"
                :href="item.href"
                :aria-label="item.label"
                :title="item.label"
                class="flex shrink-0 items-center justify-center rounded-lg p-2.5"
                :class="
                    route().current(item.pattern)
                        ? 'bg-white/60 text-slate-900'
                        : 'text-slate-500'
                "
            >
                <NavIcon :name="item.icon" class="h-5 w-5" />
            </Link>
        </div>

        <div class="relative z-10 mx-auto flex min-h-screen max-w-[1400px]">
            <aside class="hidden w-64 shrink-0 flex-col gap-6 p-5 lg:flex">
                <GlassPanel class="flex flex-1 flex-col gap-6">
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            {{ brandName }}
                        </p>
                        <h1 class="text-lg font-extrabold text-slate-900">
                            Кабінет майстра
                        </h1>
                    </div>

                    <nav class="flex flex-1 flex-col gap-1">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            class="rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                            :class="
                                route().current(item.pattern)
                                    ? 'glass-surface text-slate-900'
                                    : 'text-slate-600 hover:bg-white/40'
                            "
                        >
                            {{ item.label }}
                        </Link>
                    </nav>

                    <Link
                        :href="route('master-logout')"
                        method="post"
                        as="button"
                        class="rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-500 transition hover:bg-white/40"
                    >
                        Вийти
                    </Link>
                </GlassPanel>
            </aside>

            <main class="flex-1 p-4 lg:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, inject, onMounted, onUnmounted, ref } from 'vue';
import type { route as routeFn } from 'ziggy-js';
import GlassPanel from '../components/MasterCrm/GlassPanel.vue';
import NavIcon from '../components/MasterCrm/NavIcon.vue';
import { useBrand } from '../composables/useBrand';

// ZiggyVue only exposes `route()` as a global template property + a
// provide/inject pair (see app.ts's `.use(ZiggyVue, ...)`) — there is no
// real `window.route`, so it must be injected explicitly for use here in
// <script setup>, unlike template expressions where Vue resolves it via
// app.config.globalProperties automatically.
const route = inject<typeof routeFn>('route')!;

const { brandName, portalThemeClass } = useBrand();

// Purely decorative backdrop (locked to Kyiv, no interaction) — same tile
// provider as the public guest map (resources/js/composables/useGuestMap.ts)
// for visual consistency with the rest of the site. This layout is
// persistent across Inertia navigations (mounted once), so the map is too.
const KYIV_CENTER: [number, number] = [50.4501, 30.5234];
const mapEl = ref<HTMLElement | null>(null);
let map: L.Map | null = null;

onMounted(() => {
    if (!mapEl.value) return;

    map = L.map(mapEl.value, {
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

    L.tileLayer(
        `https://api.mapbox.com/styles/v1/rotting/claqrpplh000g14mmffvd0767/tiles/256/{z}/{x}/{y}@2x?access_token=${import.meta.env.VITE_MAPBOX_TOKEN}`,
        { maxZoom: 18, crossOrigin: true },
    ).addTo(map);
});

onUnmounted(() => {
    map?.remove();
    map = null;
});

const navItems = computed<
    {
        label: string;
        href: string;
        pattern: string;
        icon:
            | 'schedule'
            | 'appointments'
            | 'catalog'
            | 'clients'
            | 'finance'
            | 'settings';
    }[]
>(() => [
    {
        label: 'Розклад',
        href: route('master.schedule.index'),
        pattern: 'master.schedule.*',
        icon: 'schedule',
    },
    {
        label: 'Всі записи',
        href: route('master.appointments.index'),
        pattern: 'master.appointments.*',
        icon: 'appointments',
    },
    {
        label: 'Каталог послуг',
        href: route('master.catalog.index'),
        pattern: 'master.catalog.*',
        icon: 'catalog',
    },
    {
        label: 'Клієнти',
        href: route('master.clients.index'),
        pattern: 'master.clients.*',
        icon: 'clients',
    },
    {
        label: 'Фінанси',
        href: route('master.finance.index'),
        pattern: 'master.finance.*',
        icon: 'finance',
    },
    {
        label: 'Налаштування',
        href: route('master.settings.index'),
        pattern: 'master.settings.*',
        icon: 'settings',
    },
]);
</script>

<style scoped>
.master-shell {
    position: relative;
    overflow: hidden;
    background: #f4f6fa;
}

.master-map-bg {
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
.master-map-veil {
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
