<template>
    <div class="min-h-screen bg-gray-50">
        <header class="sticky top-0 z-10 backdrop-blur bg-white/70 border-b border-gray-200">
            <div class="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Realtime Availability</h1>
                <div class="flex items-center gap-3 text-sm text-gray-600">
                    <span class="inline-flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full" :class="connected ? 'bg-emerald-500' : 'bg-red-500'"></span>
                        <span>{{ connected ? 'Connected' : 'Disconnected' }}</span>
                    </span>
                    <span class="hidden sm:inline text-gray-300">|</span>
                    <span class="hidden sm:inline">Last minute: <span class="font-medium">{{ stats.lastMinute }}</span></span>
                    <span class="hidden sm:inline">5 min: <span class="font-medium">{{ stats.last5min }}</span></span>
                    <span class="hidden sm:inline">15 min: <span class="font-medium">{{ stats.last15min }}</span></span>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-6 py-6 space-y-6">
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-900">Events (last {{ maxRows }})</h2>
                        <button @click="clearAll" class="text-sm rounded-lg border px-3 py-1.5 hover:bg-gray-50">Clear</button>
                    </div>
                    <div class="text-sm text-gray-500">Total: {{ events.length }}</div>
                </div>
                <div class="px-6 pb-4">
                    <svg :width="chartWidth" :height="chartHeight" viewBox="0 0 600 120" class="w-full h-28">
                        <polyline
                            :points="chartPoints"
                            fill="none"
                            stroke="#2563eb"
                            stroke-width="2"
                        />
                        <line x1="0" y1="100" x2="600" y2="100" stroke="#e5e7eb" />
                    </svg>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Master</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Expires At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="e in events" :key="e._k" class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm text-gray-600">{{ formatTs(e.ts) }}</td>
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">#{{ e.id }}</td>
                                <td class="px-6 py-3">
                                    <span v-if="e.available" class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">available</span>
                                    <span v-else class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">unavailable</span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ formatTs(e.expiresAt) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, reactive, ref, computed } from 'vue';
import { io } from 'socket.io-client';

// UI state
const connected = ref(false);
const maxRows = 500;
const events = reactive<Array<{ _k: string; id: number; available: boolean; expiresAt?: number | null; ts: number }>>([]);

// Chart: counts per minute for last 15 minutes
const windowMinutes = 15;
const buckets = reactive<number[]>(Array.from({ length: windowMinutes }, () => 0));
let bucketBaseTs = Math.floor(Date.now() / 1000 / 60); // minutes

function pushEvent(e: { id?: number; available?: boolean; expiresAt?: number | null; ts?: number }) {
    const id = typeof e.id === 'number' ? e.id : NaN;
    const available = typeof e.available === 'boolean' ? e.available : false;
    const ts = typeof e.ts === 'number' ? e.ts : Math.floor(Date.now() / 1000);
    const expiresAt = typeof e.expiresAt === 'number' ? e.expiresAt : null;
    if (!Number.isFinite(id)) return;

    // prepend newest
    events.unshift({ _k: `${id}-${ts}-${Math.random().toString(36).slice(2)}`, id, available, expiresAt, ts });
    if (events.length > maxRows) events.splice(maxRows);

    // update chart buckets
    const nowMin = Math.floor(Date.now() / 1000 / 60);
    if (nowMin !== bucketBaseTs) {
        const shift = Math.min(windowMinutes, nowMin - bucketBaseTs);
        for (let i = 0; i < shift; i++) buckets.pop();
        for (let i = 0; i < shift; i++) buckets.unshift(0);
        bucketBaseTs = nowMin;
    }
    buckets[0] = (buckets[0] || 0) + 1;
}

// Derived stats
const stats = reactive({ lastMinute: 0, last5min: 0, last15min: 0 });
function recomputeStats() {
    stats.lastMinute = buckets[0] || 0;
    stats.last5min = buckets.slice(0, 5).reduce((a, b) => a + b, 0);
    stats.last15min = buckets.reduce((a, b) => a + b, 0);
}

setInterval(recomputeStats, 1000);

// Simple sparkline points (600x100)
const chartWidth = 600;
const chartHeight = 120;
const chartPoints = computed(() => {
    const max = Math.max(1, ...buckets);
    const w = 600;
    const h = 100;
    const step = w / (windowMinutes - 1);
    const pts: string[] = [];
    for (let i = 0; i < windowMinutes; i++) {
        const x = i * step;
        const y = h - (buckets[windowMinutes - 1 - i] / max) * h; // right is newest
        pts.push(`${x},${y}`);
    }
    return pts.join(' ');
});

function formatTs(ts?: number | null): string {
    if (!ts) return '';
    try {
        return new Date(ts * 1000).toISOString();
    } catch (_) {
        return '';
    }
}

function clearAll() {
    events.splice(0, events.length);
}

let socket: any = null;
onMounted(() => {
    try {
        socket = io('/', { path: '/socket.io/' });
        socket.on('connect', () => { connected.value = true; });
        socket.on('disconnect', () => { connected.value = false; });
        socket.on('availability:update', (m: any) => {
            if (m && typeof m === 'object') {
                pushEvent({ id: m.id, available: !!m.available, expiresAt: m.expiresAt ?? null, ts: m.ts ?? Math.floor(Date.now()/1000) });
            }
        });
    } catch (e) {
        connected.value = false;
    }
});

onUnmounted(() => {
    try { socket?.disconnect?.(); } catch (_) {}
});
</script>

<style scoped>
</style>


