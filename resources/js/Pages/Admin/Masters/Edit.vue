<template>
    <div class="min-h-screen bg-gray-50">
        <header
            class="sticky top-0 z-10 border-b border-gray-200 bg-white/70 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-3xl items-center justify-between px-6 py-4"
            >
                <h1 class="text-2xl font-semibold text-gray-900">
                    Edit Master #{{ master?.id }}
                </h1>
                <div class="flex items-center gap-2">
                    <a
                        v-if="master?.slug"
                        :href="publicProfileUrl(master.slug)"
                        target="_blank"
                        rel="noopener"
                        class="rounded-xl border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        View public page
                    </a>
                    <Link
                        href="/admin/masters"
                        class="text-gray-600 hover:text-gray-900"
                        >Back</Link
                    >
                    <button
                        @click="save"
                        :disabled="saving"
                        class="rounded-xl bg-black px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        {{ saving ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-3xl space-y-6 px-6 py-6">
            <div
                class="space-y-5 overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-gray-100"
                    >
                        <img
                            v-if="master?.main_photo"
                            :src="master.main_photo"
                            alt="Photo"
                            class="h-20 w-20 object-cover"
                        />
                    </div>
                    <div>
                        <div class="text-sm text-gray-700">Rating</div>
                        <div class="text-2xl font-semibold">
                            {{
                                master?.reviews_avg_rating != null
                                    ? Number(master.reviews_avg_rating).toFixed(
                                          1,
                                      )
                                    : '0.0'
                            }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Auto-updates from reviews
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        class="rounded-2xl border border-gray-100 bg-gradient-to-br from-gray-50 to-white p-4"
                    >
                        <div class="text-sm text-gray-600">Subscription</div>
                        <div
                            class="mt-1 text-2xl font-semibold"
                            :class="
                                isPremium ? 'text-purple-700' : 'text-gray-800'
                            "
                        >
                            {{ isPremium ? 'Premium' : 'Free plan' }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            {{
                                isPremium
                                    ? `Valid until ${premiumExpiresLabel}`
                                    : 'No active subscription'
                            }}
                        </div>
                    </div>
                    <div
                        class="rounded-2xl border border-gray-100 bg-gradient-to-br from-gray-50 to-white p-4"
                    >
                        <div class="text-sm text-gray-600">Limits</div>
                        <div class="mt-1 text-sm text-gray-800">
                            {{
                                isPremium
                                    ? 'Premium limits applied (photos, description, services)'
                                    : 'Free limits applied'
                            }}
                        </div>
                        <div
                            v-if="master?.premium_until"
                            class="mt-1 text-xs text-gray-500"
                        >
                            Expires: {{ premiumExpiresLabel }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Name</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            class="mt-1 w-full rounded-xl bg-gray-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Slug</label
                        >
                        <input
                            v-model="form.slug"
                            type="text"
                            class="mt-1 w-full rounded-xl bg-gray-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Phone</label
                        >
                        <input
                            v-model="form.phone"
                            type="text"
                            class="mt-1 w-full rounded-xl bg-gray-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >City</label
                        >
                        <select
                            v-model.number="form.city_id"
                            class="mt-1 w-full rounded-xl bg-gray-100 px-4 py-2 text-sm"
                        >
                            <option :value="null">Select city...</option>
                            <option
                                v-for="c in cities"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Main service</label
                        >
                        <select
                            v-model.number="form.service_id"
                            class="mt-1 w-full rounded-xl bg-gray-100 px-4 py-2 text-sm"
                        >
                            <option :value="null">Select service...</option>
                            <option
                                v-for="s in services"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.name }}
                            </option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700"
                            >Address</label
                        >
                        <input
                            v-model="form.address"
                            type="text"
                            class="mt-1 w-full rounded-xl bg-gray-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Latitude</label
                        >
                        <input
                            v-model.number="form.latitude"
                            type="number"
                            step="0.000001"
                            min="-90"
                            max="90"
                            class="mt-1 w-full rounded-xl bg-gray-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Longitude</label
                        >
                        <input
                            v-model.number="form.longitude"
                            type="number"
                            step="0.000001"
                            min="-180"
                            max="180"
                            class="mt-1 w-full rounded-xl bg-gray-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700"
                            >Location</label
                        >
                        <div
                            id="map"
                            class="mt-2 h-64 w-full overflow-hidden rounded-xl border"
                        ></div>
                        <div class="mt-1 text-xs text-gray-500">
                            Drag the marker or click on map to set coordinates
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Description</label
                    >
                    <textarea
                        v-model="form.description"
                        rows="4"
                        class="mt-1 w-full rounded-xl bg-gray-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Services</label
                    >
                    <div class="mt-1 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <label
                            v-for="s in services"
                            :key="s.id"
                            class="flex items-center gap-2 text-sm"
                        >
                            <input
                                type="checkbox"
                                :value="s.id"
                                v-model="form.service_ids"
                            />
                            <span>{{ s.name }}</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="save"
                        :disabled="saving"
                        class="rounded-xl bg-black px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        {{ saving ? 'Saving...' : 'Save changes' }}
                    </button>
                    <span v-if="saved" class="text-sm text-green-600"
                        >Saved</span
                    >
                </div>
            </div>

            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Gallery</h2>
                </div>
                <div
                    class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4"
                >
                    <div
                        v-for="p in master?.photos || []"
                        :key="p.id"
                        class="group relative"
                    >
                        <img
                            :src="p.url"
                            alt="Photo"
                            class="h-32 w-full cursor-zoom-in rounded-lg object-cover"
                            @click="openPreview(p.url)"
                        />
                        <button
                            @click="deletePhoto(p)"
                            class="absolute right-2 top-2 rounded bg-red-600/90 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview Modal -->
            <div
                v-if="previewUrl"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/70"
                @click.self="closePreview"
            >
                <div class="relative max-h-[90vh] max-w-[90vw]">
                    <button
                        @click="closePreview"
                        class="absolute -top-10 right-0 rounded bg-white/90 px-3 py-1 text-sm text-gray-800"
                    >
                        Close
                    </button>
                    <img
                        :src="previewUrl"
                        alt="Full preview"
                        class="max-h-[90vh] max-w-[90vw] rounded-lg object-contain"
                    />
                </div>
            </div>

            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Reviews</h2>
                    <div class="flex items-center gap-2">
                        <select
                            v-model.number="newReview.user_id"
                            class="min-w-[160px] rounded-xl bg-gray-100 px-3 py-2 text-sm"
                        >
                            <option :value="null">Select user...</option>
                            <option
                                v-for="u in users"
                                :key="u.id"
                                :value="u.id"
                            >
                                {{ u.name || u.phone }} ({{ u.phone }})
                            </option>
                        </select>
                        <input
                            v-model.number="newReview.rating"
                            type="number"
                            step="0.5"
                            min="0"
                            max="5"
                            placeholder="Rating"
                            class="w-24 rounded-xl bg-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <input
                            v-model="newReview.review"
                            type="text"
                            placeholder="Comment"
                            class="w-64 rounded-xl bg-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <button
                            @click="addReview"
                            class="rounded-xl bg-black px-4 py-2 text-sm text-white"
                        >
                            Add
                        </button>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    <div
                        v-for="r in reviews"
                        :key="r.id"
                        class="flex items-start justify-between py-3"
                    >
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                Rating: {{ r.rating }}
                            </div>
                            <div class="text-sm text-gray-600">
                                {{ r.review || '—' }}
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ new Date(r.created_at).toLocaleString() }}
                            </div>
                            <div class="text-xs text-gray-500">
                                By:
                                {{
                                    r.user?.name ||
                                    r.user?.phone ||
                                    'User #' + r.user_id
                                }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="editReview(r)"
                                class="text-sm text-blue-600 hover:text-blue-800"
                            >
                                Edit
                            </button>
                            <button
                                @click="deleteReview(r)"
                                class="text-sm text-red-600 hover:text-red-800"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, onMounted, reactive, ref } from 'vue';

