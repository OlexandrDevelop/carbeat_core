<template>
    <div class="min-h-screen bg-gray-50">
        <header
            class="sticky top-0 z-10 border-b border-gray-200 bg-white/70 backdrop-blur"
        >
            <div class="mx-auto max-w-7xl px-6 py-4">
                <h1 class="text-2xl font-semibold text-gray-900">
                    Visit Monitoring
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Sessions, pageviews and clicks on the public site.
                </p>
            </div>
        </header>

        <main class="mx-auto max-w-7xl space-y-6 px-6 py-6">
            <div
                v-if="statsError"
                class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                Couldn't load the stats above — showing zeros until this is
                fixed.
                <button
                    @click="fetchStats"
                    class="ml-1 font-medium underline hover:no-underline"
                >
                    Retry
                </button>
            </div>

            <!-- Stats cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="text-sm font-medium text-gray-500">
                        Sessions Today
                    </div>
                    <div class="mt-1 text-3xl font-bold text-emerald-600">
                        {{ stats.sessions_today }}
                    </div>
                </div>
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="text-sm font-medium text-gray-500">
                        Sessions (30d)
                    </div>
                    <div class="mt-1 text-3xl font-bold text-gray-900">
                        {{ stats.total_sessions }}
                    </div>
                </div>
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="text-sm font-medium text-gray-500">
                        Pageviews (30d)
                    </div>
                    <div class="mt-1 text-3xl font-bold text-gray-900">
                        {{ stats.total_pageviews }}
                    </div>
                </div>
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="text-sm font-medium text-gray-500">
                        Clicks (30d)
                    </div>
                    <div class="mt-1 text-3xl font-bold text-gray-900">
                        {{ stats.total_clicks }}
                    </div>
                </div>
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="text-sm font-medium text-gray-500">
                        Avg. Time on Site
                    </div>
                    <div class="mt-1 text-3xl font-bold text-gray-900">
                        {{ formatDuration(stats.avg_duration_seconds) }}
                    </div>
                </div>
            </div>

            <!-- Top referrers / top pages -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <section
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <h2 class="mb-3 text-sm font-semibold text-gray-900">
                        Top Referrers (30d)
                    </h2>
                    <ul class="space-y-1 text-sm">
                        <li
                            v-if="stats.top_referrers.length === 0"
                            class="text-gray-400"
                        >
                            No referrer data yet
                        </li>
                        <li
                            v-for="row in stats.top_referrers"
                            :key="row.referrer_host"
                            class="flex items-center justify-between"
                        >
                            <span class="truncate text-gray-700">{{
                                row.referrer_host
                            }}</span>
                            <span
                                class="ml-2 shrink-0 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"
                                >{{ row.c }}</span
                            >
                        </li>
                    </ul>
                </section>
                <section
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <h2 class="mb-3 text-sm font-semibold text-gray-900">
                        Top Pages (30d)
                    </h2>
                    <ul class="space-y-1 text-sm">
                        <li
                            v-if="stats.top_pages.length === 0"
                            class="text-gray-400"
                        >
                            No pageview data yet
                        </li>
                        <li
                            v-for="row in stats.top_pages"
                            :key="row.path"
                            class="flex items-center justify-between"
                        >
                            <span
                                class="truncate font-mono text-xs text-gray-700"
                                >{{ row.path }}</span
                            >
                            <span
                                class="ml-2 shrink-0 rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700"
                                >{{ row.c }}</span
                            >
                        </li>
                    </ul>
                </section>
            </div>

            <!-- Filters -->
            <section
                class="flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
            >
                <div>
                    <label class="block text-xs font-medium text-gray-500"
                        >IP address</label
                    >
                    <input
                        v-model="filters.ip"
                        type="text"
                        placeholder="e.g. 91.20"
                        class="mt-1 rounded-md border-gray-300 text-sm shadow-sm"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500"
                        >Referrer host</label
                    >
                    <input
                        v-model="filters.referrer_host"
                        type="text"
                        placeholder="google.com"
                        class="mt-1 rounded-md border-gray-300 text-sm shadow-sm"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500"
                        >Device</label
                    >
                    <select
                        v-model="filters.device_type"
                        class="mt-1 rounded-md border-gray-300 text-sm shadow-sm"
                    >
                        <option value="">All</option>
                        <option value="desktop">Desktop</option>
                        <option value="mobile">Mobile</option>
                        <option value="tablet">Tablet</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500"
                        >From</label
                    >
                    <input
                        v-model="filters.date_from"
                        type="date"
                        class="mt-1 rounded-md border-gray-300 text-sm shadow-sm"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500"
                        >To</label
                    >
                    <input
                        v-model="filters.date_to"
                        type="date"
                        class="mt-1 rounded-md border-gray-300 text-sm shadow-sm"
                    />
                </div>
                <button
                    @click="resetFilters"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
                >
                    Reset
                </button>
            </section>

            <!-- Sessions table -->
            <section
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
                <div class="flex items-center justify-between px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Sessions
                    </h2>
                    <span class="text-sm text-gray-500"
                        >{{ pagination.total }} total</span
                    >
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    v-for="head in [
                                        'IP',
                                        'Device',
                                        'Referrer',
                                        'Landing page',
                                        'Pages',
                                        'Clicks',
                                        'Duration',
                                        'Last seen',
                                        '',
                                    ]"
                                    :key="head"
                                    class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    {{ head }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-if="!isLoading && sessions.length === 0"
                                class="hover:bg-gray-50"
                            >
                                <td
                                    colspan="9"
                                    class="px-6 py-8 text-center text-sm text-gray-500"
                                >
                                    No visits recorded yet
                                </td>
                            </tr>
                            <template
                                v-for="session in sessions"
                                :key="session.id"
                            >
                                <tr class="hover:bg-gray-50">
                                    <td
                                        class="px-4 py-3 font-mono text-sm text-gray-900"
                                    >
                                        {{ session.ip_address || '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ session.device_type || '-' }}
                                    </td>
                                    <td
                                        class="max-w-[160px] truncate px-4 py-3 text-sm text-gray-600"
                                    >
                                        {{ session.referrer_host || 'direct' }}
                                    </td>
                                    <td
                                        class="max-w-[220px] truncate px-4 py-3 font-mono text-xs text-gray-600"
                                    >
                                        {{ session.landing_path || '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700"
                                            >{{ session.pageviews_count }}</span
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700"
                                            >{{ session.clicks_count }}</span
                                        >
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{
                                            formatDuration(
                                                session.duration_seconds,
                                            )
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ formatTime(session.last_seen_at) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            @click="toggleDetails(session)"
                                            class="text-sm text-blue-600 hover:text-blue-800"
                                        >
                                            {{
                                                expanded === session.id
                                                    ? 'Hide'
                                                    : 'Details'
                                            }}
                                        </button>
                                    </td>
                                </tr>
                                <tr
                                    v-if="expanded === session.id"
                                    class="bg-gray-50"
                                >
                                    <td colspan="9" class="px-6 py-4">
                                        <div
                                            v-if="isLoadingDetails"
                                            class="text-sm text-gray-500"
                                        >
                                            Loading...
                                        </div>
                                        <div v-else class="space-y-4">
                                            <div>
                                                <div
                                                    class="mb-1.5 flex items-center justify-between text-xs"
                                                >
                                                    <span
                                                        class="font-semibold uppercase text-gray-500"
                                                        >Activity —
                                                        {{
                                                            session.requests_count
                                                        }}
                                                        requests over
                                                        {{
                                                            formatDuration(
                                                                session.duration_seconds,
                                                            )
                                                        }}</span
                                                    >
                                                    <span
                                                        class="flex items-center gap-3 text-gray-500"
                                                    >
                                                        <span
                                                            class="inline-flex items-center gap-1"
                                                            ><span
                                                                class="h-2 w-2 rounded-full bg-blue-500"
                                                            ></span
                                                            >pageview</span
                                                        >
                                                        <span
                                                            class="inline-flex items-center gap-1"
                                                            ><span
                                                                class="h-2 w-2 rounded-full bg-amber-500"
                                                            ></span
                                                            >click</span
                                                        >
                                                    </span>
                                                </div>
                                                <div
                                                    class="relative h-3 w-full rounded-full bg-gray-100"
                                                >
                                                    <span
                                                        v-for="event in events"
                                                        :key="`tick-${event.id}`"
                                                        class="absolute top-1/2 h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full ring-2 ring-white"
                                                        :class="
                                                            event.type ===
                                                            'click'
                                                                ? 'bg-amber-500'
                                                                : 'bg-blue-500'
                                                        "
                                                        :style="{
                                                            left:
                                                                activityOffset(
                                                                    session,
                                                                    event,
                                                                ) + '%',
                                                        }"
                                                        :title="`${event.type} · ${formatTime(event.created_at)}`"
                                                    ></span>
                                                </div>
                                            </div>
                                            <div class="space-y-1">
                                                <div
                                                    class="mb-2 text-xs font-semibold uppercase text-gray-500"
                                                >
                                                    Timeline ({{
                                                        events.length
                                                    }})
                                                </div>
                                                <div
                                                    v-for="event in events"
                                                    :key="event.id"
                                                    class="flex items-center gap-3 text-sm"
                                                >
                                                    <span
                                                        class="font-mono text-xs text-gray-400"
                                                        >{{
                                                            formatTime(
                                                                event.created_at,
                                                            )
                                                        }}</span
                                                    >
                                                    <span
                                                        class="rounded px-1.5 py-0.5 text-xs font-medium"
                                                        :class="
                                                            event.type ===
                                                            'click'
                                                                ? 'bg-amber-100 text-amber-700'
                                                                : 'bg-blue-100 text-blue-700'
                                                        "
                                                        >{{ event.type }}</span
                                                    >
                                                    <span
                                                        class="font-mono text-xs text-gray-700"
                                                        >{{ event.path }}</span
                                                    >
                                                    <span
                                                        v-if="event.element_tag"
                                                        class="text-gray-500"
                                                    >
                                                        &lt;{{
                                                            event.element_tag
                                                        }}&gt;
                                                        <span
                                                            v-if="
                                                                event.element_text
                                                            "
                                                            class="italic"
                                                            >"{{
                                                                event.element_text
                                                            }}"</span
                                                        >
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div
                    class="flex items-center justify-between border-t border-gray-100 px-6 py-3"
                >
                    <span class="text-sm text-gray-500"
                        >Page {{ pagination.current_page }} of
                        {{ pagination.last_page }}</span
                    >
                    <div class="flex gap-2">
                        <button
                            :disabled="pagination.current_page <= 1"
                            @click="changePage(pagination.current_page - 1)"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 disabled:opacity-40"
                        >
                            Previous
                        </button>
                        <button
                            :disabled="
                                pagination.current_page >= pagination.last_page
                            "
                            @click="changePage(pagination.current_page + 1)"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 disabled:opacity-40"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { onMounted, reactive, ref, watch } from 'vue';

interface VisitSessionRow {
    id: number;
    ip_address: string | null;
    device_type: string | null;
    referrer_host: string | null;
    landing_path: string | null;
    pageviews_count: number;
    clicks_count: number;
    requests_count: number;
    duration_seconds: number;
    started_at: string;
    last_seen_at: string;
}

interface VisitEventRow {
    id: number;
    type: string;
    path: string | null;
    element_tag: string | null;
    element_text: string | null;
    created_at: string;
}

const sessions = ref<VisitSessionRow[]>([]);
const events = ref<VisitEventRow[]>([]);
const expanded = ref<number | null>(null);
const isLoading = ref(false);
const isLoadingDetails = ref(false);
const statsError = ref(false);

const pagination = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
});

const filters = reactive({
    ip: '',
    referrer_host: '',
    device_type: '',
    date_from: '',
    date_to: '',
});

const stats = reactive({
    sessions_today: 0,
    total_sessions: 0,
    total_pageviews: 0,
    total_clicks: 0,
    avg_duration_seconds: 0,
    top_referrers: [] as { referrer_host: string; c: number }[],
    top_pages: [] as { path: string; c: number }[],
});

async function fetchStats() {
    try {
        const response = await axios.get('/admin-api/visits/stats');
        Object.assign(stats, response.data);
        statsError.value = false;
    } catch (error) {
        // Without this, a failed request silently leaves every stat at its
        // zero default with no indication anything went wrong.
        console.error('Failed to load visit stats:', error);
        statsError.value = true;
    }
}

async function fetchSessions(page = 1) {
    isLoading.value = true;
    try {
        const response = await axios.get('/admin-api/visits', {
            params: { page, ...filters },
        });
        sessions.value = response.data.data;
        pagination.current_page = response.data.current_page;
        pagination.last_page = response.data.last_page;
        pagination.total = response.data.total;
    } finally {
        isLoading.value = false;
    }
}

async function toggleDetails(session: VisitSessionRow) {
    if (expanded.value === session.id) {
        expanded.value = null;
        return;
    }

    expanded.value = session.id;
    isLoadingDetails.value = true;
    try {
        const response = await axios.get(`/admin-api/visits/${session.id}`);
        events.value = response.data.events;
    } finally {
        isLoadingDetails.value = false;
    }
}

function changePage(page: number) {
    fetchSessions(page);
}

function resetFilters() {
    filters.ip = '';
    filters.referrer_host = '';
    filters.device_type = '';
    filters.date_from = '';
    filters.date_to = '';
}

function formatTime(value: string): string {
    try {
        return new Date(value).toLocaleString();
    } catch {
        return value;
    }
}

function formatDuration(seconds: number): string {
    if (!seconds || seconds <= 0) {
        return '0s';
    }

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    if (minutes > 0) {
        return `${minutes}m ${secs}s`;
    }
    return `${secs}s`;
}

// Where an event's dot lands on the 0-100% activity bar, as a share of the
// session's total duration elapsed since its first event.
function activityOffset(
    session: VisitSessionRow,
    event: VisitEventRow,
): number {
    if (session.duration_seconds <= 0) {
        return 0;
    }

    const elapsedMs =
        new Date(event.created_at).getTime() -
        new Date(session.started_at).getTime();
    const percent = (elapsedMs / (session.duration_seconds * 1000)) * 100;

    return Math.min(100, Math.max(0, percent));
}

let debounceTimer: number | null = null;
watch(filters, () => {
    if (debounceTimer) {
        window.clearTimeout(debounceTimer);
    }
    debounceTimer = window.setTimeout(() => fetchSessions(1), 300);
});

onMounted(() => {
    fetchStats();
    fetchSessions();
});
</script>
