<script setup lang="ts">
import { useAvailabilitySocket } from '@/composables/useAvailabilitySocket';
import { createCachedApi } from '@/composables/useCachedApi';
import { useGuestLang } from '@/composables/useGuestLang';
import { useGuestMap } from '@/composables/useGuestMap';
import { useStatusBeacon } from '@/composables/useStatusBeacon';
import { useStatusRequest } from '@/composables/useStatusRequest';
import { SERVICE_LABELS } from '@/shared/guest-map-display-labels';
import {
    buildOpeningHoursSpecification,
    formatWorkingHours,
    getWorkStatus,
    type WorkingHoursData,
} from '@/shared/workingHours';
import type {
    Flavor,
    MasterDetails,
    MasterService,
    SeoContentPayload,
    SeoPayload,
} from '@/types/guest-map';
import { Head } from '@inertiajs/vue3';
import type { LatLngBounds } from 'leaflet';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import GuestMapLightbox from './GuestMapLightbox.vue';
import GuestMapMasterDetail from './GuestMapMasterDetail.vue';
import GuestMapNearbyStrip from './GuestMapNearbyStrip.vue';
import GuestMapSeoSection from './GuestMapSeoSection.vue';
import GuestMapStatusBeacon from './GuestMapStatusBeacon.vue';
import GuestMapTopPanel from './GuestMapTopPanel.vue';

const props = defineProps<{
    apiBase: string;
    flavor?: Flavor;
    mapPath?: string;
    profilePathPrefix?: string;
    initialMapView?: { center: [number, number]; zoom: number } | null;
    initialServiceId?: number | null;
    initialSelectedMaster?: MasterDetails | null;
    seo?: SeoPayload | null;
    seoContent?: SeoContentPayload | null;
}>();

const MASTERS_LIST_TTL = 60_000;
const MASTER_DETAILS_TTL = 5 * 60_000;
const LOADING_DELAY_MS = 220;
const MOBILE_SELECTION_OFFSET_Y = 160;

const mapEl = ref<HTMLElement | null>(null);
const selectedMaster = ref<MasterDetails | null>(
    props.initialSelectedMaster ?? null,
);
const selectedMasterId = ref<number | null>(
    props.initialSelectedMaster?.id ?? null,
);
const currentMasters = ref<MasterDetails[]>(
    props.initialSelectedMaster ? [{ ...props.initialSelectedMaster }] : [],
);
const mapBounds = ref<LatLngBounds | null>(null);
const visibleMasters = computed(() => {
    const bounds = mapBounds.value;
    const masters = bounds
        ? currentMasters.value.filter((master) =>
              bounds.contains([master.latitude, master.longitude]),
          )
        : currentMasters.value;

    return [...masters].sort((a, b) => {
        const reviewsDiff = (b.reviews_count ?? 0) - (a.reviews_count ?? 0);
        if (reviewsDiff !== 0) return reviewsDiff;
        return (b.rating ?? 0) - (a.rating ?? 0);
    });
});
const loading = ref(false);
const geoErrorMessage = ref<string | null>(null);
const searchQuery = ref('');
const selectedServiceId = ref<number | null>(props.initialServiceId ?? null);
const availableOnly = ref(false);
const lightboxImage = ref<string | null>(null);
const scheduleOpen = ref(false);
const isMobileViewport = ref(false);

const flavor = computed<Flavor>(() => props.flavor ?? 'carbeat');
const isFloxcity = computed(() => flavor.value === 'floxcity');
const brandName = computed(() => (isFloxcity.value ? 'Floxcity' : 'Carbeat'));
const baseMapPath = computed(() => props.mapPath ?? '/');
const seoContent = computed(() => props.seoContent ?? null);
const isMasterSeoContent = computed(() => seoContent.value?.type === 'master');
const hasVisibleSeoContent = computed(
    () => !!seoContent.value && !isMasterSeoContent.value,
);
const mobileAppUrl = computed<string>(() =>
    isFloxcity.value
        ? 'https://play.google.com/store/search?q=Floxcity&c=apps'
        : 'https://play.google.com/store/search?q=CarBeat&c=apps',
);

