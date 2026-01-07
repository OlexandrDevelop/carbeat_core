<template>
    <div class="min-h-screen bg-gray-50">
        <header
            class="sticky top-0 z-10 border-b border-gray-200 bg-white/70 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4"
            >
                <h1 class="text-2xl font-semibold text-gray-900">Services</h1>
                <div class="flex items-center gap-2">
                    <input
                        v-model="search"
                        @input="debouncedFetch"
                        type="search"
                        placeholder="Search"
                        class="rounded-xl bg-gray-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <button
                        :disabled="selectedIds.length === 0"
                        @click="openBulkPreview"
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50"
                    >
                        Delete selected ({{ selectedIds.length }})
                    </button>
                    <button
                        :disabled="selectedIds.length < 2"
                        @click="openMergeModal"
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700 disabled:opacity-50"
                    >
                        Merge selected
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-6 py-6">
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">
                                <input
                                    type="checkbox"
                                    :checked="allChecked"
                                    @change="toggleCheckAll($event)"
                                />
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                ID
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                <button
                                    class="inline-flex items-center gap-1 hover:text-gray-700"
                                    @click="toggleSort('masters_count')"
                                >
                                    Masters
                                    <i
                                        v-if="
                                            sortBy === 'masters_count' &&
                                            sortDir === 'asc'
                                        "
                                        class="fa fa-sort-asc"
                                    ></i>
                                    <i
                                        v-else-if="
                                            sortBy === 'masters_count' &&
                                            sortDir === 'desc'
                                        "
                                        class="fa fa-sort-desc"
                                    ></i>
                                    <i
                                        v-else
                                        class="fa fa-sort text-gray-300"
                                    ></i>
                                </button>
                            </th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="s in services"
                            :key="s.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-3">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(s.id)"
                                    @change="toggleChecked(s.id, $event)"
                                />
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                {{ s.id }}
                            </td>
                            <td
                                class="px-6 py-3 text-sm font-medium text-gray-900"
                            >
                                {{ s.name }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                {{ s.masters_count }}
                            </td>
                            <td class="space-x-3 px-6 py-3 text-right">
                                <Link
                                    :href="
                                        route('admin.services.edit', {
                                            id: s.id,
                                        })
                                    "
                                    class="text-blue-600 hover:text-blue-800"
                                    >Edit</Link
                                >
                                <button
                                    @click="confirmDelete(s)"
                                    class="text-red-600 hover:text-red-800"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>

        <!-- Single delete modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        >
            <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-lg">
                <h3 class="mb-2 text-lg font-semibold">Confirm deletion</h3>
                <p class="mb-4 text-sm text-gray-600">
                    Service
                    <span class="font-medium"
                        >"{{ preview?.service?.name }}"</span
                    >
                    will be deleted.
                </p>
                <div class="mb-4 space-y-2">
                    <p class="text-sm">
                        Affected masters:
                        <span class="font-medium">{{
                            preview?.affected_masters_count
                        }}</span>
                    </p>
                    <div
                        v-if="preview?.masters_to_delete?.length"
                        class="text-sm"
                    >
                        The following masters will be fully deleted because this
                        is their only service:
                        <ul class="mt-2 max-h-40 list-disc overflow-auto pl-6">
                            <li
                                v-for="m in preview?.masters_to_delete"
                                :key="m.id"
                            >
                                <Link
                                    :href="
                                        route('admin.masters.edit', {
                                            id: m.id,
                                        })
                                    "
                                    class="text-blue-600 hover:text-blue-800"
                                    >#{{ m.id }} — {{ m.name }}</Link
                                >
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button
                        @click="closeModal"
                        class="rounded-lg border px-4 py-2 text-sm"
                    >
                        Cancel
                    </button>
                    <button
                        @click="performDelete"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Bulk delete modal -->
        <div
            v-if="showBulkModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        >
            <div class="w-full max-w-3xl rounded-xl bg-white p-6 shadow-lg">
                <h3 class="mb-2 text-lg font-semibold">
                    Confirm bulk deletion
                </h3>
                <p class="mb-4 text-sm text-gray-600">
                    Selected services will be deleted.
                </p>
                <div class="mb-4 space-y-2">
                    <p class="text-sm">
                        Affected masters:
                        <span class="font-medium">{{
                            bulkPreview?.affected_masters_count || 0
                        }}</span>
                    </p>
                    <div
                        v-if="bulkPreview?.masters_to_delete?.length"
                        class="text-sm"
                    >
                        The following masters will be fully deleted because they
                        will have no services left:
                        <ul class="mt-2 max-h-60 list-disc overflow-auto pl-6">
                            <li
                                v-for="m in bulkPreview?.masters_to_delete"
                                :key="m.id"
                            >
                                <Link
                                    :href="
                                        route('admin.masters.edit', {
                                            id: m.id,
                                        })
                                    "
                                    class="text-blue-600 hover:text-blue-800"
                                    >#{{ m.id }} — {{ m.name }}</Link
                                >
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button
                        @click="closeBulkModal"
                        class="rounded-lg border px-4 py-2 text-sm"
                    >
                        Cancel
                    </button>
                    <button
                        @click="performBulkDelete"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700"
                    >
                        Delete selected
                    </button>
                </div>
            </div>
        </div>

        <!-- Merge services modal -->
        <div
            v-if="showMergeModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
        >
            <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-lg">
                <h3 class="mb-2 text-lg font-semibold">Merge services</h3>
                <p class="mb-4 text-sm text-gray-600">
                    Choose the primary service. All selected services will be
                    merged into the primary; other services will be deleted.
                </p>
                <div class="max-h-64 space-y-2 overflow-auto">
                    <label
                        v-for="s in mergeCandidates"
                        :key="s.id"
                        class="flex items-center gap-2 text-sm"
                    >
                        <input
                            type="radio"
                            name="primary"
                            :value="s.id"
                            v-model.number="mergePrimaryId"
                        />
                        <span>#{{ s.id }} — {{ s.name }}</span>
                    </label>
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <button
                        @click="closeMergeModal"
                        class="rounded-lg border px-4 py-2 text-sm"
                    >
                        Cancel
                    </button>
                    <button
                        @click="performMerge"
                        :disabled="!mergePrimaryId"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700 disabled:opacity-50"
                    >
                        Merge
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const services = ref<
    Array<{ id: number; name: string; masters_count: number }>
>([]);
const search = ref('');
const sortBy = ref<'name' | 'masters_count'>('name');
const sortDir = ref<'asc' | 'desc'>('asc');

const showModal = ref(false);
const pendingServiceId = ref<number | null>(null);
const preview = ref<any>(null);

const selectedIds = ref<number[]>([]);
const allChecked = computed(
    () =>
        services.value.length > 0 &&
        selectedIds.value.length === services.value.length,
);

const showBulkModal = ref(false);
const bulkPreview = ref<any>(null);
// Merge modal state
const showMergeModal = ref(false);
const mergeCandidates = ref<Array<{ id: number; name: string }>>([]);
const mergePrimaryId = ref<number | null>(null);

async function fetchData() {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (sortBy.value) params.set('sort_by', sortBy.value);
    if (sortDir.value) params.set('sort_dir', sortDir.value);
    const { data } = await axios.get(
        `/admin-api/admin-services?${params.toString()}`,
    );
    services.value = data.data;
    // keep only ids still present
    const present = new Set(services.value.map((s) => s.id));
    selectedIds.value = selectedIds.value.filter((id) => present.has(id));
}

function toggleSort(column: 'masters_count') {
    if (sortBy.value === column) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDir.value = 'desc';
    }
    fetchData();
}

