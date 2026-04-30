<template>
    <div class="space-y-8 p-6">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Smart Random Status</h1>
                <p class="text-sm text-gray-600">
                    Controls fake green statuses for imported STOs without exposing fake-vs-real to the client API.
                </p>
            </div>
            <button
                @click="load"
                :disabled="loading"
                class="rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 disabled:opacity-60"
            >
                {{ loading ? 'Refreshing...' : 'Refresh' }}
            </button>
        </div>

        <section class="rounded border bg-white p-5 shadow-sm">
            <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
                <div class="space-y-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold">System Toggle</h2>
                            <p class="text-sm text-gray-500">
                                Enable or disable smart fake green rotation.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="relative inline-flex h-7 w-14 items-center rounded-full transition"
                            :class="form.enabled ? 'bg-green-600' : 'bg-gray-300'"
                            @click="form.enabled = !form.enabled"
                        >
                            <span
                                class="inline-block h-5 w-5 transform rounded-full bg-white transition"
                                :class="form.enabled ? 'translate-x-8' : 'translate-x-1'"
                            />
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="font-semibold">Percentage</h2>
                                <p class="text-sm text-gray-500">
                                    Target visible green density across the current brand.
                                </p>
                            </div>
                            <span class="text-lg font-semibold">{{ form.percentage }}%</span>
                        </div>
                        <input
                            v-model.number="form.percentage"
                            type="range"
                            min="0"
                            max="100"
                            class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200"
                        />
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>0%</span>
                            <span>100%</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            @click="save"
                            :disabled="saving"
                            class="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                        >
                            {{ saving ? 'Saving...' : 'Save Settings' }}
                        </button>
                        <span
                            v-if="message.text"
                            :class="message.type === 'error' ? 'text-red-700' : 'text-green-700'"
                            class="text-sm"
                        >
                            {{ message.text }}
                        </span>
                    </div>
                </div>

                <div class="rounded border border-gray-200 bg-gray-50 p-4">
                    <h2 class="font-semibold">Rotation Rules</h2>
                    <dl class="mt-4 space-y-3 text-sm text-gray-700">
                        <div class="flex justify-between gap-4">
                            <dt>Global fallback window</dt>
                            <dd class="font-medium">
                                {{ settings.global_window.start }} - {{ settings.global_window.end }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt>Rotation interval</dt>
                            <dd class="font-medium">
                                {{ settings.rotation_window_minutes.min }}-{{ settings.rotation_window_minutes.max }} min
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt>Working hours priority</dt>
                            <dd class="font-medium">Per STO if available</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3 xl:grid-cols-4">
            <article class="rounded border bg-white p-4 shadow-sm">
                <p class="text-sm text-gray-500">Total STOs</p>
                <p class="mt-2 text-3xl font-semibold">{{ stats.total_stos }}</p>
            </article>
            <article class="rounded border bg-white p-4 shadow-sm">
                <p class="text-sm text-gray-500">Green (Fake)</p>
                <p class="mt-2 text-3xl font-semibold text-green-600">{{ stats.fake_green }}</p>
            </article>
            <article class="rounded border bg-white p-4 shadow-sm">
                <p class="text-sm text-gray-500">Green (Real/Manual)</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-700">{{ stats.real_green }}</p>
            </article>
            <article class="rounded border bg-white p-4 shadow-sm">
                <p class="text-sm text-gray-500">System</p>
                <p class="mt-2 text-3xl font-semibold">{{ form.enabled ? 'On' : 'Off' }}</p>
            </article>
        </section>

        <section class="rounded border bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold">Fake Green STOs</h2>
                    <p class="text-sm text-gray-500">
                        Current random green statuses for this brand. Turning one off removes the fake green marker immediately.
                    </p>
                </div>
                <span class="rounded bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                    {{ fakeMasters.length }} active
                </span>
            </div>

            <div v-if="fakeMasters.length === 0" class="mt-6 rounded border border-dashed p-8 text-center text-sm text-gray-500">
                No STOs are currently under fake green status.
            </div>

            <div v-else class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">STO</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Address</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Phone</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Updated</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="master in fakeMasters" :key="master.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ master.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ master.address || '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ master.phone || '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ formatDate(master.last_status_update) }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    @click="turnOff(master.id)"
                                    :disabled="turningOffId === master.id"
                                    class="rounded bg-red-600 px-3 py-2 text-xs font-semibold text-white disabled:opacity-60"
                                >
                                    {{ turningOffId === master.id ? 'Turning Off...' : 'Turn Off' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';

const loading = ref(false);
const saving = ref(false);
const turningOffId = ref(null);
const message = ref({ type: '', text: '' });

const settings = ref({
    enabled: false,
    percentage: 50,
    global_window: { start: '08:00', end: '20:00' },
    rotation_window_minutes: { min: 30, max: 120 },
});

const form = ref({
    enabled: false,
    percentage: 50,
});

const stats = ref({
    total_stos: 0,
    fake_green: 0,
    real_green: 0,
});

const fakeMasters = ref([]);

const showMessage = (type, text) => {
    message.value = { type, text };
    setTimeout(() => {
        message.value = { type: '', text: '' };
    }, 3000);
};

const applyPayload = (payload) => {
    settings.value = payload.settings;
    form.value = {
        enabled: payload.settings.enabled,
        percentage: payload.settings.percentage,
    };
    stats.value = payload.stats;
    fakeMasters.value = payload.fake_green_masters;
};

const load = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/admin-api/smart-random-status');
        applyPayload(data);
    } catch (error) {
        showMessage('error', error?.response?.data?.message || 'Failed to load smart random status data');
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    if (saving.value) return;

    saving.value = true;
    try {
        const { data } = await axios.put('/admin-api/smart-random-status', form.value);
        applyPayload(data);
        showMessage('success', 'Smart random status settings saved');
    } catch (error) {
        showMessage('error', error?.response?.data?.message || 'Failed to save smart random status settings');
    } finally {
        saving.value = false;
    }
};

const turnOff = async (masterId) => {
    if (turningOffId.value !== null) return;

    turningOffId.value = masterId;
    try {
        const { data } = await axios.post(`/admin-api/smart-random-status/${masterId}/turn-off`);
        stats.value = data.stats;
        fakeMasters.value = data.fake_green_masters;
        showMessage('success', 'Fake green status turned off');
    } catch (error) {
        showMessage('error', error?.response?.data?.message || 'Failed to turn off fake green status');
    } finally {
        turningOffId.value = null;
    }
};

const formatDate = (value) => {
    if (!value) return '—';

    return new Date(value).toLocaleString();
};

onMounted(load);
</script>
