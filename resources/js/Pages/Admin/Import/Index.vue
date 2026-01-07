<template>
    <div class="min-h-screen bg-gray-100">
        <header class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                    Import Masters
                </h1>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="space-y-6 rounded-xl bg-white p-6 shadow-sm">
                <!-- Import Form -->
                <form @submit.prevent="startImport" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Service</label
                        >
                        <select
                            v-model="form.service_id"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option :value="0">Auto-detect</option>
                            <option
                                v-for="service in services"
                                :key="service.id"
                                :value="service.id"
                            >
                                {{ service.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >RateList URLs</label
                        >
                        <textarea
                            v-model="form.urls_text"
                            rows="4"
                            placeholder="One URL per line"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-500">
                            You can paste multiple links, each on a new line.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Pages limit (optional)</label
                        >
                        <input
                            v-model.number="form.pages"
                            type="number"
                            min="1"
                            placeholder="Leave empty for all pages"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div class="flex items-center justify-between">
                        <div></div>
                        <button
                            type="submit"
                            :disabled="importing"
                            class="rounded-xl bg-blue-600 px-4 py-2 text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            {{ importing ? 'Importing...' : 'Start Import' }}
                        </button>
                    </div>
                </form>

                <!-- Progress (multiple jobs) -->
                <div v-if="jobs.length" class="mt-8 space-y-6">
                    <div
                        v-for="job in jobs"
                        :key="job.job_id"
                        class="rounded-xl border border-gray-200 p-4"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-gray-700">
                                    {{ job.url }}
                                </div>
                                <div
                                    v-if="
                                        job.progress &&
                                        job.progress.eta_seconds != null
                                    "
                                    class="mt-1 text-xs text-gray-500"
                                >
                                    ETA:
                                    {{
                                        formatSeconds(job.progress.eta_seconds)
                                    }}
                                </div>
                            </div>
                            <button
                                v-if="
                                    ['running', 'queued'].includes(
                                        job.progress?.status,
                                    )
                                "
                                type="button"
                                @click.prevent="stopJob(job)"
                                class="rounded-xl bg-red-600 px-3 py-1.5 text-xs text-white shadow-sm hover:bg-red-700"
                            >
                                Stop
                            </button>
                        </div>

                        <div class="mt-3">
                            <div class="h-3 rounded-full bg-gray-200">
                                <div
                                    class="h-3 rounded-full transition-all duration-500"
                                    :class="{
                                        'bg-blue-600':
                                            job.progress?.status ===
                                                'running' ||
                                            job.progress?.status === 'queued',
                                        'bg-green-600':
                                            job.progress?.status ===
                                            'completed',
                                        'bg-red-600':
                                            job.progress?.status === 'error',
                                    }"
                                    :style="{ width: jobProgressWidth(job) }"
                                ></div>
                            </div>
                            <div
                                v-if="
                                    job.progress &&
                                    job.progress.status !== 'error'
                                "
                                class="mt-1 text-xs text-gray-600"
                            >
                                {{ job.progress.processed || 0 }} /
                                {{ job.progress.total_urls || 0 }} ({{
                                    job.progress.imported || 0
                                }}
                                imported,
                                {{ job.progress.skipped || 0 }} skipped)
                            </div>
                        </div>

                        <div
                            v-if="job.progress?.error"
                            class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700"
                        >
                            {{ job.progress.error }}
                        </div>

                        <div
                            v-if="job.progress?.status === 'completed'"
                            class="mt-3 rounded-md bg-green-50 p-3 text-sm text-green-700"
                        >
                            Completed: {{ job.progress.imported || 0 }} imported
                            ({{ job.progress.skipped || 0 }} skipped)
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onUnmounted, ref } from 'vue';

const services = ref([]);
const importing = ref(false);
const jobs = ref([]); // [{ job_id, url, progress }]
let progressInterval = null;

const form = ref({
    service_id: 0,
    urls_text: '',
    pages: null,
});

function jobProgressWidth(job) {
    const p = job.progress;
    if (!p || p.status === 'error') return '0%';
    if (p.status === 'completed') return '100%';
    const total = p.total_urls || 100;
    if (total > 0) {
        const percentage = (p.processed / total) * 100;
        return `${Math.min(percentage, 100)}%`;
    }
    return '60%';
}

async function loadServices() {
    try {
        const response = await axios.get('/admin-api/services');
        services.value = response.data;
    } catch (error) {
        console.error('Failed to load services:', error);
    }
}

async function startImport() {
    if (importing.value) return;
    try {
        importing.value = true;
        jobs.value = [];

        const payload = {
            service_id: form.value.service_id,
            pages: form.value.pages,
            urls_text: form.value.urls_text,
        };
        const response = await axios.post('/admin-api/import/start', payload);
        const startedJobs =
            response.data?.data?.jobs || response.data?.jobs || [];
        jobs.value = startedJobs.map((j) => ({
            job_id: j.job_id,
            url: j.url,
            progress: {
                status: 'queued',
                imported: 0,
                skipped: 0,
                processed: 0,
                total_urls: 0,
            },
        }));
        startProgressPolling();
    } catch (error) {
        console.error('Failed to start import:', error);
        jobs.value = [];
        importing.value = false;
    }
}

async function stopJob(job) {
    try {
        await axios.post(`/admin-api/import/stop/${job.job_id}`);
    } catch (e) {
        console.error('Failed to stop import', e);
    }
}

function formatSeconds(sec) {
    const s = Math.max(0, Math.floor(sec));
    const h = String(Math.floor(s / 3600)).padStart(2, '0');
    const m = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
    const ss = String(s % 60).padStart(2, '0');
    return `${h}:${m}:${ss}`;
}

function startProgressPolling() {
    if (progressInterval) {
        clearInterval(progressInterval);
    }

    progressInterval = setInterval(async () => {
        if (!jobs.value.length) return;

        try {
            let allDone = true;
            await Promise.all(
                jobs.value.map(async (job) => {
                    try {
                        const resp = await axios.get(
                            `/admin-api/import/progress/${job.job_id}`,
                        );
                        job.progress = resp.data?.data || resp.data;
                        if (
                            !['completed', 'error', 'stopped'].includes(
                                job.progress?.status,
                            )
                        ) {
                            allDone = false;
                        }
                    } catch (err) {
                        if (err.response?.status === 404) {
                            job.progress = {
                                status: 'error',
                                error: 'Job not found',
                            };
                        } else {
                            console.error(
                                'Progress error for job',
                                job.job_id,
                                err,
                            );
                        }
                    }
                }),
            );

            if (allDone) {
                importing.value = false;
                clearInterval(progressInterval);
                progressInterval = null;
            }
        } catch (error) {
            console.error('Failed to fetch progress:', error);
        }
    }, 1000);
}

onUnmounted(() => {
    if (progressInterval) {
        clearInterval(progressInterval);
    }
});

// Load services on mount
loadServices();
</script>