const themeVars = computed<Record<string, string>>(() =>
    isFloxcity.value
        ? {
              '--glass-tint': 'rgba(110, 231, 183, 0.08)',
              '--glass-accent-rgb': '16, 185, 129',
              '--panel-bg':
                  'linear-gradient(180deg, rgba(255, 255, 255, 0.78) 0%, rgba(236, 253, 245, 0.62) 100%)',
              '--panel-border': 'rgba(16, 185, 129, 0.18)',
              '--panel-text': 'rgba(15, 23, 42, 0.92)',
              '--panel-muted-text': 'rgba(15, 23, 42, 0.62)',
              '--surface-bg': 'rgba(255, 255, 255, 0.58)',
              '--surface-bg-hover': 'rgba(255, 255, 255, 0.72)',
              '--surface-border': 'rgba(16, 185, 129, 0.16)',
              '--surface-shadow': 'inset 0 1px 0 rgba(255, 255, 255, 0.55)',
              '--loading-pill-bg': 'rgba(255, 255, 255, 0.72)',
              '--dropdown-bg': 'rgba(255, 255, 255, 0.82)',
              '--brand-primary': '#10b981',
              '--brand-primary-rgb': '16, 185, 129',
              '--brand-primary-strong': '#059669',
              '--brand-success': '#047857',
              '--marker-fallback-gradient':
                  'radial-gradient(circle at 30% 20%, #34d399 0%, #10b981 45%, #052e2b 100%)',
              '--cluster-bg': 'rgba(16, 185, 129, 0.88)',
          }
        : {
              '--glass-tint': 'rgba(147, 197, 253, 0.07)',
              '--glass-accent-rgb': '37, 99, 235',
              '--panel-bg':
                  'linear-gradient(180deg, rgba(255, 255, 255, 0.8) 0%, rgba(239, 246, 255, 0.64) 100%)',
              '--panel-border': 'rgba(37, 99, 235, 0.16)',
              '--panel-text': 'rgba(15, 23, 42, 0.92)',
              '--panel-muted-text': 'rgba(15, 23, 42, 0.62)',
              '--surface-bg': 'rgba(255, 255, 255, 0.58)',
              '--surface-bg-hover': 'rgba(255, 255, 255, 0.72)',
              '--surface-border': 'rgba(37, 99, 235, 0.16)',
              '--surface-shadow': 'inset 0 1px 0 rgba(255, 255, 255, 0.55)',
              '--loading-pill-bg': 'rgba(255, 255, 255, 0.72)',
              '--dropdown-bg': 'rgba(255, 255, 255, 0.82)',
              '--brand-primary': '#2563eb',
              '--brand-primary-rgb': '37, 99, 235',
              '--brand-primary-strong': '#0369a1',
              '--brand-success': '#047857',
              '--marker-fallback-gradient':
                  'radial-gradient(circle at 30% 20%, #38bdf8 0%, #2563eb 45%, #1e293b 100%)',
              '--cluster-bg': 'rgba(37, 99, 235, 0.88)',
          },
);

const mapViewportClasses = computed(() =>
    hasVisibleSeoContent.value
        ? 'guest-map-root relative h-[100svh] w-full overflow-hidden bg-slate-100 md:h-[82vh]'
        : 'guest-map-root relative h-screen w-screen overflow-hidden bg-slate-100',
);

const cachedApi = createCachedApi({
    baseURL: props.apiBase,
    defaultTtlMs: MASTERS_LIST_TTL,
});

