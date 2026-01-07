<template>
    <div class="p-6">
        <h1 class="mb-4 text-2xl font-semibold">Subscriptions</h1>
        <div class="mb-4 flex gap-2">
            <input
                v-model="filters.phone"
                placeholder="Phone"
                class="rounded border p-2"
            />
            <select v-model="filters.platform" class="rounded border p-2">
                <option value="">All platforms</option>
                <option value="apple">Apple</option>
                <option value="google">Google</option>
            </select>
            <select v-model="filters.status" class="rounded border p-2">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <button
                @click="load()"
                class="rounded bg-blue-600 px-4 py-2 text-white"
            >
                Search
            </button>
            <button
                @click="exportCsv()"
                class="rounded bg-gray-600 px-4 py-2 text-white"
            >
                Export CSV
            </button>
        </div>

        <table class="w-full border text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="p-2 text-left">ID</th>
                    <th class="p-2 text-left">User</th>
                    <th class="p-2 text-left">Phone</th>
                    <th class="p-2 text-left">Platform</th>
                    <th class="p-2 text-left">Product</th>
                    <th class="p-2 text-left">External ID</th>
                    <th class="p-2 text-left">Status</th>
                    <th class="p-2 text-left">Expires</th>
                    <th class="p-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="s in items" :key="s.id" class="border-t">
                    <td class="p-2">{{ s.id }}</td>
                    <td class="p-2">{{ s.user_id }}</td>
                    <td class="p-2">{{ s.user_phone }}</td>
                    <td class="p-2">{{ s.platform }}</td>
                    <td class="p-2">{{ s.product_id }}</td>
                    <td class="max-w-[240px] truncate p-2">
                        {{ s.external_id }}
                    </td>
                    <td class="p-2">{{ s.status }}</td>
                    <td class="p-2">{{ s.expires_at }}</td>
                    <td class="flex gap-2 p-2">
                        <button
                            @click="goEdit(s.id)"
                            class="rounded bg-gray-200 px-2 py-1"
                        >
                            Edit
                        </button>
                        <button
                            @click="remove(s.id)"
                            class="rounded bg-red-600 px-2 py-1 text-white"
                        >
                            Delete
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="mt-4 flex gap-2">
            <button @click="prev()" class="rounded border px-3 py-1">
                Prev
            </button>
            <div>Page {{ meta.current_page }} / {{ meta.last_page }}</div>
            <button @click="next()" class="rounded border px-3 py-1">
                Next
            </button>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const filters = ref({ phone: '', platform: '', status: '' });
const items = ref([]);
const meta = ref({ current_page: 1, last_page: 1 });
let page = 1;

const load = async () => {
    const { data } = await axios.get(`/admin-api/subscriptions`, {
        params: { ...filters.value, page },
    });
    items.value = data.data;
    meta.value = data.meta;
};

const next = () => {
    if (page < meta.value.last_page) {
        page++;
        load();
    }
};
const prev = () => {
    if (page > 1) {
        page--;
        load();
    }
};
const goEdit = (id) => router.visit(`/admin/subscriptions/${id}/edit`);
const remove = async (id) => {
    await axios.delete(`/admin-api/subscriptions/${id}`);
    load();
};
const exportCsv = () => {
    window.location.href =
        `/admin-api/subscriptions/export?` +
        new URLSearchParams(filters.value).toString();
};

onMounted(load);
</script>

<style scoped></style>
