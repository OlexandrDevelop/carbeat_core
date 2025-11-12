<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- SEO Meta Tags -->
        <Head>
            <title>{{ metaTags.title }}</title>
            <meta name="description" :content="metaTags.description" />
            <meta name="keywords" :content="metaTags.keywords" />
            <link rel="canonical" :href="metaTags.canonicalUrl" />

            <!-- Open Graph -->
            <meta property="og:title" :content="metaTags.ogTitle" />
            <meta property="og:description" :content="metaTags.ogDescription" />
            <meta property="og:type" content="website" />
            <meta property="og:url" :href="metaTags.ogUrl" />
            <meta property="og:image" :content="metaTags.ogImage" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" :content="metaTags.ogTitle" />
            <meta
                name="twitter:description"
                :content="metaTags.ogDescription"
            />
            <meta name="twitter:image" :content="metaTags.ogImage" />
        </Head>

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Page Title and Description -->
            <div class="mb-8 text-center">
                <h1
                    class="mb-4 text-4xl font-bold text-gray-900 dark:text-white"
                >
                    {{ $t('masters.title') }}
                </h1>
                <p
                    class="mx-auto max-w-3xl text-lg text-gray-600 dark:text-gray-400"
                >
                    {{ $t('masters.description') }}
                </p>
            </div>

            <!-- Filters and Controls Row -->
            <div class="mb-8">
                <!-- Filters -->
                <div class="w-full">
                    <MastersFilters
                        v-model="filters"
                        :available-services="availableServices"
                        :cities="cities"
                        @filters-changed="handleFiltersChanged"
                    />
                </div>

                <!-- View Toggle -->
                <div class="mt-4 flex justify-end">
                    <ViewToggle v-model="viewMode" />
                </div>
            </div>

            <!-- Loading State -->
            <div
                v-if="isLoading"
                class="flex items-center justify-center py-12"
            >
                <div class="text-center">
                    <div
                        class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-blue-500"
                    ></div>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ $t('common.loading') }}
                    </p>
                </div>
            </div>

            <!-- No Results State -->
            <div
                v-else-if="masters.data.length === 0"
                class="py-12 text-center"
            >
                <div class="mx-auto max-w-md">
                    <i class="fa fa-search mb-4 text-6xl text-gray-400"></i>
                    <h3
                        class="mb-2 text-lg font-medium text-gray-900 dark:text-white"
                    >
                        {{ $t('masters.no_results.title') }}
                    </h3>
                    <p class="mb-6 text-gray-600 dark:text-gray-400">
                        {{ $t('masters.no_results.description') }}
                    </p>

                    <!-- Popular Searches -->
                    <div class="mb-6">
                        <p
                            class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            {{ $t('masters.no_results.suggestions') }}
                        </p>
                        <div class="flex flex-wrap justify-center gap-2">
                            <button
                                v-for="suggestion in popularSearches"
                                :key="suggestion"
                                @click="applyPopularSearch(suggestion)"
                                class="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 transition-colors duration-200 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800"
                            >
                                {{ suggestion }}
                            </button>
                        </div>
                    </div>

                    <button
                        @click="clearAllFilters"
                        class="inline-flex items-center rounded-lg border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        {{ $t('masters.no_results.clear_filters') }}
                    </button>
                </div>
            </div>

            <!-- Masters Grid -->
            <div
                v-if="viewMode !== 'map' && masters.data.length > 0"
                class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <MasterCard
                    v-for="master in masters.data"
                    :key="master.id"
                    :master-id="master.id"
                    :image-url="master.main_photo"
                    :name="master.name"
                    :address="master.address"
                    :rating="getRating(master)"
                    :description="master.description"
                    :phone="master.phone"
                    :slug="master.slug"
                    :available="master.available"
                    :experience="5"
                    :reviews-count="master.reviews_count"
                    :specializations="[]"
                    :services="master.services"
                />
            </div>

            <!-- Masters Map -->
            <div
                v-if="viewMode === 'map' && masters.data.length > 0"
                class="mt-4"
            >
                <MastersMap :masters="masters.data" />
            </div>

            <!-- Pagination -->
            <Pagination
                v-if="viewMode !== 'map' && masters.data.length > 0"
                :current-page="masters.current_page || 1"
                :total-pages="masters.last_page || 1"
                :total-items="masters.total || 0"
                :per-page="perPage"
                @page-changed="handlePageChanged"
                @per-page-changed="handlePerPageChanged"
                class="mt-8"
            />
        </main>

        <footer class="border-t border-gray-200 bg-white py-6">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <p class="text-sm text-gray-500">
                    © {{ new Date().getFullYear() }} Carbeat
                </p>
                <nav class="flex items-center gap-4 text-sm">
                    <a
                        :href="route('privacy')"
                        class="text-gray-600 hover:text-gray-900"
                        >Політика конфіденційності</a
                    >
                    <span class="text-gray-300">·</span>
                    <a
                        :href="route('terms')"
                        class="text-gray-600 hover:text-gray-900"
                        >Умови користування</a
                    >
                    <span class="text-gray-300">·</span>
                    <a
                        :href="route('data_deletion')"
                        class="text-gray-600 hover:text-gray-900"
                        >Видалення даних</a
                    >
                </nav>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { onUnmounted } from 'vue';
