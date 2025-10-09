<template>
    <div class="p-6">
        <h1 class="mb-4 text-2xl font-semibold">Maintenance</h1>

        <div class="max-w-2xl rounded border bg-white p-4 shadow-sm">
            <h2 class="mb-2 text-lg font-semibold">Gallery cleanup</h2>
            <p class="mb-3 text-sm text-gray-600">
                Remove gallery DB records that no longer have files on disk
                (storage/public).
            </p>
            <button
                @click="cleanup"
                :disabled="loading"
                class="rounded bg-black px-4 py-2 text-sm text-white disabled:opacity-50"
            >
                {{ loading ? 'Running...' : 'Run cleanup' }}
            </button>
            <div v-if="result" class="mt-3 text-sm text-gray-800">
                <div><strong>Checked:</strong> {{ result.checked }}</div>
                <div><strong>Deleted:</strong> {{ result.deleted }}</div>
                <div
                    v-if="result.masters && Object.keys(result.masters).length"
                >
                    <strong>Per master:</strong>
                    <ul class="ml-5 list-disc">
                        <li v-for="(cnt, mid) in result.masters" :key="mid">
                            Master #{{ mid }}: {{ cnt }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-6 max-w-2xl rounded border bg-white p-4 shadow-sm">
            <h2 class="mb-2 text-lg font-semibold">Database reset</h2>
            <p class="mb-3 text-sm text-gray-600">
                Truncate domain tables (clients, cities, masters, services, reviews, master services, master galleries). Use with caution.
            </p>
            <button
                @click="truncate"
                :disabled="truncateLoading"
                class="rounded bg-red-600 px-4 py-2 text-sm text-white disabled:opacity-50"
            >
                {{ truncateLoading ? 'Truncating...' : 'Truncate tables' }}
            </button>
            <div v-if="truncateResult" class="mt-3 text-sm text-gray-800">
                <div class="font-semibold">Truncated tables:</div>
                <ul class="ml-5 list-disc">
                    <li v-for="(count, table) in truncateResult.tables" :key="table">
                        {{ table }} (removed: {{ count }})
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-6 max-w-2xl rounded border bg-white p-4 shadow-sm">
            <h2 class="mb-2 text-lg font-semibold">Regenerate thumbnails</h2>
            <p class="mb-3 text-sm text-gray-600">
                Queue jobs to regenerate thumbnail images for masters with photos.
            </p>
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" v-model="resetFlags" /> Reset thumbnail flags first
                </label>
                <button
                    @click="regenerate"
                    :disabled="regenLoading"
                    class="rounded bg-blue-600 px-4 py-2 text-sm text-white disabled:opacity-50"
                >
                    {{ regenLoading ? 'Queuing...' : 'Regenerate thumbnails' }}
                </button>
            </div>
            <div v-if="regenResult" class="mt-3 text-sm text-gray-800">
                <div><strong>Total to process:</strong> {{ regenResult.total }}</div>
                <div><strong>Queued chunks:</strong> {{ regenResult.queued_chunks }}</div>
                <div><strong>Chunk size:</strong> {{ regenResult.chunk_size }}</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { ref } from 'vue';

const loading = ref(false);
const result = ref(null);

const truncateLoading = ref(false);
const truncateResult = ref(null);

const regenLoading = ref(false);
const regenResult = ref(null);
const resetFlags = ref(true);

const cleanup = async () => {
    loading.value = true;
    result.value = null;
    try {
        const { data } = await axios.post(
            '/admin-api/maintenance/gallery/cleanup',
        );
        result.value = data;
    } finally {
        loading.value = false;
    }
};

const truncate = async () => {
    truncateLoading.value = true;
    truncateResult.value = null;
    try {
        const { data } = await axios.post('/admin-api/maintenance/truncate');
        truncateResult.value = data;
    } finally {
        truncateLoading.value = false;
    }
};

const regenerate = async () => {
    regenLoading.value = true;
    regenResult.value = null;
    try {
        const { data } = await axios.post('/admin-api/maintenance/regenerate-thumbs', { reset: resetFlags.value });
        regenResult.value = data;
    } finally {
        regenLoading.value = false;
    }
};
</script>

<style scoped></style>
