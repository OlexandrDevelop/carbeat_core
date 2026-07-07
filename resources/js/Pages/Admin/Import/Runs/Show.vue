<template>
    <div class="min-h-screen bg-gray-100">
        <header class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <a
                    href="/admin/import/runs"
                    class="text-sm text-blue-600 hover:underline"
                    >&larr; All import runs</a
                >
                <h1
                    class="mt-2 text-3xl font-bold tracking-tight text-gray-900"
                >
                    {{ run?.source || 'Import run' }}
                </h1>
                <p
                    v-if="run"
                    class="mt-1 truncate text-sm text-gray-500"
                    :title="run.url"
                >
                    {{ run.url }}
                </p>
            </div>
        </header>

        <main class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <!-- Stat tiles -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <div
                        class="text-xs font-medium uppercase tracking-wide text-gray-500"
                    >
                        Total processed
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ totals.total }}
                    </div>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <div
                        class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-gray-500"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-emerald-600"
                        ></span>
                        New
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-emerald-700">
                        {{ totals.created }}
                    </div>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <div
                        class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-gray-500"
                    >
                        <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                        Matched
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-blue-700">
                        {{ totals.matched }}
                    </div>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm">
                    <div
                        class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-gray-500"
                    >
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        Skipped
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-amber-700">
                        {{ totals.skipped }}
                    </div>
                </div>
            </div>

            <!-- By city -->
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">By city</h2>
                    <div class="flex items-center gap-4 text-xs text-gray-600">
                        <span class="flex items-center gap-1.5"
                            ><span
                                class="h-2 w-2 rounded-full bg-emerald-600"
                            ></span>
                            New</span
                        >
                        <span class="flex items-center gap-1.5"
                            ><span
                                class="h-2 w-2 rounded-full bg-blue-600"
                            ></span>
                            Matched</span
                        >
                        <span class="flex items-center gap-1.5"
                            ><span
                                class="h-2 w-2 rounded-full bg-amber-500"
                            ></span>
                            Skipped</span
                        >
                    </div>
                </div>

                <div
                    v-if="byCity.length === 0"
                    class="py-6 text-center text-gray-400"
                >
                    No data yet.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="city in byCity"
                        :key="city.city_name || 'unknown'"
                        class="grid grid-cols-[minmax(0,10rem)_1fr_auto] items-center gap-3"
                    >
                        <button
                            type="button"
                            class="truncate text-left text-sm font-medium text-gray-700 hover:text-blue-600 hover:underline"
                            @click="filterByCity(city.city_name)"
                        >
                            {{ city.city_name || 'Unknown' }}
                        </button>
                        <div
                            class="flex h-4 overflow-hidden rounded bg-gray-100"
                        >
                            <div
                                v-if="city.created_count > 0"
                                class="h-full bg-emerald-600"
                                :style="{ width: barWidth(city.created_count) }"
                            ></div>
                            <div
                                v-if="
                                    city.created_count > 0 &&
                                    city.matched_count > 0
                                "
                                class="w-0.5 bg-gray-100"
                            ></div>
                            <div
                                v-if="city.matched_count > 0"
                                class="h-full bg-blue-600"
                                :style="{ width: barWidth(city.matched_count) }"
                            ></div>
                            <div
                                v-if="
                                    city.matched_count > 0 &&
                                    city.skipped_count > 0
                                "
                                class="w-0.5 bg-gray-100"
                            ></div>
                            <div
                                v-if="city.skipped_count > 0"
                                class="h-full bg-amber-500"
                                :style="{ width: barWidth(city.skipped_count) }"
                            ></div>
                        </div>
                        <div class="whitespace-nowrap text-xs text-gray-600">
                            <span class="text-emerald-700">{{
                                city.created_count
                            }}</span>
                            /
                            <span class="text-blue-700">{{
                                city.matched_count
                            }}</span>
                            /
                            <span class="text-amber-700">{{
                                city.skipped_count
                            }}</span>
                            <span class="ml-1 text-gray-400"
                                >({{ city.total }})</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skip reasons -->
            <div
                v-if="bySkipReason.length"
                class="rounded-xl bg-white p-6 shadow-sm"
            >
                <h2 class="mb-3 text-lg font-semibold text-gray-900">
                    Why masters were skipped
                </h2>
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="reason in bySkipReason"
                            :key="reason.skip_reason || 'unknown'"
                        >
                            <td class="py-1.5 pr-4 text-gray-700">
                                {{ reason.skip_reason || 'unknown' }}
                            </td>
                            <td
                                class="py-1.5 text-right font-medium text-gray-900"
                            >
                                {{ reason.total }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Master list -->
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <div
                    class="mb-4 flex flex-wrap items-end justify-between gap-3"
                >
                    <h2 class="text-lg font-semibold text-gray-900">Masters</h2>
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-600"
                                >Status</label
                            >
                            <select
                                v-model="masterFilters.status"
                                @change="loadMasters(1)"
                                class="mt-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">All</option>
                                <option value="created">New</option>
                                <option value="matched">Matched</option>
                                <option value="skipped">Skipped</option>
                            </select>
                        </div>
                        <div v-if="masterFilters.city_name">
                            <label
                                class="block text-xs font-medium text-gray-600"
                                >City</label
                            >
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-sm text-gray-700">{{
                                    masterFilters.city_name
                                }}</span>
                                <button
                                    type="button"
                                    class="text-xs text-blue-600 hover:underline"
                                    @click="filterByCity('')"
                                >
                                    clear
                                </button>
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-600"
                                >Search</label
                            >
                            <input
                                v-model="masterFilters.q"
                                @input="debouncedLoadMasters"
                                type="text"
                                placeholder="Master name"
                                class="mt-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr
                                class="text-left text-xs uppercase tracking-wide text-gray-500"
                            >
                                <th class="py-2 pr-4">Master</th>
                                <th class="py-2 pr-4">City</th>
                                <th class="py-2 pr-4">Status</th>
                                <th class="py-2 pr-4">Skip reason</th>
                                <th class="py-2 pr-4">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="row in masters"
                                :key="row.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="py-2 pr-4">
                                    <a
                                        v-if="row.master_id"
                                        :href="`/admin/masters/${row.master_id}/edit`"
                                        class="text-blue-600 hover:underline"
                                    >
                                        {{
                                            row.master_name ||
                                            `#${row.master_id}`
                                        }}
                                    </a>
                                    <span v-else class="text-gray-500">{{
                                        row.master_name || '-'
                                    }}</span>
                                </td>
                                <td class="py-2 pr-4 text-gray-600">
                                    {{ row.city_name || '-' }}
                                </td>
                                <td class="py-2 pr-4">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700':
                                                row.status === 'created',
                                            'bg-blue-100 text-blue-700':
                                                row.status === 'matched',
                                            'bg-amber-100 text-amber-700':
                                                row.status === 'skipped',
                                        }"
                                    >
                                        {{ statusLabel(row.status) }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4 text-gray-500">
                                    {{ row.skip_reason || '-' }}
                                </td>
                                <td class="py-2 pr-4 text-gray-500">
                                    {{ formatDate(row.created_at) }}
                                </td>
                            </tr>
                            <tr v-if="masters.length === 0">
                                <td
                                    colspan="5"
                                    class="py-6 text-center text-gray-400"
                                >
                                    No masters match these filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="masterMeta.last_page > 1"
                    class="flex items-center justify-between pt-4 text-sm text-gray-600"
                >
                    <button
                        type="button"
                        :disabled="masterMeta.current_page <= 1"
                        @click="loadMasters(masterMeta.current_page - 1)"
                        class="rounded-lg border border-gray-300 px-3 py-1 disabled:opacity-40"
                    >
                        Prev
                    </button>
                    <span
                        >Page {{ masterMeta.current_page }} /
                        {{ masterMeta.last_page }}</span
                    >
                    <button
                        type="button"
                        :disabled="
                            masterMeta.current_page >= masterMeta.last_page
                        "
                        @click="loadMasters(masterMeta.current_page + 1)"
                        class="rounded-lg border border-gray-300 px-3 py-1 disabled:opacity-40"
                    >
                        Next
                    </button>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';

const props = defineProps({
    runId: { type: [Number, String], required: true },
});

const run = ref(null);
const byCity = ref([]);
const bySkipReason = ref([]);
const masters = ref([]);
const masterMeta = reactive({ current_page: 1, last_page: 1 });
const masterFilters = reactive({ status: '', city_name: '', q: '' });

const totals = computed(() => {
    return byCity.value.reduce(
        (acc, c) => {
            acc.created += Number(c.created_count) || 0;
            acc.matched += Number(c.matched_count) || 0;
            acc.skipped += Number(c.skipped_count) || 0;
            acc.total += Number(c.total) || 0;
            return acc;
        },
        { created: 0, matched: 0, skipped: 0, total: 0 },
    );
});

const maxCityTotal = computed(() =>
    Math.max(1, ...byCity.value.map((c) => Number(c.total) || 0)),
);

function barWidth(count) {
    return `${Math.max(0, (Number(count) / maxCityTotal.value) * 100)}%`;
}

function statusLabel(status) {
    return (
        { created: 'New', matched: 'Matched', skipped: 'Skipped' }[status] ||
        status
    );
}

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleString();
}

function filterByCity(cityName) {
    masterFilters.city_name = cityName || '';
    loadMasters(1);
}

async function loadSummary() {
    try {
        const response = await axios.get(
            `/admin-api/import/runs/${props.runId}/summary`,
        );
        run.value = response.data.run;
        byCity.value = response.data.by_city || [];
        bySkipReason.value = response.data.by_skip_reason || [];
    } catch (error) {
        console.error('Failed to load run summary:', error);
    }
}

async function loadMasters(page = 1) {
    try {
        const response = await axios.get(
            `/admin-api/import/runs/${props.runId}/masters`,
            {
                params: {
                    page,
                    status: masterFilters.status || undefined,
                    city_name: masterFilters.city_name || undefined,
                    q: masterFilters.q || undefined,
                },
            },
        );
        const data = response.data;
        masters.value = data.data || [];
        masterMeta.current_page = data.current_page || 1;
        masterMeta.last_page = data.last_page || 1;
    } catch (error) {
        console.error('Failed to load run masters:', error);
    }
}

let debounceTimer = null;
function debouncedLoadMasters() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadMasters(1), 300);
}

onMounted(() => {
    loadSummary();
    loadMasters(1);
});
</script>
