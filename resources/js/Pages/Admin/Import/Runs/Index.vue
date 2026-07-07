<template>
    <div class="min-h-screen bg-gray-100">
        <header class="bg-white shadow">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8"
            >
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                    Import History
                </h1>
                <a
                    href="/admin/import"
                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white shadow-sm hover:bg-blue-700"
                >
                    New import
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="space-y-4 rounded-xl bg-white p-6 shadow-sm">
                <!-- Filters -->
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600"
                            >Source</label
                        >
                        <select
                            v-model="filters.source"
                            @change="load(1)"
                            class="mt-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All</option>
                            <option v-for="s in sources" :key="s" :value="s">
                                {{ s }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600"
                            >Status</label
                        >
                        <select
                            v-model="filters.status"
                            @change="load(1)"
                            class="mt-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All</option>
                            <option value="running">Running</option>
                            <option value="completed">Completed</option>
                            <option value="stopped">Stopped</option>
                            <option value="error">Error</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr
                                class="text-left text-xs uppercase tracking-wide text-gray-500"
                            >
                                <th class="py-2 pr-4">Source</th>
                                <th class="py-2 pr-4">URL</th>
                                <th class="py-2 pr-4">Status</th>
                                <th class="py-2 pr-4 text-right">New</th>
                                <th class="py-2 pr-4 text-right">Matched</th>
                                <th class="py-2 pr-4 text-right">Skipped</th>
                                <th class="py-2 pr-4 text-right">Total</th>
                                <th class="py-2 pr-4">Started</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="run in runs"
                                :key="run.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="py-2 pr-4 font-medium text-gray-800">
                                    {{ run.source }}
                                </td>
                                <td
                                    class="max-w-xs truncate py-2 pr-4 text-gray-500"
                                    :title="run.url"
                                >
                                    {{ run.url }}
                                </td>
                                <td class="py-2 pr-4">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="statusBadgeClass(run.status)"
                                    >
                                        {{ run.status }}
                                    </span>
                                </td>
                                <td
                                    class="py-2 pr-4 text-right text-emerald-700"
                                >
                                    {{ run.imported_count }}
                                </td>
                                <td class="py-2 pr-4 text-right text-blue-700">
                                    {{ run.matched_count }}
                                </td>
                                <td class="py-2 pr-4 text-right text-amber-700">
                                    {{ run.skipped_count }}
                                </td>
                                <td
                                    class="py-2 pr-4 text-right font-medium text-gray-700"
                                >
                                    {{
                                        run.imported_count +
                                        run.matched_count +
                                        run.skipped_count
                                    }}
                                </td>
                                <td class="py-2 pr-4 text-gray-500">
                                    {{ formatDate(run.started_at) }}
                                </td>
                                <td class="py-2 text-right">
                                    <a
                                        :href="`/admin/import/runs/${run.id}`"
                                        class="text-blue-600 hover:underline"
                                        >View</a
                                    >
                                </td>
                            </tr>
                            <tr v-if="!loading && runs.length === 0">
                                <td
                                    colspan="9"
                                    class="py-6 text-center text-gray-400"
                                >
                                    No import runs yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="meta.last_page > 1"
                    class="flex items-center justify-between pt-2 text-sm text-gray-600"
                >
                    <button
                        type="button"
                        :disabled="meta.current_page <= 1"
                        @click="load(meta.current_page - 1)"
                        class="rounded-lg border border-gray-300 px-3 py-1 disabled:opacity-40"
                    >
                        Prev
                    </button>
                    <span
                        >Page {{ meta.current_page }} /
                        {{ meta.last_page }}</span
                    >
                    <button
                        type="button"
                        :disabled="meta.current_page >= meta.last_page"
                        @click="load(meta.current_page + 1)"
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
import { onMounted, reactive, ref } from 'vue';

const runs = ref([]);
const loading = ref(false);
const sources = ['VseSto', 'Ratelist', 'MechanicAdvisor', 'AutoWerkstatt'];
const meta = reactive({ current_page: 1, last_page: 1 });
const filters = reactive({ source: '', status: '' });

function statusBadgeClass(status) {
    return {
        'bg-blue-100 text-blue-700':
            status === 'running' || status === 'queued',
        'bg-emerald-100 text-emerald-700': status === 'completed',
        'bg-amber-100 text-amber-700': status === 'stopped',
        'bg-red-100 text-red-700': status === 'error',
    };
}

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleString();
}

async function load(page = 1) {
    loading.value = true;
    try {
        const response = await axios.get('/admin-api/import/runs', {
            params: {
                page,
                source: filters.source || undefined,
                status: filters.status || undefined,
            },
        });
        const data = response.data;
        runs.value = data.data || [];
        meta.current_page = data.current_page || 1;
        meta.last_page = data.last_page || 1;
    } catch (error) {
        console.error('Failed to load import runs:', error);
    } finally {
        loading.value = false;
    }
}

onMounted(() => load(1));
</script>