function photoUrl(path?: string | null): string | null {
    if (!path) return null;
    if (path.startsWith('http://') || path.startsWith('https://')) {
        if (
            typeof window !== 'undefined' &&
            window.location.protocol === 'https:'
        ) {
            return path.replace(/^http:\/\//i, 'https://');
        }
        return path;
    }
    return path.startsWith('/') ? path : `/${path}`;
}

function currentOrigin(): string | null {
    if (typeof window !== 'undefined') return window.location.origin;
    const canonical = props.seo?.canonical;
    if (!canonical) return null;
    try {
        return new URL(canonical).origin;
    } catch {
        return null;
    }
}

function buildMasterPath(slug?: string | null): string {
    const prefix = props.profilePathPrefix ?? '/sto';
    return slug ? `${prefix}/${encodeURIComponent(slug)}` : baseMapPath.value;
}

function buildCanonicalUrl(path: string): string {
    if (/^https?:\/\//i.test(path)) return path;
    const origin = currentOrigin();
    return origin ? new URL(path, origin).toString() : path;
}

function normalizeMetaText(value?: string | null): string {
    return (value ?? '').replace(/\s+/g, ' ').trim();
}

const { currentLang, t, setLanguage, initLanguage } = useGuestLang({
    onLanguageChanged: () => {
        cachedApi.invalidate((key) => key.startsWith('masters:'));
        void loadMasters();
    },
});

const { statusBeacon, showStatusBeacon, closeStatusBeacon } = useStatusBeacon();

const {
    isSendingStatusRequest,
    statusRequestMessage,
    statusCooldownUntil,
    canRequestMasterStatus,
    loadCooldown,
    saveCooldown,
    requestMasterStatus,
    getRequestedStatusMasterIds,
    rememberRequestedStatus,
    forgetRequestedStatus,
} = useStatusRequest({
    apiInstance: cachedApi.instance as {
        post: (
            url: string,
            data: unknown,
        ) => Promise<{ data: Record<string, unknown> }>;
    },
    getMasterId: () => selectedMaster.value?.id,
    t,
});

type Service = { id: number; name: string };
const services = ref<Service[]>([]);

const serviceOptions = computed(() =>
    [...services.value]
        .map((service) => ({
            ...service,
            label:
                SERVICE_LABELS[service.name]?.[currentLang.value] ??
                service.name,
        }))
        .sort((a, b) => a.label.localeCompare(b.label, currentLang.value)),
);

// The map list endpoint (`/api/masters`) only returns `main_service_id`, not
// the eager-loaded `services` relation — this resolves that id back to a
// display name for the nearby strip.
const serviceNameById = computed<Record<number, string>>(() =>
    Object.fromEntries(serviceOptions.value.map((s) => [s.id, s.label])),
);

const selectedMasterPhotos = computed<string[]>(() => {
    const master = selectedMaster.value;
    if (!master) return [];
    const main = master.main_photo ? [master.main_photo] : [];
    const extra = (master.photos ?? []).map((p) => p.url);
    return [...main, ...extra].filter(Boolean) as string[];
});

const primaryService = computed<MasterService | null>(() => {
    const list = selectedMaster.value?.services;
    if (!list?.length) return null;
    return list.find((s) => s.is_primary) ?? list[0];
});

const extraServices = computed<MasterService[]>(() => {
    const list = selectedMaster.value?.services;
    if (!list?.length) return [];
    const primary = primaryService.value;
    return primary ? list.filter((s) => s.id !== primary.id) : list;
});

const selectedMasterSchedule = computed(() =>
    formatWorkingHours(
        selectedMaster.value?.working_hours as Parameters<
            typeof formatWorkingHours
        >[0],
    ),
);

const selectedMasterWorkStatus = computed(() =>
    getWorkStatus(
        selectedMaster.value?.working_hours as Parameters<
            typeof getWorkStatus
        >[0],
    ),
);

const guestMap = useGuestMap({
    photoUrl,
    onMarkerClick: (master) => void openMaster(master.id),
    onMapMoved: () => void loadMasters(),
    onMapClick: () => {
        if (isMobileViewport.value && selectedMaster.value) closeDetails();
    },
});

let mastersRequestSeq = 0;
let loadingTimer: number | null = null;
let geoErrorTimer: number | null = null;

function buildMastersCacheKey(params: {
    lat: number;
    lng: number;
    zoom: number;
    serviceId: number | null;
    available: boolean;
    locale: string;
    search: string;
}): string {
    const zoomBucket = Math.round(params.zoom);
    const bucketSize = 0.005 * Math.pow(2, Math.max(0, 14 - zoomBucket));
    const lat = (Math.round(params.lat / bucketSize) * bucketSize).toFixed(4);
    const lng = (Math.round(params.lng / bucketSize) * bucketSize).toFixed(4);
    const service = params.serviceId ?? 0;
    const avail = params.available ? 1 : 0;
    const search = params.search.trim().toLowerCase();
    return `masters:${params.locale}:${zoomBucket}:${lat}:${lng}:${service}:${avail}:${search}`;
}

function showLoadingSoon(): void {
    if (loadingTimer !== null) return;
    loadingTimer = window.setTimeout(() => {
        loadingTimer = null;
        loading.value = true;
    }, LOADING_DELAY_MS);
}

function hideLoading(): void {
    if (loadingTimer !== null) {
        window.clearTimeout(loadingTimer);
        loadingTimer = null;
    }
    loading.value = false;
}

async function loadServices(): Promise<void> {
    try {
        const data = await cachedApi.getCached<{ data: Service[] }>(
            'services',
            '/services',
            {
                ttlMs: 30 * 60_000,
                group: 'services',
            },
        );
        services.value = data?.data ?? [];
    } catch (error) {
        if (cachedApi.isCancel(error)) return;
        services.value = [];
    }
}

async function loadMasters(): Promise<void> {
    const view = guestMap.getView();
    if (!view) return;

    mapBounds.value = view.bounds;

    const { lat, lng } = { lat: view.center.lat, lng: view.center.lng };
    const zoom = view.zoom;

    const search = searchQuery.value.trim();

    const cacheKey = buildMastersCacheKey({
        lat,
        lng,
        zoom,
        serviceId: selectedServiceId.value,
        available: availableOnly.value,
        locale: currentLang.value,
        search,
    });

    const seq = ++mastersRequestSeq;
    showLoadingSoon();

    const baseParams = {
        per_page: 1500,
        zoom: Math.round(zoom),
        lat,
        lng,
        min_lat: view.bounds.getSouth(),
        max_lat: view.bounds.getNorth(),
        min_lng: view.bounds.getWest(),
        max_lng: view.bounds.getEast(),
        fields: 'light',
        locale: currentLang.value,
        service_id: selectedServiceId.value || undefined,
        available: availableOnly.value ? 1 : undefined,
        name: search || undefined,
    };

    try {
        const firstPage = await cachedApi.getCached<{
            data: MasterDetails[];
            meta?: { last_page?: number };
        }>(cacheKey, '/masters', {
            params: { ...baseParams, page: 1 },
            ttlMs: MASTERS_LIST_TTL,
            group: 'masters',
        });

        if (seq !== mastersRequestSeq) return;

        let list = Array.isArray(firstPage?.data) ? firstPage.data : [];

        // The bbox can match more masters than fit on one page (e.g. a whole-country
        // view) — fetch the rest in parallel so the map always shows every match,
        // not just the first slice. Mirrors the Flutter map's own fetch-all-pages logic.
        const lastPage = firstPage?.meta?.last_page ?? 1;
        if (lastPage > 1) {
            const extraPages = await Promise.all(
                Array.from({ length: lastPage - 1 }, (_, i) => i + 2).map(
                    (page) =>
                        cachedApi.getCached<{ data: MasterDetails[] }>(
                            `${cacheKey}:page:${page}`,
                            '/masters',
                            {
                                params: { ...baseParams, page },
                                ttlMs: MASTERS_LIST_TTL,
                                group: `masters-page-${seq}-${page}`,
                            },
                        ),
                ),
            );
            if (seq !== mastersRequestSeq) return;
            for (const extra of extraPages) {
                if (Array.isArray(extra?.data)) list = list.concat(extra.data);
            }
        }

        currentMasters.value =
            selectedMaster.value &&
            !list.some((m) => m.id === selectedMaster.value?.id)
                ? [{ ...selectedMaster.value }, ...list]
                : list;
        guestMap.syncMasters(currentMasters.value);
        if (selectedMasterId.value !== null)
            guestMap.setSelected(selectedMasterId.value);
    } catch (error) {
        if (cachedApi.isCancel(error)) return;
    } finally {
        if (seq === mastersRequestSeq) hideLoading();
    }
}

async function openMaster(masterId: number): Promise<void> {
    selectedMasterId.value = masterId;
    scheduleOpen.value = false;
    const summary = currentMasters.value.find((m) => m.id === masterId);
    if (summary) selectedMaster.value = { ...summary };
    guestMap.setSelected(masterId, {
        reveal: true,
        offsetY: isMobileViewport.value ? MOBILE_SELECTION_OFFSET_Y : 0,
    });
    loadCooldown(masterId);
    statusRequestMessage.value = '';

    try {
        const data = await cachedApi.getCached<
            { data?: MasterDetails } & MasterDetails
        >(`master:${masterId}`, `/masters/${masterId}`, {
            ttlMs: MASTER_DETAILS_TTL,
            group: `master:${masterId}`,
        });

        if (selectedMasterId.value !== masterId) return;
        const details = (data?.data ?? data) as MasterDetails;
        selectedMaster.value = summary ? { ...summary, ...details } : details;
    } catch (error) {
        if (cachedApi.isCancel(error)) return;
    }
}

async function refreshSelectedMaster(masterId: number): Promise<void> {
    cachedApi.invalidate((key) => key === `master:${masterId}`);
    await openMaster(masterId);
}

const isSubmittingReview = ref(false);
const reviewSubmitError = ref('');

async function submitGuestReview(payload: {
    name: string;
    rating: number;
    review: string;
}): Promise<void> {
    const masterId = selectedMasterId.value;
    if (!masterId) return;
    isSubmittingReview.value = true;
    reviewSubmitError.value = '';
    try {
        await cachedApi.instance.post(`/masters/${masterId}/reviews`, payload);
        await refreshSelectedMaster(masterId);
    } catch {
        reviewSubmitError.value = t('reviewSubmitError');
    } finally {
        isSubmittingReview.value = false;
    }
}

const isSubmittingReply = ref(false);
const replySubmitError = ref('');

async function submitReviewReply(payload: {
    reviewId: number;
    name: string;
    review: string;
}): Promise<void> {
    const masterId = selectedMasterId.value;
    if (!masterId) return;
    isSubmittingReply.value = true;
    replySubmitError.value = '';
    try {
        await cachedApi.instance.post(
            `/masters/${masterId}/reviews/${payload.reviewId}/reply`,
            { name: payload.name, review: payload.review },
        );
        await refreshSelectedMaster(masterId);
    } catch {
        replySubmitError.value = t('replySubmitError');
    } finally {
        isSubmittingReply.value = false;
    }
}

function closeDetails(): void {
    selectedMaster.value = null;
    selectedMasterId.value = null;
    lightboxImage.value = null;
    statusRequestMessage.value = '';
    statusCooldownUntil.value = null;
    guestMap.setSelected(null);
}

function openLightbox(photo: string): void {
    const normalized = photoUrl(photo);
    if (normalized) lightboxImage.value = normalized;
}

function closeLightbox(): void {
    lightboxImage.value = null;
}

function showGeoError(): void {
    if (geoErrorTimer !== null) window.clearTimeout(geoErrorTimer);
    geoErrorMessage.value = t('geoError');
    geoErrorTimer = window.setTimeout(() => {
        geoErrorMessage.value = null;
        geoErrorTimer = null;
    }, 5000);
}

async function useMyLocation(): Promise<void> {
    if (!navigator.geolocation) {
        showGeoError();
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (position) => {
            geoErrorMessage.value = null;
            guestMap.setUserPosition([
                position.coords.latitude,
                position.coords.longitude,
            ]);
            guestMap.flyTo(
                [position.coords.latitude, position.coords.longitude],
                13,
            );
        },
        () => showGeoError(),
        { enableHighAccuracy: true, timeout: 5000 },
    );
}

function syncViewportMode(): void {
    isMobileViewport.value = window.matchMedia('(max-width: 767px)').matches;
}

function parseAvailability(value: unknown): boolean | null {
    if (typeof value === 'boolean') return value;
    if (typeof value === 'number') return value !== 0;
    if (typeof value === 'string') {
        const lower = value.toLowerCase();
        if (lower === '1' || lower === 'true') return true;
        if (lower === '0' || lower === 'false') return false;
    }
    return null;
}

function applyAvailabilityRealtime(payload: unknown): void {
    if (!payload || typeof payload !== 'object') return;
    const data = payload as Record<string, unknown>;
    const masterId = Number(data.master_id ?? data.id ?? data.master);
    if (!Number.isFinite(masterId)) return;

    const available = parseAvailability(
        data.available ?? data.status ?? data.is_available,
    );
    if (available === null) return;

    const master = currentMasters.value.find((m) => m.id === masterId);
    if (master) master.available = available;

    if (selectedMaster.value?.id === masterId) {
        selectedMaster.value = { ...selectedMaster.value, available };
    }

    if (available && getRequestedStatusMasterIds().includes(masterId)) {
        const masterName =
            currentMasters.value.find((m) => m.id === masterId)?.name ??
            selectedMaster.value?.name ??
            'Master';
        showStatusBeacon(masterId, masterName);
        forgetRequestedStatus(masterId);
    }

    cachedApi.invalidate((key) => key === `master:${masterId}`);
    guestMap.applyAvailability(masterId, available);
}

const { connect: connectSocket } = useAvailabilitySocket({
    onAvailabilityUpdate: applyAvailabilityRealtime,
});

function extractMasterSlug(pathname: string): string | null {
    const match = pathname.match(/^\/sto\/([^/]+)$/);
    return match ? decodeURIComponent(match[1]) : null;
}

function findMasterBySlug(slug: string): MasterDetails | null {
    if (selectedMaster.value?.slug === slug) return selectedMaster.value;
    return (
        currentMasters.value.find((m) => m.slug === slug) ??
        (props.initialSelectedMaster?.slug === slug
            ? props.initialSelectedMaster
            : null)
    );
}

async function syncSelectionFromLocation(): Promise<void> {
    if (typeof window === 'undefined') return;
    const slug = extractMasterSlug(window.location.pathname);
    if (!slug) {
        if (selectedMaster.value !== null || selectedMasterId.value !== null)
            closeDetails();
        return;
    }
    const matched = findMasterBySlug(slug);
    if (matched?.id) {
        if (selectedMasterId.value === matched.id) return;
        await openMaster(matched.id);
        return;
    }
    window.location.reload();
}

watch(
    () => selectedMaster.value?.slug ?? null,
    (slug, previousSlug) => {
        if (typeof window === 'undefined') return;
        const nextPath = slug ? buildMasterPath(slug) : baseMapPath.value;
        if (window.location.pathname === nextPath) return;
        if (!previousSlug && slug) {
            window.history.pushState({}, '', nextPath);
            return;
        }
        window.history.replaceState({}, '', nextPath);
    },
);

watch([selectedServiceId, availableOnly], () => {
    selectedMaster.value = null;
    selectedMasterId.value = null;
    statusRequestMessage.value = '';
    statusCooldownUntil.value = null;
    guestMap.setSelected(null);
    cachedApi.invalidate((key) => key.startsWith('masters:'));
    void loadMasters();
});

let searchDebounceTimer: number | null = null;
watch(searchQuery, () => {
    if (searchDebounceTimer !== null) window.clearTimeout(searchDebounceTimer);
    searchDebounceTimer = window.setTimeout(() => {
        searchDebounceTimer = null;
        void loadMasters();
    }, 400);
});

// SEO helpers

function buildGenericSeo(): SeoPayload {
    return {
        title: `${brandName.value} Car Service Map & STO Near You`,
        description: `Find nearby STO stations, auto repair shops and car service specialists on the ${brandName.value} map.`,
        canonical: buildCanonicalUrl(baseMapPath.value),
        robots: 'index, follow',
        ogImage: props.seo?.ogImage ?? '/og-image.svg',
        structuredData: {
            '@context': 'https://schema.org',
            '@type': 'WebPage',
            name: `${brandName.value} Car Service Map`,
            url: buildCanonicalUrl(baseMapPath.value),
        },
    };
}

function buildSelectedMasterSeo(master: MasterDetails): SeoPayload {
    const servicesText = (master.services ?? [])
        .map((s) => normalizeMetaText(s.name))
        .filter(Boolean)
        .slice(0, 3)
        .join(', ');
    const location = [master.city, master.address]
        .map((v) => normalizeMetaText(v))
        .filter(Boolean)
        .join(', ');
    const descriptionBody = [
        location,
        servicesText ? `Services: ${servicesText}.` : '',
        normalizeMetaText(master.description).slice(0, 120),
    ]
        .filter(Boolean)
        .join(' ');
    const description = (
        descriptionBody
            ? `${master.name} on ${brandName.value}. ${descriptionBody}`
            : `${master.name} on ${brandName.value}. View services, location and reviews on the map.`
    ).slice(0, 160);
    const canonical = buildCanonicalUrl(buildMasterPath(master.slug));
    const structuredData: Record<string, unknown> = {
        '@context': 'https://schema.org',
        '@type': 'AutoRepair',
        name: master.name,
        url: canonical,
        description,
        image:
            photoUrl(master.main_photo) ??
            props.seo?.ogImage ??
            '/og-image.svg',
        telephone: master.phone ?? undefined,
        geo: {
            '@type': 'GeoCoordinates',
            latitude: master.latitude,
            longitude: master.longitude,
        },
    };

    const addressParts: Record<string, unknown> = { '@type': 'PostalAddress' };
    if (normalizeMetaText(master.address))
        addressParts.streetAddress = normalizeMetaText(master.address);
    if (normalizeMetaText(master.city))
        addressParts.addressLocality = normalizeMetaText(master.city);
    if (Object.keys(addressParts).length > 1)
        structuredData.address = addressParts;

    if ((master.rating ?? 0) > 0 && (master.reviews_count ?? 0) > 0) {
        structuredData.aggregateRating = {
            '@type': 'AggregateRating',
            ratingValue: Number(master.rating ?? 0).toFixed(1),
            reviewCount: master.reviews_count,
        };
    }

    const openingHours = buildOpeningHoursSpecification(
        master.working_hours as WorkingHoursData | null | undefined,
    );
    if (openingHours) {
        structuredData.openingHoursSpecification = openingHours;
    }

    return {
        title: `${master.name} STO · ${brandName.value}`,
        description,
        canonical,
        robots: 'index, follow',
        ogImage:
            photoUrl(master.main_photo) ??
            props.seo?.ogImage ??
            '/og-image.svg',
        structuredData,
    };
}

const pageSeo = computed<SeoPayload>(() => {
    if (selectedMaster.value?.slug)
        return buildSelectedMasterSeo(selectedMaster.value);
    return props.seo ?? buildGenericSeo();
});

const semanticHeading = computed(() => {
    if (isMasterSeoContent.value && selectedMaster.value) {
        return `${selectedMaster.value.name} STO profile`;
    }
    return (
        seoContent.value?.title ??
        `${brandName.value} car service map and STO near you`
    );
});

const semanticSubheading = computed(() => {
    if (isMasterSeoContent.value && selectedMaster.value) {
        const city = normalizeMetaText(selectedMaster.value.city);
        return city
            ? `Services, reviews and location in ${city}`
            : 'Services, reviews and location';
    }
    return (
        seoContent.value?.sections?.[0]?.heading ??
        'Compare nearby car service stations, ratings and available STO profiles'
    );
});

const semanticIntro = computed(
    () => seoContent.value?.intro || pageSeo.value.description,
);

const structuredDataJson = computed(() =>
    pageSeo.value.structuredData
        ? JSON.stringify(pageSeo.value.structuredData)
        : '',
);

const clarityScript = computed(
    () =>
        `(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window, document, "clarity", "script", "${
            isFloxcity.value ? 'wntdc7yqgq' : 'wntbe61vi0'
        }");`,
);

onMounted(async () => {
    await nextTick();
    if (!mapEl.value) return;
    initLanguage();
    syncViewportMode();
    window.addEventListener('resize', syncViewportMode);

    const hasInitialCoords =
        typeof props.initialSelectedMaster?.latitude === 'number' &&
        Number.isFinite(props.initialSelectedMaster.latitude) &&
        typeof props.initialSelectedMaster?.longitude === 'number' &&
        Number.isFinite(props.initialSelectedMaster.longitude);

    const initialCenter = hasInitialCoords
        ? [
              props.initialSelectedMaster!.latitude,
              props.initialSelectedMaster!.longitude,
          ]
        : (props.initialMapView?.center ?? [50.4501, 30.5234]);

    const initialZoom = props.initialSelectedMaster
        ? 14
        : (props.initialMapView?.zoom ?? 11);

    guestMap.init(mapEl.value, {
        center: initialCenter as [number, number],
        zoom: initialZoom,
    });

    void loadServices();
    void loadMasters();

    if (props.initialSelectedMaster?.id) {
        guestMap.syncMasters([{ ...props.initialSelectedMaster }]);
        guestMap.setSelected(props.initialSelectedMaster.id, {
            reveal: true,
            offsetY: isMobileViewport.value ? MOBILE_SELECTION_OFFSET_Y : 0,
        });
    }

    connectSocket();
    window.addEventListener('popstate', syncSelectionFromLocation);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', syncViewportMode);
    cachedApi.abortAll();
    if (loadingTimer !== null) {
        window.clearTimeout(loadingTimer);
        loadingTimer = null;
    }
    if (searchDebounceTimer !== null) {
        window.clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
    }
    if (geoErrorTimer !== null) {
        window.clearTimeout(geoErrorTimer);
        geoErrorTimer = null;
    }
    window.removeEventListener('popstate', syncSelectionFromLocation);
});
</script>

<template>
    <Head>
        <title>{{ pageSeo.title }}</title>
        <meta name="description" :content="pageSeo.description" />
        <meta name="robots" :content="pageSeo.robots ?? 'index, follow'" />
        <link rel="canonical" :href="pageSeo.canonical" />
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="pageSeo.canonical" />
        <meta property="og:title" :content="pageSeo.title" />
        <meta property="og:description" :content="pageSeo.description" />
        <meta property="og:site_name" :content="brandName" />
        <meta
            v-if="pageSeo.ogImage"
            property="og:image"
            :content="pageSeo.ogImage"
        />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="pageSeo.title" />
        <meta name="twitter:description" :content="pageSeo.description" />
        <meta
            v-if="pageSeo.ogImage"
            name="twitter:image"
            :content="pageSeo.ogImage"
        />
        <component
            :is="'script'"
            type="text/javascript"
            v-text="clarityScript"
        />
        <component
            :is="'script'"
            v-if="structuredDataJson"
            type="application/ld+json"
            v-text="structuredDataJson"
        />
    </Head>

    <div class="guest-map-page min-h-screen bg-slate-100" :style="themeVars">
        <section class="sr-only" aria-label="Page summary">
            <h1>{{ semanticHeading }}</h1>
            <p>{{ semanticIntro }}</p>
            <h2>{{ semanticSubheading }}</h2>
            <p>{{ pageSeo.description }}</p>
        </section>

        <div :class="mapViewportClasses">
            <div ref="mapEl" class="h-full w-full" />

            <div class="pointer-events-none absolute inset-0 z-[500]">
                <!-- Top panel -->
                <div
                    class="pointer-events-auto absolute left-3 right-3 top-3 md:left-5 md:right-auto md:w-[460px]"
                    :style="{ top: 'max(0.75rem, env(safe-area-inset-top))' }"
                >
                    <GuestMapTopPanel
                        :flavor="flavor"
                        :current-lang="currentLang"
                        :services="serviceOptions"
                        :selected-service-id="selectedServiceId"
                        :available-only="availableOnly"
                        :search-query="searchQuery"
                        :brand-name="brandName"
                        :mobile-app-url="mobileAppUrl"
                        :geo-error-message="geoErrorMessage"
                        :t="t"
                        @update:selected-service-id="selectedServiceId = $event"
                        @update:available-only="availableOnly = $event"
                        @update:search-query="searchQuery = $event"
                        @set-language="setLanguage"
                        @use-my-location="useMyLocation"
                    />
                </div>

                <!-- Loading pill -->
                <div
                    v-if="loading"
                    class="lg-loading-pill pointer-events-auto absolute right-3 top-20 rounded-xl px-3 py-2 text-xs"
                >
                    {{ t('loading') }}
                </div>

                <!-- Status beacon -->
                <Transition name="status-beacon">
                    <div
                        v-if="statusBeacon"
                        class="pointer-events-auto absolute left-3 right-3 z-[700] md:left-auto md:right-5 md:w-[420px]"
                        :style="{
                            top: 'max(6rem, calc(env(safe-area-inset-top) + 5.25rem))',
                        }"
                    >
                        <GuestMapStatusBeacon
                            :beacon="statusBeacon"
                            :is-floxcity="isFloxcity"
                            @close="closeStatusBeacon"
                        />
                    </div>
                </Transition>

                <!-- Nearby strip -->
                <Transition name="nearby-panel">
                    <div
                        v-if="
                            (visibleMasters.length > 0 || loading) &&
                            !selectedMaster
                        "
                        class="pointer-events-auto absolute bottom-0 left-0 right-0"
                    >
                        <GuestMapNearbyStrip
                            :masters="visibleMasters"
                            :loading="loading"
                            :photo-url="photoUrl"
                            :service-name-by-id="serviceNameById"
                            @master-click="openMaster"
                        />
                    </div>
                </Transition>

                <!-- Master detail -->
                <div
                    v-if="selectedMaster"
                    class="pointer-events-auto absolute left-3 right-3 md:left-5 md:right-auto md:w-[460px]"
                    :style="{
                        bottom: 'max(0.75rem, env(safe-area-inset-bottom))',
                    }"
                >
                    <Transition name="master-card" mode="out-in">
                        <GuestMapMasterDetail
                            :key="selectedMaster.id"
                            :master="selectedMaster"
                            :photos="selectedMasterPhotos"
                            :primary-service="primaryService"
                            :extra-services="extraServices"
                            :work-status="selectedMasterWorkStatus"
                            :schedule="selectedMasterSchedule"
                            :schedule-open="scheduleOpen"
                            :current-lang="currentLang"
                            :is-floxcity="isFloxcity"
                            :is-master-seo-content="isMasterSeoContent"
                            :can-request-status="canRequestMasterStatus"
                            :is-sending-status-request="isSendingStatusRequest"
                            :status-request-message="statusRequestMessage"
                            :is-submitting-review="isSubmittingReview"
                            :review-submit-error="reviewSubmitError"
                            :is-submitting-reply="isSubmittingReply"
                            :reply-submit-error="replySubmitError"
                            :t="t"
                            :photo-url="photoUrl"
                            @close="closeDetails"
                            @photo-click="openLightbox"
                            @request-status="requestMasterStatus"
                            @update:schedule-open="scheduleOpen = $event"
                            @submit-review="submitGuestReview"
                            @submit-reply="submitReviewReply"
                        />
                    </Transition>
                </div>

                <!-- Lightbox -->
                <GuestMapLightbox
                    v-if="lightboxImage"
                    :src="lightboxImage"
                    :alt="
                        selectedMaster ? selectedMaster.name + ' фото' : 'Фото'
                    "
                    @close="closeLightbox"
                />
            </div>
        </div>

        <GuestMapSeoSection
            v-if="seoContent && !isMasterSeoContent"
            :seo-content="seoContent"
            :is-floxcity="isFloxcity"
            :build-master-path="buildMasterPath"
        />
    </div>
