<template>
    <div class="space-y-8 p-6">
        <h1 class="text-2xl font-semibold">App Config</h1>

        <section class="rounded border p-4">
            <h2 class="mb-3 font-semibold">App Versions (Force/Soft Update)</h2>
            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <h3 class="font-medium">Android</h3>
                    <label class="text-sm text-gray-700"
                        >Min supported build (обов’язкове оновлення)</label
                    >
                    <input
                        v-model.number="versions.android.min_supported_build"
                        class="w-full rounded border p-2"
                        type="number"
                        min="1"
                    />
                    <label class="text-sm text-gray-700"
                        >Recommended build (рекомендоване оновлення)</label
                    >
                    <input
                        v-model.number="versions.android.recommended_build"
                        class="w-full rounded border p-2"
                        type="number"
                        min="1"
                    />
                    <label class="text-sm text-gray-700">Store URL</label>
                    <input
                        v-model="versions.android.store_url"
                        class="w-full rounded border p-2"
                    />
                    <label class="text-sm text-gray-700"
                        >Повідомлення про оновлення</label
                    >
                    <textarea
                        v-model="versions.android.message"
                        rows="3"
                        class="w-full rounded border p-2"
                    ></textarea>
                </div>
                <div class="space-y-2">
                    <h3 class="font-medium">iOS</h3>
                    <label class="text-sm text-gray-700"
                        >Min supported build (обов’язкове оновлення)</label
                    >
                    <input
                        v-model.number="versions.ios.min_supported_build"
                        class="w-full rounded border p-2"
                        type="number"
                        min="1"
                    />
                    <label class="text-sm text-gray-700"
                        >Recommended build (рекомендоване оновлення)</label
                    >
                    <input
                        v-model.number="versions.ios.recommended_build"
                        class="w-full rounded border p-2"
                        type="number"
                        min="1"
                    />
                    <label class="text-sm text-gray-700">Store URL</label>
                    <input
                        v-model="versions.ios.store_url"
                        class="w-full rounded border p-2"
                    />
                    <label class="text-sm text-gray-700"
                        >Повідомлення про оновлення</label
                    >
                    <textarea
                        v-model="versions.ios.message"
                        rows="3"
                        class="w-full rounded border p-2"
                    ></textarea>
                </div>
            </div>
            <div class="mt-3 space-y-2">
                <button
                    @click="saveVersions"
                    :disabled="savingVersions"
                    class="rounded bg-blue-600 px-4 py-2 text-white disabled:opacity-60"
                >
                    {{ savingVersions ? 'Saving...' : 'Save Versions' }}
                </button>
                <div
                    v-if="messageVersions.text"
                    :class="[
                        'text-sm',
                        messageVersions.type === 'success'
                            ? 'text-green-700'
                            : 'text-red-700',
                    ]"
                >
                    {{ messageVersions.text }}
                </div>
            </div>
        </section>

        <!-- Subscription (Trial) controls removed: free trial is managed by stores (Google Play / App Store) -->
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';

const versions = ref({
    android: {
        min_supported_build: 1,
        recommended_build: 1,
        message: '',
        store_url: '',
    },
    ios: {
        min_supported_build: 1,
        recommended_build: 1,
        message: '',
        store_url: '',
    },
});
const subscription = ref({ trial_enabled: false, trial_days: 0 });

const savingVersions = ref(false);
const savingSubscription = ref(false);
const messageVersions = ref({ type: '', text: '' });
const messageSubscription = ref({ type: '', text: '' });

const showMessage = (target, type, text) => {
    target.value = { type, text };
    setTimeout(() => {
        target.value = { type: '', text: '' };
    }, 3000);
};

const load = async () => {
    const { data: v } = await axios.get('/admin-api/app-config/versions');
    const { data: s } = await axios.get('/admin-api/app-config/subscription');
    versions.value = Object.assign(versions.value, v);
    subscription.value = Object.assign(subscription.value, s);
};

const saveVersions = async () => {
    if (savingVersions.value) return;
    savingVersions.value = true;
    try {
        await axios.post('/admin-api/app-config/versions', versions.value);
        showMessage(
            messageVersions,
            'success',
            'Налаштування версій збережено',
        );
    } catch (e) {
        const msg =
            e?.response?.data?.message ||
            'Помилка збереження налаштувань версій';
        showMessage(messageVersions, 'error', msg);
    } finally {
        savingVersions.value = false;
    }
};
const saveSubscription = async () => {
    // Deprecated: trial is fully managed by stores; keep function to avoid runtime errors.
    showMessage(
        messageSubscription,
        'success',
        'Trial керується Google Play / App Store',
    );
};

onMounted(load);
</script>

<style scoped></style>
