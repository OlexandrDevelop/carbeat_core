<template>
    <div class="min-h-screen bg-gray-50">
        <header class="sticky top-0 z-10 backdrop-blur bg-white/70 border-b border-gray-200">
            <div class="mx-auto max-w-8xl px-6 py-4 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Masters</h1>
                <div class="flex gap-2">
                    <input v-model="filters.search" @input="debouncedFetch" type="search" placeholder="Search"
                           class="rounded-xl bg-gray-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <select v-model="filters.available" @change="fetchData" class="rounded-xl bg-gray-100 px-3 py-2 text-sm">
                        <option value="">Availability</option>
                        <option value="true">Available</option>
                        <option value="false">Unavailable</option>
                    </select>
                    <select v-model="filters.service_id" @change="fetchData" class="rounded-xl bg-gray-100 px-3 py-2 text-sm min-w-[180px]">
                        <option value="">All services</option>
                        <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <select v-model="filters.city_id" @change="fetchData" class="rounded-xl bg-gray-100 px-3 py-2 text-sm min-w-[160px]">
                        <option value="">All cities</option>
                        <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <select v-model="filters.uses_system" @change="fetchData" class="rounded-xl bg-gray-100 px-3 py-2 text-sm min-w-[160px]">
                        <option value="">All users</option>
                        <option value="true">Uses system</option>
                        <option value="false">Does not use</option>
                    </select>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="no_pagination" @change="fetchData" class="rounded" />
                        <span class="text-sm">Show all</span>
                    </label>

                    <button @click="confirmDeleteAll" class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm hover:bg-red-700">Delete all</button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-8xl px-6 py-6">
            <div v-if="inviteFeedback" class="mb-4 flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                <span>{{ inviteFeedback }}</span>
                <button class="text-emerald-700 underline" @click="inviteFeedback = null">Закрити</button>
            </div>

            <div
                v-if="selectedCount"
                class="mb-4 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900"
            >
                <span>Обрано {{ selectedCount }} майстрів</span>
                <div class="flex gap-3">
                    <button
                        @click="openInviteModal"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >
                        Надіслати інвайт
                    </button>
                    <button @click="clearSelection" class="text-blue-700 underline">Очистити</button>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="allVisibleSelected"
                                    @change="toggleSelectAllVisible"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                            </th>
                            <th @click="setSort('id')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer select-none">
                                <span>ID</span>
                                <span v-if="filters.sort_by === 'id'">{{ filters.sort_dir === 'asc' ? '▲' : '▼' }}</span>
                            </th>
                            <th @click="setSort('photo')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer select-none">
                                <span>Photo</span>
                                <span v-if="filters.sort_by === 'photo'">{{ filters.sort_dir === 'asc' ? '▲' : '▼' }}</span>
                            </th>
                            <th @click="setSort('name')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer select-none">
                                <span>Name</span>
                                <span v-if="filters.sort_by === 'name'">{{ filters.sort_dir === 'asc' ? '▲' : '▼' }}</span>
                            </th>
                            <th @click="setSort('city')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer select-none">
                                <span>City</span>
                                <span v-if="filters.sort_by === 'city'">{{ filters.sort_dir === 'asc' ? '▲' : '▼' }}</span>
                            </th>
                            <th @click="setSort('rating')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer select-none">
                                <span>Rating</span>
                                <span v-if="filters.sort_by === 'rating'">{{ filters.sort_dir === 'asc' ? '▲' : '▼' }}</span>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Subscription
                            </th>
                            <th @click="setSort('photos_count')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer select-none">
                                <span>Photos</span>
                                <span v-if="filters.sort_by === 'photos_count'">{{ filters.sort_dir === 'asc' ? '▲' : '▼' }}</span>
                            </th>
                            <th @click="setSort('available')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer select-none">
                                <span>Available</span>
                                <span v-if="filters.sort_by === 'available'">{{ filters.sort_dir === 'asc' ? '▲' : '▼' }}</span>
                            </th>
                            <th @click="setSort('last_login_at')" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer select-none">
                                <span>Last login</span>
                                <span v-if="filters.sort_by === 'last_login_at'">{{ filters.sort_dir === 'asc' ? '▲' : '▼' }}</span>
                            </th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="m in masters"
                            :key="m.id"
                            class="hover:bg-gray-50"
                            :class="[m.user_id !== 1 ? 'bg-yellow-50/50' : '', selectedIds.includes(m.id) ? 'bg-blue-50/80' : '']"
                        >
                            <td class="px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(m.id)"
                                    @change="toggleSelection(m.id)"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ m.id }}</td>
                            <td class="px-6 py-3">
                                <div class="h-10 w-10 overflow-hidden rounded bg-gray-100 ring-2" :class="m.user_id !== 1 ? 'ring-yellow-300' : 'ring-transparent'">
                                    <img
                                        v-if="m.main_thumb_url || m.main_photo"
                                        :src="m.main_thumb_url || m.main_photo"
                                        alt="Thumb"
                                        class="h-10 w-10 object-cover"
                                    />
                                </div>
                            </td>
                            <td class="px-6 py-3 text-sm font-medium text-gray-900 truncate max-w-[240px]">
                                <span class="mr-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs" :class="m.uses_system ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="m.uses_system ? 'bg-emerald-600' : 'bg-gray-500'" />
                                    {{ m.uses_system ? 'Uses system' : 'Guest' }}
                                </span>
                                {{ m.name }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ m.city?.name ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ m.reviews_avg_rating != null ? Number(m.reviews_avg_rating).toFixed(1) : '0.0' }}</td>
                            <td class="px-6 py-3 text-sm">
                                <div class="flex flex-col">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs"
                                          :class="m.is_premium ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600'">
                                        <span class="h-1.5 w-1.5 rounded-full"
                                              :class="m.is_premium ? 'bg-purple-600' : 'bg-gray-500'" />
                                        {{ m.is_premium ? 'Premium' : 'Free' }}
                                    </span>
                                    <span class="text-xs text-gray-500 mt-1">
                                        {{ m.premium_until ? new Date(m.premium_until).toLocaleDateString() : '—' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ m.photos_count ?? 0 }}</td>
                            <td class="px-6 py-3 text-sm">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs" :class="m.available ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="m.available ? 'bg-green-600' : 'bg-gray-500'" />
                                    {{ m.available ? 'Available' : 'Unavailable' }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ m.last_login_at ? new Date(m.last_login_at).toLocaleString() : '—' }}</td>
                            <td class="px-6 py-3 text-right space-x-3">
                                <Link :href="route('admin.masters.edit', { id: m.id })" class="text-blue-600 hover:text-blue-800">Edit</Link>
                                <button @click="confirmDelete(m)" class="text-red-600 hover:text-red-800">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="!no_pagination" class="flex items-center justify-between px-6 py-4">
                    <div class="text-sm text-gray-600">Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)</div>
                    <div class="flex items-center gap-2">
                        <button :disabled="meta.current_page <= 1" @click="goto(meta.current_page - 1)" class="rounded-lg border px-3 py-1.5 text-sm disabled:opacity-50">Prev</button>
                        <select v-model.number="perPage" @change="fetchData" class="rounded-lg border px-2 py-1.5 text-sm">
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                        </select>
                        <button :disabled="meta.current_page >= meta.last_page" @click="goto(meta.current_page + 1)" class="rounded-lg border px-3 py-1.5 text-sm disabled:opacity-50">Next</button>
                        <input v-model.number="jumpPage" @keyup.enter="goDirect" type="number" min="1" :max="meta.last_page" placeholder="Page" class="w-20 rounded-lg border px-2 py-1.5 text-sm" />
                        <button @click="goDirect" class="rounded-lg border px-3 py-1.5 text-sm">Go</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div v-if="inviteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4">
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-slate-900">Надіслати інвайт</h3>
                <button class="text-slate-500 hover:text-slate-700" @click="closeInviteModal">✕</button>
            </div>
            <p class="text-sm text-slate-600">
                Текст повідомлення. Використайте плейсхолдер
                <code class="rounded bg-slate-100 px-1 py-0.5">:link</code>
                — він буде автоматично замінений на діп-лінк до застосунку.
            </p>
            <textarea
                v-model="inviteMessage"
                rows="5"
                class="mt-4 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
            ></textarea>
            <p v-if="inviteError" class="mt-3 text-sm text-red-600">{{ inviteError }}</p>
            <div class="mt-6 flex justify-end gap-3">
                <button @click="closeInviteModal" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Скасувати
                </button>
                <button
                    @click="sendInvites"
                    :disabled="sendingInvite"
                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ sendingInvite ? 'Надсилання...' : 'Надіслати' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{ defaultInviteMessage: string }>();

