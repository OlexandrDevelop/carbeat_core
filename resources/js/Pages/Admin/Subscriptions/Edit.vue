<template>
    <div class="p-6">
        <h1 class="mb-4 text-2xl font-semibold">Edit Subscription #{{ id }}</h1>

        <div class="grid max-w-2xl grid-cols-2 gap-4">
            <label class="block">
                <span class="text-sm text-gray-600">Status</span>
                <select v-model="form.status" class="w-full rounded border p-2">
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </label>
            <label class="block">
                <span class="text-sm text-gray-600">Product ID</span>
                <input
                    v-model="form.product_id"
                    class="w-full rounded border p-2"
                />
            </label>
            <label class="col-span-2 block">
                <span class="text-sm text-gray-600">Expires At</span>
                <input
                    v-model="form.expires_at"
                    type="datetime-local"
                    class="w-full rounded border p-2"
                />
            </label>
        </div>

        <div class="mt-4 flex gap-2">
            <button
                @click="save()"
                class="rounded bg-blue-600 px-4 py-2 text-white"
            >
                Save
            </button>
            <button @click="back()" class="rounded bg-gray-200 px-4 py-2">
                Back
            </button>
        </div>

        <div class="mt-8 max-w-2xl border-t pt-4">
            <h2 class="mb-2 text-lg font-semibold">Verify Subscription</h2>
            <div class="grid grid-cols-2 gap-4">
                <label class="block">
                    <span class="text-sm text-gray-600">User ID</span>
                    <input
                        v-model.number="verifyForm.user_id"
                        type="number"
                        class="w-full rounded border p-2"
                    />
                </label>
                <label class="block">
                    <span class="text-sm text-gray-600">Platform</span>
                    <select
                        v-model="verifyForm.platform"
                        class="w-full rounded border p-2"
                    >
                        <option value="apple">Apple</option>
                        <option value="google">Google</option>
                    </select>
                </label>
                <label class="col-span-2 block">
                    <span class="text-sm text-gray-600">Receipt/Token</span>
                    <textarea
                        v-model="verifyForm.receipt_token"
                        class="w-full rounded border p-2"
                        rows="3"
                    ></textarea>
                </label>
                <label class="col-span-2 block">
                    <span class="text-sm text-gray-600"
                        >Product ID (optional)</span
                    >
                    <input
                        v-model="verifyForm.product_id"
                        class="w-full rounded border p-2"
                    />
                </label>
            </div>
            <div class="mt-2">
                <button
                    @click="verify()"
                    class="rounded bg-green-600 px-4 py-2 text-white"
                >
                    Verify & Store
                </button>
                <span class="ml-2 text-sm text-gray-600" v-if="lastVerify"
                    >Last verify: {{ lastVerify }}</span
                >
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const props = defineProps({ subscriptionId: Number });
const id = props.subscriptionId;
const form = ref({ status: 'active', product_id: '', expires_at: '' });
const lastVerify = ref('');
const verifyForm = ref({
    user_id: 0,
    platform: 'apple',
    receipt_token: '',
    product_id: '',
});

const load = async () => {
    const { data } = await axios.get(`/admin-api/subscriptions/${id}`);
    const s = data;
    form.value.status = s.status;
    form.value.product_id = s.product_id || '';
    form.value.expires_at = s.expires_at ? s.expires_at.substring(0, 16) : '';
};

const save = async () => {
    await axios.put(`/admin-api/subscriptions/${id}`, form.value);
    back();
};

const back = () => router.visit('/admin/subscriptions');

const verify = async () => {
    const { data } = await axios.post(
        `/admin-api/subscriptions/verify`,
        verifyForm.value,
    );
    lastVerify.value = JSON.stringify(data);
};

onMounted(load);
</script>

<style scoped></style>
