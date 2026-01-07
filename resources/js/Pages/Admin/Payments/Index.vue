<template>
    <div class="p-6">
        <h1 class="mb-4 text-2xl font-semibold">Payment Settings</h1>

        <div class="grid gap-6 md:grid-cols-2">
            <section class="rounded border p-4">
                <h2 class="mb-2 font-semibold">Apple</h2>
                <div class="space-y-2">
                    <input
                        v-model="form.apple.issuer_id"
                        placeholder="Issuer ID"
                        class="w-full rounded border p-2"
                    />
                    <input
                        v-model="form.apple.key_id"
                        placeholder="Key ID"
                        class="w-full rounded border p-2"
                    />
                    <textarea
                        v-model="form.apple.private_key"
                        placeholder="Private Key (PKCS8)"
                        rows="4"
                        class="w-full rounded border p-2"
                    ></textarea>
                    <input
                        v-model="form.apple.bundle_id"
                        placeholder="Bundle ID"
                        class="w-full rounded border p-2"
                    />
                    <label class="inline-flex items-center gap-2">
                        <input
                            type="checkbox"
                            v-model="form.apple.use_sandbox"
                        />
                        <span>Use Sandbox</span>
                    </label>
                </div>
            </section>

            <section class="rounded border p-4">
                <h2 class="mb-2 font-semibold">Google</h2>
                <div class="space-y-2">
                    <textarea
                        v-model="form.google.service_account_json"
                        placeholder="Service Account JSON"
                        rows="6"
                        class="w-full rounded border p-2"
                    ></textarea>
                    <input
                        v-model="form.google.package_name"
                        placeholder="Package Name"
                        class="w-full rounded border p-2"
                    />
                </div>
            </section>
        </div>

        <div class="mt-4">
            <button
                @click="save()"
                class="rounded bg-blue-600 px-4 py-2 text-white"
            >
                Save
            </button>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';

const form = ref({
    apple: {
        issuer_id: '',
        key_id: '',
        private_key: '',
        bundle_id: '',
        use_sandbox: true,
    },
    google: { service_account_json: '', package_name: '' },
});

const load = async () => {
    const { data } = await axios.get('/admin-api/payment-settings');
    form.value = Object.assign(form.value, data);
};

const save = async () => {
    await axios.put('/admin-api/payment-settings', form.value);
};

onMounted(load);
</script>

<style scoped></style>