import { io } from 'socket.io-client';
import { useI18n } from 'vue-i18n';

// Components
import MasterCard from '@/Components/MasterCard.vue';
import MastersFilters from '@/Components/MastersFilters.vue';
import MastersMap from '@/Components/MastersMap.vue';
import Pagination from '@/Components/Pagination.vue';
import ViewToggle from '@/Components/ViewToggle.vue';

// SEO Configuration
import {
    generateMetaTags,
    generateStructuredData,
    getSEOConfig,
} from '@/config/seo';

const { t, locale } = useI18n();
const page = usePage();

// SEO Configuration
const seoConfig = getSEOConfig();
const metaTags = computed(() => generateMetaTags(seoConfig, locale.value));

// State
interface Master {
    id: number;
    name: string;
    description: string;
    address: string;
    phone: string;
    reviews_count: number;
    rating: number;
    main_photo: string;
    distance: number;
    main_service_id: number;
    slug: string;
    services: Array<{ id: number; name: string }>;
    available: boolean;
    tariff_id: number;
    tariff: string;
    approved: boolean;
    latitude?: number;
    longitude?: number;
    reviews_avg_rating?: number; // Added for fallback rating
}

const masters = ref<{
    data: Master[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}>({
    data: [],
    current_page: 1,
    last_page: 1,
    total: 0,
    prev_page_url: null,
    next_page_url: null,
});

const isLoading = ref(false);
const perPage = ref(20);

// View mode: grid | list | map
const viewMode = ref<'grid' | 'list' | 'map'>('grid');

// Filters
const filters = ref({
    search: '',
    service_id: '', // Changed from specialization to service_id
    minRating: '',
    available: '',
    minPrice: null,
    maxPrice: null,
    selectedServices: [],
    city: '',
    distance: null,
    sortBy: 'rating',
});

const availableServices = ref([
    { id: 1, name: 'Технічний огляд' },
    { id: 2, name: 'Заміна масла' },
    { id: 3, name: 'Ремонт гальм' },
    { id: 4, name: 'Ремонт кондиціонера' },
    { id: 5, name: 'Ремонт АКПП' },
]);

const cities = ref(['Київ', 'Львів', 'Харків', 'Одеса', 'Дніпро']);

const popularSearches = ref([
    'Діагностика',
    'Ремонт двигуна',
    'Технічний огляд',
    'Ремонт гальм',
]);

// Computed properties
const startItem = computed(
    () => (masters.value.current_page - 1) * perPage.value + 1,
);
const endItem = computed(() =>
    Math.min(masters.value.current_page * perPage.value, masters.value.total),
);

const structuredData = computed(() =>
    generateStructuredData(seoConfig, masters.value.data, locale.value),
);

// Methods
const fetchMasters = async (url = '/web-api/masters') => {
    try {
        isLoading.value = true;

        const params = new URLSearchParams();
        const effectivePerPage =
            viewMode.value === 'map' ? 10000 : perPage.value;
        const effectivePage =
            viewMode.value === 'map' ? 1 : masters.value.current_page;

        params.append('page', effectivePage.toString());
        params.append('per_page', effectivePerPage.toString());
        params.append('sort_by', filters.value.sortBy);

        // Add filters (map to backend expected keys)
        Object.entries(filters.value).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                let paramKey = key;
                let paramValue: string | number = value as any;

                switch (key) {
                    case 'minRating':
                        paramKey = 'min_rating';
                        break;
                    case 'minPrice':
                        paramKey = 'min_price';
                        break;
                    case 'maxPrice':
                        paramKey = 'max_price';
                        break;
                    case 'selectedServices':
                        paramKey = 'selected_services';
                        break;
                    case 'available':
                        // ensure boolean-like strings "true"/"false" as used by backend
                        paramValue = String(value);
                        break;
                    case 'distance':
                        if (typeof value === 'string') {
                            const n = parseInt(value, 10);
                            if (!Number.isNaN(n)) paramValue = n;
                        }
                        break;
                }

                if (Array.isArray(value) && value.length > 0) {
                    value.forEach((v: any) =>
                        params.append(paramKey, v.toString()),
                    );
                } else {
                    params.append(paramKey, paramValue.toString());
                }
            }
        });

        const { data } = await axios.get(`${url}?${params.toString()}`);
        // Support both Web response shape ({ masters: {...} }) and API v1 shape ({ data: [...], meta: {...} })
        if (data && data.data.masters) {
            masters.value = data.data.masters;
        } else if (data && Array.isArray(data.data)) {
            masters.value = {
                data: data.data,
                current_page: data.meta?.current_page ?? 1,
                last_page: data.meta?.last_page ?? 1,
                total: data.meta?.total ?? data.data.length,
                prev_page_url: null,
                next_page_url: null,
            };
        }

        // Update URL without page reload
        updateUrl(params.toString());
    } catch (error) {
        console.error('Error fetching masters:', error);
        // Handle error - show notification, etc.
    } finally {
        isLoading.value = false;
    }
};