function toggleChecked(id: number, e: Event) {
    const checked = (e.target as HTMLInputElement).checked;
    if (checked) {
        if (!selectedIds.value.includes(id)) selectedIds.value.push(id);
    } else {
        selectedIds.value = selectedIds.value.filter((x) => x !== id);
    }
}

function toggleCheckAll(e: Event) {
    const checked = (e.target as HTMLInputElement).checked;
    if (checked) {
        selectedIds.value = services.value.map((s) => s.id);
    } else {
        selectedIds.value = [];
    }
}

let debounceTimer: any;
function debouncedFetch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchData, 400);
}

async function confirmDelete(s: { id: number; name: string }) {
    const { data } = await axios.get(
        `/admin-api/admin-services/${s.id}/delete-preview`,
    );
    preview.value = data;
    pendingServiceId.value = s.id;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    preview.value = null;
    pendingServiceId.value = null;
}

async function performDelete() {
    if (!pendingServiceId.value) return;
    await axios.delete(`/admin-api/admin-services/${pendingServiceId.value}`);
    closeModal();
    await fetchData();
}

async function openBulkPreview() {
    if (selectedIds.value.length === 0) return;
    const { data } = await axios.post(
        '/admin-api/admin-services/bulk/delete-preview',
        { ids: selectedIds.value },
    );
    bulkPreview.value = data;
    showBulkModal.value = true;
}

function closeBulkModal() {
    showBulkModal.value = false;
    bulkPreview.value = null;
}

async function performBulkDelete() {
    if (selectedIds.value.length === 0) return;
    await axios.post('/admin-api/admin-services/bulk/delete', {
        ids: selectedIds.value,
    });
    selectedIds.value = [];
    closeBulkModal();
    await fetchData();
}

function openMergeModal() {
    const map = new Map(services.value.map((s) => [s.id, s.name]));
    mergeCandidates.value = selectedIds.value
        .map((id) => ({ id, name: map.get(id) || `Service #${id}` }))
        .sort((a, b) => a.name.localeCompare(b.name));
    mergePrimaryId.value = mergeCandidates.value.length
        ? mergeCandidates.value[0].id
        : null;
    showMergeModal.value = true;
}

function closeMergeModal() {
    showMergeModal.value = false;
    mergeCandidates.value = [];
    mergePrimaryId.value = null;
}

async function performMerge() {
    if (!mergePrimaryId.value || selectedIds.value.length < 2) return;
    await axios.post('/admin-api/admin-services/merge', {
        service_ids: selectedIds.value,
        primary_id: mergePrimaryId.value,
    });
    closeMergeModal();
    selectedIds.value = [mergePrimaryId.value];
    await fetchData();
}

onMounted(() => {
    fetchData();
});
</script>