</template>

<style scoped>
.guest-map-root {
    min-height: 100dvh;
}

.lg-loading-pill {
    background: var(--loading-pill-bg);
    border: 1px solid var(--panel-border);
    color: var(--panel-text);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}

/* Transitions */
:global(.nearby-panel-enter-active),
:global(.nearby-panel-leave-active) {
    transition:
        opacity 0.18s ease,
        transform 0.18s ease;
}
:global(.nearby-panel-enter-from),
:global(.nearby-panel-leave-to) {
    opacity: 0;
    transform: translateY(100%);
}

:global(.master-card-enter-active),
:global(.master-card-leave-active) {
    transition:
        opacity 0.18s ease,
        transform 0.18s ease;
}
:global(.master-card-enter-from),
:global(.master-card-leave-to) {
    opacity: 0;
    transform: translateY(10px);
}

:global(.status-beacon-enter-active),
:global(.status-beacon-leave-active) {
    transition:
        opacity 0.18s ease,
        transform 0.18s ease;
}
:global(.status-beacon-enter-from),
:global(.status-beacon-leave-to) {
    opacity: 0;
    transform: translateY(-6px);
}

:global(.lightbox-fade-enter-active),
:global(.lightbox-fade-leave-active) {
    transition: opacity 0.18s ease;
}
:global(.lightbox-fade-enter-from),
:global(.lightbox-fade-leave-to) {
    opacity: 0;
}