const updateUrl = (queryString: string) => {
    const newUrl = `/masters?${queryString}`;
    if (window.location.pathname + window.location.search !== newUrl) {
        window.history.pushState({}, '', newUrl);
    }
};

const handleFiltersChanged = (newFilters: any) => {
    filters.value = newFilters;
    masters.value.current_page = 1; // Reset to first page
    fetchMasters();
};

const handlePageChanged = (page: number) => {
    masters.value.current_page = page;
    fetchMasters();
};

const handlePerPageChanged = (newPerPage: number) => {
    perPage.value = newPerPage;
    masters.value.current_page = 1;
    fetchMasters();
};

const clearAllFilters = () => {
    filters.value = {
        search: '',
        service_id: '',
        minRating: '',
        available: '',
        minPrice: null,
        maxPrice: null,
        selectedServices: [],
        city: '',
        distance: null,
        sortBy: 'rating',
    };
    fetchMasters();
};

const applyPopularSearch = (search: string) => {
    filters.value.search = search;
    fetchMasters();
};

const getRating = (master: Master) => {
    const val = Number(master.reviews_avg_rating ?? 0);
    return Math.round(val * 10) / 10;
};

// Realtime: update availability via Socket.IO
let socket: any = null;
function applyAvailabilityUpdate(id: number, available: boolean) {
    try {
        const list = masters.value?.data || [];
        let changed = false;
        for (let i = 0; i < list.length; i++) {
            if (list[i].id === id) {
                if ((list[i] as any).available !== available) {
                    (list[i] as any).available = available;
                    changed = true;
                }
                break;
            }
        }
        if (changed) {
            // Trigger reactivity by replacing the array reference
            masters.value = { ...masters.value, data: [...list] };
        }
    } catch (_) {}
}

// Lifecycle
onMounted(() => {
    fetchMasters();

    // Parse URL parameters on page load
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('page')) {
        masters.value.current_page = parseInt(urlParams.get('page') || '1');
    }
    if (urlParams.has('per_page')) {
        perPage.value = parseInt(urlParams.get('per_page') || '20');
    }

    // Inject structured data script if enabled
    if (seoConfig.seo_settings.enable_structured_data) {
        injectStructuredData();
    }

    // Connect to Socket.IO for realtime availability updates
    try {
        const socketUrl = import.meta.env.VITE_SOCKET_IO_URL || '/';
        const socketPath = import.meta.env.VITE_SOCKET_IO_PATH || '/socket.io/';
        socket = io(socketUrl, { path: socketPath });
        socket.on('availability:update', (m: any) => {
            if (m && typeof m === 'object' && typeof m.id === 'number') {
                const available =
                    typeof m.available === 'boolean'
                        ? m.available
                        : !!(m.status ?? m.is_available);
                applyAvailabilityUpdate(m.id, available);
            }
        });
    } catch (_) {}
});

onUnmounted(() => {
    try { socket?.disconnect?.(); } catch (_) {}
});

// Inject structured data script into document head
const injectStructuredData = () => {
    // Remove existing structured data script if any
    const existingScript = document.querySelector(
        'script[data-structured-data]',
    );
    if (existingScript) {
        existingScript.remove();
    }

    // Create new script tag
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.setAttribute('data-structured-data', 'true');
    script.textContent = JSON.stringify(structuredData.value);

    // Append to document head
    document.head.appendChild(script);
};

// Watch for route changes
watch(
    () => page.url,
    () => {
        // Handle route changes if needed
    },
    { immediate: true },
);

// Re-fetch when view mode changes (e.g., map should fetch all)
watch(
    () => viewMode.value,
    () => {
        masters.value.current_page = 1;
        fetchMasters();
    },
);

// Watch for masters data changes to update structured data
watch(
    () => masters.value.data,
    () => {
        if (seoConfig.seo_settings.enable_structured_data) {
            injectStructuredData();
        }
    },
    { deep: true },
);
</script>