const props = defineProps<{ masterId: number }>();
const page = usePage();

const master = ref<any>(null);
const saving = ref(false);
const saved = ref(false);

const services = ref<Array<{ id: number; name: string }>>([]);
const cities = ref<Array<{ id: number; name: string }>>([]);
const users = ref<
    Array<{ id: number; name: string | null; phone: string | null }>
>([]);

const form = reactive<{
    name: string;
    slug: string;
    phone: string;
    address: string;
    latitude: number | null;
    longitude: number | null;
    description: string | null;
    service_id: number | null;
    service_ids: number[];
    city_id: number | null;
}>({
    name: '',
    slug: '',
    phone: '',
    address: '',
    latitude: null,
    longitude: null,
    description: '',
    service_id: null,
    service_ids: [],
    city_id: null,
});

const reviews = ref<
    Array<{
        id: number;
        rating: number;
        review: string;
        created_at: string;
        user_id: number | null;
        user?: any;
    }>
>([]);
const newReview = reactive<{
    user_id: number | null;
    rating: number | null;
    review: string | null;
}>({ user_id: null, rating: null, review: '' });

const previewUrl = ref<string | null>(null);
const isPremium = computed(() => !!master.value?.is_premium);
const premiumExpiresLabel = computed(() => {
    if (!master.value?.premium_until) return '—';
    return new Date(master.value.premium_until).toLocaleString();
});
const publicProfilePrefix = computed(() =>
    (page.props as { brand?: string }).brand === 'floxcity' ? '/salon' : '/sto',
);
function openPreview(url: string) {
    previewUrl.value = url;
}
function closePreview() {
    previewUrl.value = null;
}

