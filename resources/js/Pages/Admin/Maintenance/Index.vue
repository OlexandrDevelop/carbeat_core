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
    </div>
</template>

<script setup>
import axios from 'axios';
import { ref } from 'vue';

const loading = ref(false);
const result = ref(null);

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
</script>

<style scoped></style>