const masters = ref<any[]>([]);
const services = ref<Array<{ id: number; name: string }>>([]);
const cities = ref<Array<{ id: number; name: string }>>([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const perPage = ref(20);
const no_pagination = ref(false);
const filters = ref<{ search: string; available: string; service_id: string | number; city_id: string | number; uses_system: string; sort_by: string; sort_dir: string }>({
    search: '',
    available: '',
    service_id: '',
    city_id: '',
    uses_system: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
});

const page = ref(1);
const jumpPage = ref<number | null>(null);

const selectedIds = ref<number[]>([]);
const inviteModalOpen = ref(false);
const inviteMessage = ref('');
const inviteError = ref<string | null>(null);
const inviteFeedback = ref<string | null>(null);
const sendingInvite = ref(false);

const visibleMasterIds = computed(() => masters.value.map((m) => m.id));
const allVisibleSelected = computed(
    () => visibleMasterIds.value.length > 0 && visibleMasterIds.value.every((id) => selectedIds.value.includes(id)),
);
const selectedCount = computed(() => selectedIds.value.length);

async function fetchData() {
    const params = new URLSearchParams();
    params.set('page', String(page.value));
    params.set('per_page', String(perPage.value));
    if (filters.value.search) params.set('search', filters.value.search);
    if (filters.value.available !== '') params.set('available', filters.value.available);
    if (filters.value.service_id !== '') params.set('service_id', String(filters.value.service_id));
    if (filters.value.city_id !== '') params.set('city_id', String(filters.value.city_id));
    if (filters.value.uses_system !== '') params.set('uses_system', String(filters.value.uses_system));
    if (filters.value.sort_by) params.set('sort_by', filters.value.sort_by);
    if (filters.value.sort_dir) params.set('sort_dir', filters.value.sort_dir);
    if (no_pagination.value) params.set('no_pagination', 'true');

    const { data } = await axios.get(`/admin-api/masters?${params.toString()}`);
    masters.value = data.data;
    meta.value = data.meta;
    jumpPage.value = meta.value.current_page;
}

async function loadServices() {
    const { data } = await axios.get('/admin-api/services');
    services.value = data;
}

async function loadCities() {
    const { data } = await axios.get('/admin-api/cities');
    cities.value = data;
}

function goto(p: number) {
    page.value = p;
    fetchData();
}

function goDirect() {
    if (!jumpPage.value) return;
    const target = Math.max(1, Math.min(jumpPage.value, meta.value.last_page));
    page.value = target;
    fetchData();
}

let debounceTimer: any;
function debouncedFetch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchData, 400);
}