function publicProfileUrl(slug: string): string {
    return `${publicProfilePrefix.value}/${encodeURIComponent(slug)}`;
}

let map: L.Map | null = null;
let marker: L.Marker | null = null;

async function load() {
    const [masterRes, servicesRes, reviewsRes, usersRes, citiesRes] =
        await Promise.all([
            axios.get(`/admin-api/masters/${props.masterId}`),
            axios.get('/admin-api/services'),
            axios.get(`/admin-api/masters/${props.masterId}/reviews`),
            axios.get('/admin-api/users'),
            axios.get('/admin-api/cities'),
        ]);
    master.value = masterRes.data;
    services.value = servicesRes.data;
    reviews.value = reviewsRes.data;
    users.value = usersRes.data;
    cities.value = citiesRes.data;

    form.name = master.value.name ?? '';
    form.slug = master.value.slug ?? '';
    form.phone = master.value.phone ?? '';
    form.address = master.value.address ?? '';
    form.latitude = master.value.latitude ?? null;
    form.longitude = master.value.longitude ?? null;
    form.description = master.value.description ?? '';
    form.service_id = master.value.service_id ?? null;
    form.service_ids = (master.value.services || []).map((s: any) => s.id);
    form.city_id = master.value.city?.id ?? master.value.city_id ?? null;

    // Initialize map
    initMap();
}

async function save() {
    saving.value = true;
    saved.value = false;
    try {
        const { data } = await axios.put(
            `/admin-api/masters/${props.masterId}`,
            form,
        );
        master.value = data;
        saved.value = true;
    } finally {
        saving.value = false;
    }
}

function deletePhoto(p: any) {
    if (!confirm('Delete this photo?')) return;
    axios
        .delete(`/admin-api/masters/${props.masterId}/gallery/${p.id}`)
        .then(async () => {
            const { data } = await axios.get(
                `/admin-api/masters/${props.masterId}`,
            );
            master.value = data;
        });
}

async function addReview() {
    if (newReview.user_id === null || newReview.rating === null) return;
    await axios.post(`/admin-api/masters/${props.masterId}/reviews`, newReview);
    await reloadReviewsAndMaster();
    newReview.user_id = null;
    newReview.rating = null;
    newReview.review = '';
}

function editReview(r: any) {
    const userIdStr = prompt('User ID:', String(r.user_id ?? ''));
    const user_id = userIdStr ? Number(userIdStr) : null;
    const rating = Number(prompt('New rating (0..5):', r.rating));
    if (Number.isNaN(rating)) return;
    const review = prompt('New comment:', r.review || '') || '';
    const payload: any = { rating, review };
    if (user_id) payload.user_id = user_id;
    axios
        .put(`/admin-api/reviews/${r.id}`, payload)
        .then(reloadReviewsAndMaster);
}

function deleteReview(r: any) {
    if (!confirm('Delete this review?')) return;
    axios.delete(`/admin-api/reviews/${r.id}`).then(reloadReviewsAndMaster);
}

async function reloadReviewsAndMaster() {
    const [{ data: reviewsData }, { data: masterData }] = await Promise.all([
        axios.get(`/admin-api/masters/${props.masterId}/reviews`),
        axios.get(`/admin-api/masters/${props.masterId}`),
    ]);
    reviews.value = reviewsData;
    master.value = masterData;
}

onMounted(load);

function initMap() {
    const mapEl = document.getElementById('map');
    if (!mapEl) return;
    const lat = form.latitude ?? 50.4501;
    const lng = form.longitude ?? 30.5234;
    const zoom = form.latitude && form.longitude ? 14 : 11;
    if (!map) {
        map = L.map('map').setView([lat, lng], zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors',
        }).addTo(map);
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', () => {
            const pos = marker!.getLatLng();
            form.latitude = Number(pos.lat.toFixed(6));
            form.longitude = Number(pos.lng.toFixed(6));
        });
        map.on('click', (e: any) => {
            const { lat, lng } = e.latlng;
            if (marker) marker.setLatLng([lat, lng]);
            form.latitude = Number(lat.toFixed(6));
            form.longitude = Number(lng.toFixed(6));
        });
        // Ensure proper rendering after container became visible
        setTimeout(() => map && map.invalidateSize(), 0);
    } else {
        map.setView([lat, lng], zoom);
        if (marker) marker.setLatLng([lat, lng]);
        setTimeout(() => map && map.invalidateSize(), 0);
    }
}
</script>

<style scoped></style>