/* Leaflet marker styles — pin shape matching Flutter mobile */
:global(.master-marker-wrapper) {
    background: transparent;
    border: none;
    will-change: transform;
}

:global(.master-marker) {
    position: relative;
    /* width/height driven by Leaflet iconSize; we fill 100% */
    width: 100%;
    height: 100%;
    transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
}

:global(.master-marker:not(.pin-active):hover) {
    transform: translateY(-2px) scale(1.08);
}

/* Photo layer (behind the SVG overlay) */
:global(.marker-bg) {
    position: absolute;
    border-radius: 50%;
    overflow: hidden;
    background: #fff;
    z-index: 0;
}

:global(.marker-avatar-img),
:global(.marker-avatar-fallback) {
    width: 100%;
    height: 100%;
}

:global(.marker-avatar-img) {
    object-fit: cover;
}

:global(.marker-avatar-fallback) {
    display: grid;
    place-items: center;
    background: var(--marker-fallback-gradient);
    color: #fff;
}

:global(.marker-avatar-icon) {
    width: 55%;
    height: 55%;
}

/* Pin SVG — evenodd hole reveals the photo underneath */
:global(.marker-pin-svg) {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    overflow: visible;
    filter: drop-shadow(0 3px 6px rgba(15, 23, 42, 0.22));
    z-index: 1;
    pointer-events: none;
}