function setSort(field: string) {
    if (filters.value.sort_by === field) {
        filters.value.sort_dir = filters.value.sort_dir === 'asc' ? 'desc' : 'asc';
    } else {
        filters.value.sort_by = field;
        filters.value.sort_dir = field === 'name' ? 'asc' : 'desc';
    }
    page.value = 1;
    fetchData();
}

async function confirmDelete(m: any) {
    if (! confirm(`Delete master #${m.id} (${m.name})?`)) return;
    await axios.delete(`/admin-api/masters/${m.id}`);
    if (masters.value.length === 1 && page.value > 1) {
        page.value -= 1;
    }
    await fetchData();
}

async function confirmDeleteAll() {
    if (! confirm('Are you sure you want to delete ALL masters? This action cannot be undone.')) return;
    await axios.delete('/admin-api/masters');
    page.value = 1;
    await fetchData();
}

function toggleSelection(id: number) {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter((item) => item !== id);
    } else {
        selectedIds.value = [...selectedIds.value, id];
    }
}

function toggleSelectAllVisible() {
    if (allVisibleSelected.value) {
        selectedIds.value = selectedIds.value.filter((id) => !visibleMasterIds.value.includes(id));
    } else {
        const newIds = visibleMasterIds.value.filter((id) => !selectedIds.value.includes(id));
        selectedIds.value = [...selectedIds.value, ...newIds];
    }
}

function clearSelection() {
    selectedIds.value = [];
}

function openInviteModal() {
    inviteMessage.value = inviteMessage.value || props.defaultInviteMessage || 'Carbeat: керуйте своїм профілем → :link';
    inviteModalOpen.value = true;
    inviteError.value = null;
}

function closeInviteModal() {
    inviteModalOpen.value = false;
}

async function sendInvites() {
    if (! selectedIds.value.length) {
        return;
    }

    sendingInvite.value = true;
    inviteError.value = null;

    try {
        const { data } = await axios.post('/admin-api/masters/invite', {
            master_ids: selectedIds.value,
            message: inviteMessage.value,
        });
        inviteFeedback.value = `Відправлено ${data.sent} із ${data.requested}. Пропущено ${data.skipped.length}.`;
        inviteModalOpen.value = false;
        selectedIds.value = [];
    } catch (error: any) {
        inviteError.value = error?.response?.data?.message || 'Не вдалося надіслати інвайти';
    } finally {
        sendingInvite.value = false;
    }
}

onMounted(async () => {
    await Promise.all([loadServices(), loadCities(), fetchData()]);
});
</script>

<style scoped>
/***** minimal Apple-like clean look handled via Tailwind *****/
</style>