:global(.master-marker.pin-active .marker-pin-svg) {
    filter: drop-shadow(0 6px 14px rgba(15, 23, 42, 0.28));
}

/* Pin fill colors — mirrors Flutter Styles */
:global(.pin-available .marker-pin-path) {
    fill: var(--brand-primary);
}

:global(.pin-unavailable .marker-pin-path) {
    fill: #9ca3af;
}

:global(.pin-active .marker-pin-path) {
    fill: #f97316;
}

/* Cluster markers */
:global(.cluster-marker) {
    background: transparent !important;
    border: none !important;
}

:global(.cluster-marker .cluster-inner) {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.3px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.45);
    background: radial-gradient(
        circle,
        var(--brand-primary) 40%,
        transparent 100%
    );
    transition: transform 0.14s ease;
}

:global(.cluster-marker.cluster-all-unavailable .cluster-inner) {
    background: radial-gradient(circle, #9ca3af 40%, transparent 100%);
}

:global(.cluster-marker.cluster-md .cluster-inner) {
    width: 60px;
    height: 60px;
    font-size: 15px;
}
:global(.cluster-marker.cluster-lg .cluster-inner) {
    width: 72px;
    height: 72px;
    font-size: 16px;
}
:global(.cluster-marker.cluster-xl .cluster-inner) {
    width: 88px;
    height: 88px;
    font-size: 17px;
}
:global(.cluster-marker.cluster-xxl .cluster-inner) {
    width: 104px;
    height: 104px;
    font-size: 18px;
}
:global(.cluster-marker .cluster-inner:hover) {
    transform: scale(1.08);
}
</style>
