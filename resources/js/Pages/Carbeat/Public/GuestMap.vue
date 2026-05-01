<script setup lang="ts">
import { createCachedApi } from '@/composables/useCachedApi';
import { useGuestMap, type Master } from '@/composables/useGuestMap';
import {
    SERVICE_LABELS,
    detectLanguageByRegion,
    getUiText,
    type Lang,
    type UiTextKey,
} from '@/shared/guest-map-display-labels';
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue';
import { Head } from '@inertiajs/vue3';
import { io } from 'socket.io-client';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

type Service = { id: number; name: string };
type Flavor = 'carbeat' | 'floxcity';

interface MasterDetails extends Master {
    slug?: string | null;
    description?: string | null;
    address?: string | null;
    city?: string | null;
    rating?: number;
    reviews_count?: number;
    phone?: string | null;
    services?: Array<{ id: number; name: string; is_primary?: boolean }>;
    reviews?: Array<{
        id: number;
        rating: number;
        review?: string;
        user?: { name?: string };
    }>;
    photos?: Array<{ id: number; url: string }>;
    working_hours?: Array<Record<string, unknown>> | Record<string, unknown> | null;
    is_claimed?: boolean;
    claim_link?: string | null;
}

interface SeoPayload {
    title: string;
    description: string;
    canonical: string;
    robots?: string;
    ogImage?: string | null;
    structuredData?: unknown;
}

interface SeoLink {
    label: string;
    href: string;
    active?: boolean;
}

interface SeoStat {
    label: string;
    value: string;
}

interface SeoFaq {
    q: string;
    a: string;
}

interface SeoSection {
    heading: string;
    body: string;
}

interface SeoMasterCard {
    id: number;
    name: string;
    slug: string;
    address?: string | null;
    city?: string | null;
    rating?: number;
    reviews_count?: number;
    service_names?: string[];
}

interface SeoContentPayload {
    type: 'master' | 'city' | 'city_service';
    title: string;
    intro?: string;
    sections?: SeoSection[];
    breadcrumbs: SeoLink[];
    stats: SeoStat[];
    serviceLinks: SeoLink[];
    topMasters: SeoMasterCard[];
    relatedLinks: SeoLink[];
    faq: SeoFaq[];
}

const props = defineProps<{
    apiBase: string;
    flavor?: Flavor;
    mapPath?: string;
    initialMapView?: { center: [number, number]; zoom: number } | null;
    initialServiceId?: number | null;
    initialSelectedMaster?: MasterDetails | null;
    seo?: SeoPayload | null;
    seoContent?: SeoContentPayload | null;
}>();

const MASTERS_LIST_TTL = 60_000;
const MASTER_DETAILS_TTL = 5 * 60_000;
const LOADING_DELAY_MS = 220;

const mapEl = ref<HTMLElement | null>(null);
const services = ref<Service[]>([]);
const selectedServiceId = ref<number | null>(props.initialServiceId ?? null);
const availableOnly = ref(false);
const selectedMaster = ref<MasterDetails | null>(props.initialSelectedMaster ?? null);
const selectedMasterId = ref<number | null>(props.initialSelectedMaster?.id ?? null);
const currentMasters = ref<MasterDetails[]>(
    props.initialSelectedMaster ? [{ ...props.initialSelectedMaster }] : [],
);
const loading = ref(false);
const isSendingStatusRequest = ref(false);
const statusRequestMessage = ref('');
const statusCooldownUntil = ref<Date | null>(null);
const currentLang = ref<Lang>('en');
const statusBeacon = ref<{ masterId: number; masterName: string } | null>(null);
const isMobileViewport = ref(false);
let statusBeaconTimer: number | null = null;

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

const cachedApi = createCachedApi({
    baseURL: props.apiBase,
    defaultTtlMs: MASTERS_LIST_TTL,
});

const guestMap = useGuestMap({
    photoUrl,
    onMarkerClick: (master) => void openMaster(master.id),
    onMapMoved: () => void loadMasters(),
    onMapClick: () => {
        if (isMobileViewport.value && selectedMaster.value) {
            closeDetails();
        }
    },
});

let socket: ReturnType<typeof io> | null = null;
let mastersRequestSeq = 0;
let loadingTimer: number | null = null;
const MOBILE_SELECTION_OFFSET_Y = 160;

const selectedMasterPhotos = computed<string[]>(() => {
    const master = selectedMaster.value;
    if (!master) return [];
    const main = master.main_photo ? [master.main_photo] : [];
    const extra = (master.photos ?? []).map((p) => p.url);
    return [...main, ...extra].filter(Boolean) as string[];
});

const hasAnyMasterImage = computed(() => selectedMasterPhotos.value.length > 0);
const lightboxImage = ref<string | null>(null);

const primaryService = computed(() => {
    const list = selectedMaster.value?.services;
    if (!list?.length) return null;
    return list.find((service) => service.is_primary) ?? list[0];
});

const extraServices = computed(() => {
    const list = selectedMaster.value?.services;
    if (!list?.length) return [];
    const primary = primaryService.value;
    if (!primary) return list;
    return list.filter((service) => service.id !== primary.id);
});

const serviceOptions = computed(() =>
    [...services.value]
        .map((service) => ({
            ...service,
            label:
                SERVICE_LABELS[service.name]?.[currentLang.value] ??
                service.name,
        }))
        .sort((left, right) =>
            left.label.localeCompare(right.label, currentLang.value),
        ),
);

const selectedServiceLabel = computed(() => {
    if (selectedServiceId.value === null) return t('allServices');
    const found = serviceOptions.value.find(
        (service) => service.id === selectedServiceId.value,
    );
    return found?.label ?? t('allServices');
});

const canRequestMasterStatus = computed(() => {
    if (!selectedMaster.value) return false;
    if (!statusCooldownUntil.value) return true;
    return statusCooldownUntil.value.getTime() <= Date.now();
});

function requestedStatusStorageKey(): string {
    return `guest_status_requested_masters_${getGuestDeviceId()}`;
}

function getRequestedStatusMasterIds(): number[] {
    try {
        const raw = localStorage.getItem(requestedStatusStorageKey());
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) return [];
        return parsed
            .map((value) => Number(value))
            .filter((value) => Number.isFinite(value) && value > 0);
    } catch {
        return [];
    }
}

function setRequestedStatusMasterIds(ids: number[]): void {
    localStorage.setItem(requestedStatusStorageKey(), JSON.stringify(ids));
}

function rememberRequestedStatus(masterId: number): void {
    const ids = getRequestedStatusMasterIds();
    if (ids.includes(masterId)) return;
    ids.push(masterId);
    setRequestedStatusMasterIds(ids);
}

function forgetRequestedStatus(masterId: number): void {
    const next = getRequestedStatusMasterIds().filter((id) => id !== masterId);
    setRequestedStatusMasterIds(next);
}

function showStatusBeacon(masterId: number): void {
    if (statusBeaconTimer !== null) {
        window.clearTimeout(statusBeaconTimer);
        statusBeaconTimer = null;
    }
    const masterName =
        currentMasters.value.find((item) => item.id === masterId)?.name ??
        selectedMaster.value?.name ??
        'Master';
    statusBeacon.value = { masterId, masterName };
    statusBeaconTimer = window.setTimeout(() => {
        statusBeacon.value = null;
        statusBeaconTimer = null;
    }, 5000);
}

function closeStatusBeacon(): void {
    if (statusBeaconTimer !== null) {
        window.clearTimeout(statusBeaconTimer);
        statusBeaconTimer = null;
    }
    statusBeacon.value = null;
}

function t(key: UiTextKey): string {
    return getUiText(currentLang.value, key);
}

function setLanguage(lang: Lang): void {
    currentLang.value = lang;
    localStorage.setItem('site_lang', lang);
    cachedApi.invalidate((key) => key.startsWith('masters:'));
    void loadMasters();
}

function initLanguage(): void {
    const saved = localStorage.getItem('site_lang');
    if (saved === 'en' || saved === 'uk' || saved === 'de') {
        currentLang.value = saved;
        return;
    }
    currentLang.value = detectLanguageByRegion();
}

function syncViewportMode(): void {
    isMobileViewport.value = window.matchMedia('(max-width: 767px)').matches;
}

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
    return slug ? `/sto/${encodeURIComponent(slug)}` : baseMapPath.value;
}

function buildCanonicalUrl(path: string): string {
    if (/^https?:\/\//i.test(path)) return path;

    const origin = currentOrigin();
    return origin ? new URL(path, origin).toString() : path;
}

function normalizeMetaText(value?: string | null): string {
    return (value ?? '').replace(/\s+/g, ' ').trim();
}

function buildGenericSeo(): SeoPayload {
    return {
        title: `${brandName.value} Map`,
        description: `Find nearby car service stations and auto repair specialists on the ${brandName.value} map.`,
        canonical: buildCanonicalUrl(baseMapPath.value),
        robots: 'index, follow',
        ogImage: props.seo?.ogImage ?? '/og-image.jpg',
        structuredData: {
            '@context': 'https://schema.org',
            '@type': 'WebPage',
            name: `${brandName.value} Map`,
            url: buildCanonicalUrl(baseMapPath.value),
            description: `Interactive map of nearby car service stations and auto repair specialists on ${brandName.value}.`,
        },
    };
}

function buildSelectedMasterSeo(master: MasterDetails): SeoPayload {
    const servicesText = (master.services ?? [])
        .map((service) => normalizeMetaText(service.name))
        .filter(Boolean)
        .slice(0, 3)
        .join(', ');
    const location = [master.city, master.address]
        .map((value) => normalizeMetaText(value))
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
        image: photoUrl(master.main_photo) ?? props.seo?.ogImage ?? '/og-image.jpg',
        telephone: master.phone ?? undefined,
        geo: {
            '@type': 'GeoCoordinates',
            latitude: master.latitude,
            longitude: master.longitude,
        },
    };

    const addressParts: Record<string, unknown> = {
        '@type': 'PostalAddress',
    };
    if (normalizeMetaText(master.address)) {
        addressParts.streetAddress = normalizeMetaText(master.address);
    }
    if (normalizeMetaText(master.city)) {
        addressParts.addressLocality = normalizeMetaText(master.city);
    }
    if (Object.keys(addressParts).length > 1) {
        structuredData.address = addressParts;
    }

    if ((master.rating ?? 0) > 0 && (master.reviews_count ?? 0) > 0) {
        structuredData.aggregateRating = {
            '@type': 'AggregateRating',
            ratingValue: Number(master.rating ?? 0).toFixed(1),
            reviewCount: master.reviews_count,
        };
    }

    return {
        title: `${master.name} · ${brandName.value}`,
        description,
        canonical,
        robots: 'index, follow',
        ogImage: photoUrl(master.main_photo) ?? props.seo?.ogImage ?? '/og-image.jpg',
        structuredData,
    };
}

const pageSeo = computed<SeoPayload>(() => {
    if (selectedMaster.value?.slug) return buildSelectedMasterSeo(selectedMaster.value);
    return props.seo ?? buildGenericSeo();
});

const structuredDataJson = computed(() =>
    pageSeo.value.structuredData
        ? JSON.stringify(pageSeo.value.structuredData)
        : '',
);

const mapViewportClasses = computed(() =>
    hasVisibleSeoContent.value
        ? 'guest-map-root relative h-[100svh] w-full overflow-hidden bg-slate-100 md:h-[82vh]'
        : 'guest-map-root relative h-screen w-screen overflow-hidden bg-slate-100',
);

function cooldownStorageKey(masterId: number): string {
    return `master_status_request_cooldown_${masterId}`;
}

function getGuestDeviceId(): string {
    const storageKey = 'guest_status_request_device_id';
    const existing = localStorage.getItem(storageKey);
    if (existing) return existing;

    let generated: string;
    try {
        if (
            typeof crypto !== 'undefined' &&
            typeof crypto.randomUUID === 'function'
        ) {
            generated = crypto.randomUUID();
        } else {
            generated = `guest-${Date.now()}-${Math.random().toString(16).slice(2)}`;
        }
    } catch {
        generated = `guest-${Date.now()}-${Math.random().toString(16).slice(2)}`;
    }

    localStorage.setItem(storageKey, generated);
    return generated;
}

function loadCooldown(masterId: number): void {
    const raw = localStorage.getItem(cooldownStorageKey(masterId));
    if (!raw) {
        statusCooldownUntil.value = null;
        return;
    }
    const parsed = new Date(raw);
    statusCooldownUntil.value = Number.isNaN(parsed.getTime()) ? null : parsed;
}

function saveCooldown(
    masterId: number,
    rawDate: string | null | undefined,
): void {
    if (!rawDate) return;
    localStorage.setItem(cooldownStorageKey(masterId), rawDate);
    loadCooldown(masterId);
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

/**
 * Build a stable cache key for the masters request. Coordinates are bucketed
 * so small jitter in pan/zoom does not produce a fresh key (and a fresh
 * network call) every time. The bucket size grows at lower zooms.
 */
function buildMastersCacheKey(params: {
    lat: number;
    lng: number;
    zoom: number;
    serviceId: number | null;
    available: boolean;
    locale: Lang;
}): string {
    const zoomBucket = Math.round(params.zoom);
    const bucketSize = 0.005 * Math.pow(2, Math.max(0, 14 - zoomBucket));
    const lat = (Math.round(params.lat / bucketSize) * bucketSize).toFixed(4);
    const lng = (Math.round(params.lng / bucketSize) * bucketSize).toFixed(4);
    const service = params.serviceId ?? 0;
    const avail = params.available ? 1 : 0;
    return `masters:${params.locale}:${zoomBucket}:${lat}:${lng}:${service}:${avail}`;
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

    const lat = view.center.lat;
    const lng = view.center.lng;
    const zoom = view.zoom;

    const cacheKey = buildMastersCacheKey({
        lat,
        lng,
        zoom,
        serviceId: selectedServiceId.value,
        available: availableOnly.value,
        locale: currentLang.value,
    });

    const seq = ++mastersRequestSeq;
    showLoadingSoon();

    try {
        const data = await cachedApi.getCached<{ data: MasterDetails[] }>(
            cacheKey,
            '/masters',
            {
                params: {
                    per_page: 200,
                    page: 1,
                    zoom: Math.round(zoom),
                    lat,
                    lng,
                    locale: currentLang.value,
                    service_id: selectedServiceId.value || undefined,
                    available: availableOnly.value ? 1 : undefined,
                },
                ttlMs: MASTERS_LIST_TTL,
                group: 'masters',
            },
        );

        if (seq !== mastersRequestSeq) return;

        const list = Array.isArray(data?.data) ? data.data : [];
        const mergedList =
            selectedMaster.value &&
            !list.some((master) => master.id === selectedMaster.value?.id)
                ? [{ ...selectedMaster.value }, ...list]
                : list;
        currentMasters.value = mergedList;
        guestMap.syncMasters(mergedList);
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
    const summary = currentMasters.value.find(
        (master) => master.id === masterId,
    );
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

async function useMyLocation(): Promise<void> {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            guestMap.setUserPosition([lat, lng]);
            guestMap.flyTo([lat, lng], 13);
        },
        () => {
            // Silently ignore geolocation rejection.
        },
        { enableHighAccuracy: true, timeout: 5000 },
    );
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
    if (!normalized) return;
    lightboxImage.value = normalized;
}

function closeLightbox(): void {
    lightboxImage.value = null;
}

function onWindowKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape' && lightboxImage.value) {
        closeLightbox();
    }
}

function claimProfile(): void {
    if (!selectedMaster.value?.claim_link) return;
    window.location.href = selectedMaster.value.claim_link;
}

async function requestMasterStatus(): Promise<void> {
    if (
        !selectedMaster.value ||
        !canRequestMasterStatus.value ||
        isSendingStatusRequest.value
    ) {
        return;
    }

    isSendingStatusRequest.value = true;
    statusRequestMessage.value = '';

    try {
        const response = await cachedApi.instance.post('/request-status', {
            master_id: selectedMaster.value.id,
            guest_device_id: getGuestDeviceId(),
            guest_platform: 'web',
        });
        rememberRequestedStatus(selectedMaster.value.id);
        saveCooldown(
            selectedMaster.value.id,
            response.data?.cooldown_expires_at,
        );
        statusRequestMessage.value = t('statusSent');
    } catch (error: unknown) {
        const responseData =
            (error as { response?: { data?: Record<string, unknown> } })
                ?.response?.data ?? {};
        const cooldown = responseData.cooldown_expires_at;
        if (typeof cooldown === 'string') {
            saveCooldown(selectedMaster.value.id, cooldown);
        }
        const message = responseData.message;
        statusRequestMessage.value =
            typeof message === 'string' ? message : t('statusError');
        if (typeof cooldown === 'string' || typeof message === 'string') {
            rememberRequestedStatus(selectedMaster.value.id);
        }
    } finally {
        isSendingStatusRequest.value = false;
    }
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

    const master = currentMasters.value.find((item) => item.id === masterId);
    if (master) master.available = available;

    if (selectedMaster.value?.id === masterId) {
        selectedMaster.value = { ...selectedMaster.value, available };
    }

    if (available && getRequestedStatusMasterIds().includes(masterId)) {
        showStatusBeacon(masterId);
        forgetRequestedStatus(masterId);
    }

    cachedApi.invalidate((key) => key === `master:${masterId}`);
    guestMap.applyAvailability(masterId, available);
}

function extractMasterSlug(pathname: string): string | null {
    const match = pathname.match(/^\/sto\/([^/]+)$/);
    return match ? decodeURIComponent(match[1]) : null;
}

function findMasterBySlug(slug: string): MasterDetails | null {
    if (selectedMaster.value?.slug === slug) return selectedMaster.value;

    return (
        currentMasters.value.find((master) => master.slug === slug) ??
        (props.initialSelectedMaster?.slug === slug
            ? props.initialSelectedMaster
            : null)
    );
}

async function syncSelectionFromLocation(): Promise<void> {
    if (typeof window === 'undefined') return;

    const slug = extractMasterSlug(window.location.pathname);
    if (!slug) {
        if (selectedMaster.value !== null || selectedMasterId.value !== null) {
            closeDetails();
        }
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

onMounted(async () => {
    await nextTick();
    if (!mapEl.value) return;
    initLanguage();
    syncViewportMode();
    window.addEventListener('keydown', onWindowKeydown);
    window.addEventListener('resize', syncViewportMode);

    const hasInitialCoordinates =
        typeof props.initialSelectedMaster?.latitude === 'number' &&
        Number.isFinite(props.initialSelectedMaster.latitude) &&
        typeof props.initialSelectedMaster?.longitude === 'number' &&
        Number.isFinite(props.initialSelectedMaster.longitude);
    const initialCenter = hasInitialCoordinates
        ? [props.initialSelectedMaster!.latitude, props.initialSelectedMaster!.longitude]
        : (props.initialMapView?.center ?? [50.4501, 30.5234]);
    const initialZoom = props.initialSelectedMaster
        ? 14
        : (props.initialMapView?.zoom ?? 11);

    guestMap.init(mapEl.value, {
        center: initialCenter as [number, number],
        zoom: initialZoom,
    });

    await Promise.all([loadServices(), loadMasters()]);

    if (props.initialSelectedMaster?.id) {
        guestMap.setSelected(props.initialSelectedMaster.id, {
            reveal: true,
            offsetY: isMobileViewport.value ? MOBILE_SELECTION_OFFSET_Y : 0,
        });
    }

    const socketUrl =
        import.meta.env.VITE_SOCKET_IO_URL || window.location.origin;
    const socketPath = import.meta.env.VITE_SOCKET_IO_PATH || '/socket.io/';
    socket = io(socketUrl, {
        transports: ['websocket', 'polling'],
        path: socketPath,
    });

    socket.on('availability:update', applyAvailabilityRealtime);
    window.addEventListener('popstate', syncSelectionFromLocation);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onWindowKeydown);
    window.removeEventListener('resize', syncViewportMode);
    if (socket) {
        socket.off('availability:update', applyAvailabilityRealtime);
        socket.disconnect();
        socket = null;
    }
    cachedApi.abortAll();
    closeStatusBeacon();
    if (loadingTimer !== null) {
        window.clearTimeout(loadingTimer);
        loadingTimer = null;
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
        <meta v-if="pageSeo.ogImage" property="og:image" :content="pageSeo.ogImage" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="pageSeo.title" />
        <meta name="twitter:description" :content="pageSeo.description" />
        <meta v-if="pageSeo.ogImage" name="twitter:image" :content="pageSeo.ogImage" />
        <component
            :is="'script'"
            v-if="structuredDataJson"
            type="application/ld+json"
            v-text="structuredDataJson"
        />
    </Head>

    <div class="guest-map-page min-h-screen bg-slate-100" :style="themeVars">
        <div :class="mapViewportClasses">
            <div ref="mapEl" class="h-full w-full" />

            <div class="pointer-events-none absolute inset-0 z-[500]">
                <div
                    class="pointer-events-auto absolute left-3 right-3 top-3 md:left-5 md:right-auto md:w-[460px]"
                    :style="{ top: 'max(0.75rem, env(safe-area-inset-top))' }"
                >
                    <div class="glass-panel rounded-2xl p-3">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                            :class="
                                isFloxcity
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-sky-100 text-sky-700'
                            "
                        >
                            {{ isFloxcity ? 'Floxcity' : 'Carbeat' }}
                        </span>
                        <a
                            :href="mobileAppUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="app-download-cta rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white"
                        >
                            {{ t('appDownloadCta') }}
                        </a>
                        <div
                            class="inline-flex items-center gap-1 rounded-lg p-0.5"
                            :class="isFloxcity ? 'bg-emerald-50' : 'bg-sky-50'"
                        >
                            <button
                                type="button"
                                class="rounded-md px-2 py-1 text-xs font-semibold"
                                :class="
                                    currentLang === 'en'
                                        ? 'bg-white text-slate-900'
                                        : 'bg-white/70 text-slate-600'
                                "
                                @click="setLanguage('en')"
                            >
                                EN
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-2 py-1 text-xs font-semibold"
                                :class="
                                    currentLang === 'uk'
                                        ? 'bg-white text-slate-900'
                                        : 'bg-white/70 text-slate-600'
                                "
                                @click="setLanguage('uk')"
                            >
                                UK
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-2 py-1 text-xs font-semibold"
                                :class="
                                    currentLang === 'de'
                                        ? 'bg-white text-slate-900'
                                        : 'bg-white/70 text-slate-600'
                                "
                                @click="setLanguage('de')"
                            >
                                DE
                            </button>
                        </div>
                    </div>
                    <div
                        class="grid grid-cols-[1fr_auto] gap-2 md:grid-cols-[1fr_auto_auto]"
                    >
                        <Listbox
                            class="col-span-2 md:col-span-1"
                            :model-value="selectedServiceId"
                            @update:model-value="
                                (value: number | null) =>
                                    (selectedServiceId = value)
                            "
                        >
                            <div class="relative">
                                <ListboxButton
                                    class="glass-input flex min-h-10 w-full items-center justify-between gap-2 rounded-xl px-3 py-2 text-left text-sm font-medium"
                                >
                                    <span class="truncate">{{
                                        selectedServiceLabel
                                    }}</span>
                                    <svg
                                        class="h-3 w-3 shrink-0 opacity-80 transition-transform duration-150"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <polyline points="5 8 10 13 15 8" />
                                    </svg>
                                </ListboxButton>
                                <Transition
                                    enter-active-class="transition duration-150 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1 scale-[0.98]"
                                    enter-to-class="opacity-100 translate-y-0 scale-100"
                                    leave-active-class="transition duration-100 ease-in"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <ListboxOptions
                                        class="listbox-options absolute left-0 right-0 top-full z-50 mt-2 max-h-72 overflow-y-auto rounded-xl py-1 text-sm focus:outline-none"
                                    >
                                        <ListboxOption
                                            v-slot="{ active, selected }"
                                            :value="null"
                                            as="template"
                                        >
                                            <li
                                                class="listbox-option"
                                                :class="{
                                                    'is-active': active,
                                                    'is-selected': selected,
                                                }"
                                            >
                                                <span class="truncate">{{
                                                    t('allServices')
                                                }}</span>
                                                <svg
                                                    v-if="selected"
                                                    class="h-3.5 w-3.5 shrink-0"
                                                    viewBox="0 0 20 20"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2.5"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <polyline
                                                        points="5 10 9 14 15 6"
                                                    />
                                                </svg>
                                            </li>
                                        </ListboxOption>
                                        <ListboxOption
                                            v-for="service in serviceOptions"
                                            :key="service.id"
                                            v-slot="{ active, selected }"
                                            :value="service.id"
                                            as="template"
                                        >
                                            <li
                                                class="listbox-option"
                                                :class="{
                                                    'is-active': active,
                                                    'is-selected': selected,
                                                }"
                                            >
                                                <span class="truncate">{{
                                                    service.label
                                                }}</span>
                                                <svg
                                                    v-if="selected"
                                                    class="h-3.5 w-3.5 shrink-0"
                                                    viewBox="0 0 20 20"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2.5"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <polyline
                                                        points="5 10 9 14 15 6"
                                                    />
                                                </svg>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </Transition>
                            </div>
                        </Listbox>

                        <label
                            class="glass-chip inline-flex min-h-10 items-center gap-2 rounded-xl px-3 py-2 text-sm"
                        >
                            <input
                                v-model="availableOnly"
                                type="checkbox"
                                class="h-4 w-4 rounded border-white/40"
                            />
                            {{ t('availableOnly') }}
                        </label>

                        <button
                            type="button"
                            class="glass-button min-h-10 rounded-xl px-3 py-2 text-sm"
                            @click="useMyLocation"
                        >
                            {{ t('myGeo') }}
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="loading"
                class="lg-loading-pill pointer-events-auto absolute right-3 top-20 rounded-xl px-3 py-2 text-xs"
            >
                {{ t('loading') }}
            </div>

            <Transition name="status-beacon">
                <div
                    v-if="statusBeacon"
                    class="pointer-events-auto absolute left-3 right-3 top-24 z-[700] md:left-auto md:right-5 md:w-[420px]"
                    :style="{
                        top: 'max(6rem, calc(env(safe-area-inset-top) + 5.25rem))',
                    }"
                >
                    <div class="status-beacon rounded-2xl p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div
                                    class="status-beacon-dot mt-1 h-2.5 w-2.5 rounded-full"
                                />
                                <div>
                                    <div
                                        class="text-xs font-semibold uppercase tracking-wide"
                                        :class="isFloxcity ? 'text-emerald-700' : 'text-sky-700'"
                                    >
                                        Status update
                                    </div>
                                    <div
                                        class="mt-0.5 text-sm"
                                        :class="'text-slate-800'"
                                    >
                                        {{ statusBeacon.masterName }} is
                                        available now.
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="rounded-md px-2 py-1 text-xs font-semibold"
                                :class="
                                    isFloxcity
                                        ? 'bg-emerald-50 text-slate-800 hover:bg-emerald-100'
                                        : 'bg-sky-50 text-slate-800 hover:bg-sky-100'
                                "
                                @click="closeStatusBeacon"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <Transition name="master-card" mode="out-in">
                <div
                    v-if="selectedMaster"
                    :key="selectedMaster.id"
                    class="pointer-events-auto absolute bottom-3 left-3 right-3 md:bottom-5 md:left-5 md:right-auto md:w-[460px]"
                    :style="{
                        bottom: 'max(0.75rem, env(safe-area-inset-bottom))',
                    }"
                >
                    <div
                        class="glass-panel max-h-[72vh] overflow-auto rounded-2xl p-4 md:max-h-[78vh]"
                    >
                        <div
                            class="mb-2 flex items-start justify-between gap-3"
                        >
                            <div>
                                <h2
                                    class="text-lg font-semibold"
                                    :class="'text-slate-900'"
                                >
                                    {{ selectedMaster.name }}
                                </h2>
                                <p
                                    class="text-sm"
                                    :class="'text-slate-600'"
                                >
                                    {{ selectedMaster.address }}
                                </p>
                                <div class="mt-1 flex items-center gap-2">
                                    <div class="flex items-center">
                                        <span
                                            v-for="index in 5"
                                            :key="index"
                                            class="text-sm"
                                            :class="
                                                index <=
                                                Math.round(
                                                    selectedMaster.rating ?? 0,
                                                )
                                                    ? 'text-yellow-500'
                                                    : 'text-slate-300'
                                            "
                                        >
                                            ★
                                        </span>
                                    </div>
                                    <span
                                        class="text-sm font-medium"
                                        :class="'text-slate-800'"
                                    >
                                        {{
                                            (
                                                selectedMaster.rating ?? 0
                                            ).toFixed(1)
                                        }}
                                    </span>
                                </div>
                            </div>
                            <button
                                class="lg-close rounded-lg px-2 py-1"
                                :class="'text-slate-800'"
                                @click="closeDetails"
                            >
                                ✕
                            </button>
                        </div>

                        <div
                            class="mb-3 grid grid-cols-2 gap-2 text-sm sm:flex sm:flex-wrap"
                        >
                            <a
                                v-if="selectedMaster.phone"
                                :href="`tel:${selectedMaster.phone}`"
                                class="lg-action-btn lg-action-call rounded-xl px-3 py-2 font-medium text-white"
                            >
                                {{ t('call') }}
                            </a>
                            <a
                                :href="`https://www.google.com/maps/search/?api=1&query=${selectedMaster.latitude},${selectedMaster.longitude}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="lg-action-btn rounded-xl px-3 py-2 font-medium text-white"
                            >
                                {{ t('route') }}
                            </a>
                            <button
                                v-if="
                                    !selectedMaster.is_claimed &&
                                    selectedMaster.claim_link
                                "
                                type="button"
                                class="lg-action-btn lg-action-claim rounded-xl px-3 py-2 font-medium text-white"
                                @click="claimProfile"
                            >
                                {{ t('claim') }}
                            </button>
                            <button
                                type="button"
                                class="lg-action-btn rounded-xl px-3 py-2 font-medium text-white"
                                :class="
                                    canRequestMasterStatus
                                        ? 'lg-action-status'
                                        : 'lg-action-disabled'
                                "
                                :disabled="
                                    !canRequestMasterStatus ||
                                    isSendingStatusRequest
                                "
                                @click="requestMasterStatus"
                            >
                                {{
                                    isSendingStatusRequest
                                        ? t('sending')
                                        : t('askStatus')
                                }}
                            </button>
                        </div>

                        <div
                            v-if="statusRequestMessage"
                            class="mb-3 rounded-lg px-3 py-2 text-sm"
                            :class="isFloxcity ? 'bg-emerald-50 text-slate-800' : 'bg-sky-50 text-slate-800'"
                        >
                            {{ statusRequestMessage }}
                        </div>

                        <div
                            v-if="primaryService"
                            class="mb-3 rounded-xl p-3"
                            :class="'bg-white/70'"
                        >
                            <div
                                class="text-xs uppercase tracking-wide"
                                :class="'text-slate-500'"
                            >
                                {{ t('mainService') }}
                            </div>
                            <div
                                class="mt-1 text-sm font-semibold"
                                :class="'text-slate-900'"
                            >
                                {{
                                    SERVICE_LABELS[primaryService.name]?.[
                                        currentLang
                                    ] ?? primaryService.name
                                }}
                            </div>
                        </div>

                        <div
                            v-if="hasAnyMasterImage"
                            class="grid grid-cols-3 gap-2 md:grid-cols-4"
                        >
                            <button
                                v-for="(
                                    photo, idx
                                ) in selectedMasterPhotos.slice(0, 8)"
                                :key="idx"
                                type="button"
                                class="gallery-thumb group relative aspect-square overflow-hidden rounded-lg"
                                @click="openLightbox(photo)"
                            >
                                <img
                                    :src="photoUrl(photo) || ''"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                    decoding="async"
                                    alt=""
                                />
                                <span
                                    class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/0 text-xs font-semibold text-white opacity-0 transition duration-200 group-hover:bg-black/35 group-hover:opacity-100"
                                >
                                    {{ t('profile') }}
                                </span>
                            </button>
                        </div>

                        <div v-if="extraServices.length" class="mt-3">
                            <div
                                class="mb-2 text-sm font-semibold"
                                :class="'text-slate-900'"
                            >
                                {{ t('extraServices') }}
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="service in extraServices"
                                    :key="service.id"
                                    class="rounded-full px-3 py-1 text-sm"
                                    :class="isFloxcity ? 'bg-emerald-50 text-slate-800' : 'bg-sky-50 text-slate-800'"
                                >
                                    {{
                                        SERVICE_LABELS[service.name]?.[
                                            currentLang
                                        ] ?? service.name
                                    }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div
                                class="mb-2 text-sm font-semibold"
                                :class="'text-slate-900'"
                            >
                                {{ t('reviews') }}
                            </div>
                            <div
                                v-if="selectedMaster.reviews?.length"
                                class="space-y-2"
                            >
                                <div
                                    v-for="review in selectedMaster.reviews"
                                    :key="review.id"
                                    class="rounded-lg p-3 text-sm"
                                    :class="'bg-white/70'"
                                >
                                    <div
                                        class="font-medium"
                                        :class="'text-slate-900'"
                                    >
                                        {{
                                            review.user?.name || t('anonymous')
                                        }}
                                    </div>
                                    <div :class="isFloxcity ? 'text-yellow-500' : 'text-yellow-300'">
                                        ★ {{ review.rating }}
                                    </div>
                                    <div :class="'text-slate-700'">
                                        {{ review.review || '—' }}
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else
                                class="text-sm"
                                :class="'text-slate-500'"
                            >
                                {{ t('noReviews') }}
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>

        <Transition name="lightbox-fade">
            <div
                v-if="lightboxImage"
                class="lightbox-backdrop absolute inset-0 z-[999] flex items-center justify-center p-3 md:p-8"
                @click.self="closeLightbox"
            >
                <button
                    type="button"
                    class="lightbox-close absolute right-4 top-4 rounded-full px-4 py-2 text-sm font-semibold text-white"
                    @click="closeLightbox"
                >
                    ✕ Close
                </button>
                <img
                    :src="lightboxImage"
                    class="lightbox-image max-h-full max-w-full rounded-2xl object-contain"
                    alt=""
                />
            </div>
        </Transition>

        </div>

        <section
            v-if="seoContent && !isMasterSeoContent"
            class="border-t border-slate-200 bg-white"
        >
            <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
                <nav
                    v-if="seoContent.breadcrumbs.length"
                    class="mb-4 flex flex-wrap items-center gap-2 text-sm text-slate-500"
                    aria-label="Breadcrumb"
                >
                    <template
                        v-for="(crumb, index) in seoContent.breadcrumbs"
                        :key="`${crumb.href}-${index}`"
                    >
                        <a
                            :href="crumb.href"
                            class="transition hover:text-slate-900"
                        >
                            {{ crumb.label }}
                        </a>
                        <span
                            v-if="index < seoContent.breadcrumbs.length - 1"
                            aria-hidden="true"
                        >
                            /
                        </span>
                    </template>
                </nav>

                <div class="grid gap-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.8fr)]">
                    <div>
                        <h1
                            v-if="!isMasterSeoContent"
                            class="text-3xl font-semibold tracking-tight text-slate-900"
                        >
                            {{ seoContent.title }}
                        </h1>
                        <p
                            v-if="seoContent.intro"
                            class="max-w-3xl text-base leading-7 text-slate-600"
                            :class="isMasterSeoContent ? '' : 'mt-3'"
                        >
                            {{ seoContent.intro }}
                        </p>

                        <div
                            v-if="seoContent.stats.length && !isMasterSeoContent"
                            class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
                        >
                            <div
                                v-for="stat in seoContent.stats"
                                :key="stat.label"
                                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"
                            >
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ stat.label }}
                                </div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">
                                    {{ stat.value }}
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="seoContent.sections?.length"
                            class="mt-8 space-y-6"
                        >
                            <section
                                v-for="section in seoContent.sections"
                                :key="section.heading"
                            >
                                <h2 class="text-lg font-semibold text-slate-900">
                                    {{ section.heading }}
                                </h2>
                                <p class="mt-2 text-base leading-7 text-slate-600">
                                    {{ section.body }}
                                </p>
                            </section>
                        </div>

                        <div
                            v-if="seoContent.serviceLinks.length && !isMasterSeoContent"
                            class="mt-8"
                        >
                            <h2 class="text-lg font-semibold text-slate-900">
                                Service Pages
                            </h2>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a
                                    v-for="link in seoContent.serviceLinks"
                                    :key="link.href"
                                    :href="link.href"
                                    class="rounded-full px-3 py-1.5 text-sm font-medium transition"
                                    :class="
                                        link.active
                                            ? isFloxcity
                                                ? 'bg-emerald-600 text-white'
                                                : 'bg-sky-600 text-white'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                                    "
                                >
                                    {{ link.label }}
                                </a>
                            </div>
                        </div>

                        <div v-if="seoContent.topMasters.length" class="mt-8">
                            <h2 class="text-lg font-semibold text-slate-900">
                                Stations
                            </h2>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <a
                                    v-for="master in seoContent.topMasters"
                                    :key="master.slug"
                                    :href="`/sto/${master.slug}`"
                                    class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 hover:shadow-sm"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="text-base font-semibold text-slate-900">
                                                {{ master.name }}
                                            </div>
                                            <div
                                                v-if="master.address"
                                                class="mt-1 text-sm text-slate-600"
                                            >
                                                {{ master.address }}
                                            </div>
                                        </div>
                                        <div
                                            v-if="master.rating"
                                            class="rounded-full bg-amber-50 px-2.5 py-1 text-sm font-semibold text-amber-700"
                                        >
                                            ★ {{ Number(master.rating).toFixed(1) }}
                                        </div>
                                    </div>
                                    <div
                                        v-if="master.service_names?.length"
                                        class="mt-3 flex flex-wrap gap-2"
                                    >
                                        <span
                                            v-for="serviceName in master.service_names"
                                            :key="serviceName"
                                            class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"
                                        >
                                            {{ serviceName }}
                                        </span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <div
                            v-if="seoContent.relatedLinks.length"
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-5"
                        >
                            <h2 class="text-lg font-semibold text-slate-900">
                                Related Pages
                            </h2>
                            <div class="mt-3 space-y-2">
                                <a
                                    v-for="link in seoContent.relatedLinks"
                                    :key="link.href"
                                    :href="link.href"
                                    class="block text-sm font-medium text-slate-700 transition hover:text-slate-900"
                                >
                                    {{ link.label }}
                                </a>
                            </div>
                        </div>

                        <div
                            v-if="seoContent.faq.length"
                            class="rounded-2xl border border-slate-200 bg-white p-5"
                        >
                            <h2 class="text-lg font-semibold text-slate-900">
                                FAQ
                            </h2>
                            <div class="mt-4 space-y-4">
                                <div
                                    v-for="item in seoContent.faq"
                                    :key="item.q"
                                >
                                    <h3 class="text-sm font-semibold text-slate-900">
                                        {{ item.q }}
                                    </h3>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        {{ item.a }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>


<style scoped>
.glass-panel {
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    color: var(--panel-text);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.24);
    backdrop-filter: blur(24px) saturate(160%);
    -webkit-backdrop-filter: blur(24px) saturate(160%);
}

.glass-input,
.glass-chip,
.glass-button {
    color: var(--panel-text);
    background: var(--surface-bg);
    border: 1px solid var(--surface-border);
    box-shadow: var(--surface-shadow);
    backdrop-filter: blur(18px) saturate(140%);
    -webkit-backdrop-filter: blur(18px) saturate(140%);
    transition: background 0.12s ease, border-color 0.12s ease;
}

.glass-button:hover,
.glass-chip:hover,
.glass-input:hover {
    background: var(--surface-bg-hover);
    border-color: var(--surface-border);
}

.glass-button:active {
    opacity: 0.75;
}

.glass-input {
    cursor: pointer;
}

.glass-input:focus,
.glass-input:focus-visible {
    outline: none;
    border-color: rgba(var(--glass-accent-rgb), 0.35);
}

.lg-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none;
    background: rgba(255, 255, 255, 0.11);
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    transition: opacity 0.12s ease, transform 0.10s ease;
}

.lg-action-btn:hover {
    opacity: 0.82;
}

.lg-action-btn:active {
    transform: scale(0.97);
}

.lg-action-call {
    background: var(--brand-primary-strong);
    border-color: transparent;
}

.lg-action-claim {
    background: var(--brand-success);
    border-color: transparent;
}

.lg-action-status {
    background: var(--brand-primary);
    border-color: transparent;
}

.lg-action-disabled {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.07);
    color: rgba(255, 255, 255, 0.28) !important;
    cursor: not-allowed;
    opacity: 1 !important;
    transform: none !important;
}

.lg-close {
    background: rgba(255, 255, 255, 0.10);
    border: 1px solid rgba(255, 255, 255, 0.14);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    transition: background 0.12s ease, border-color 0.12s ease;
}

.lg-close:hover {
    background: rgba(255, 255, 255, 0.18);
    border-color: rgba(255, 255, 255, 0.22);
}

.lg-loading-pill {
    background: var(--loading-pill-bg);
    border: 1px solid var(--panel-border);
    color: var(--panel-text);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}

.app-download-cta {
    background: var(--brand-primary);
    border: 1px solid rgba(255, 255, 255, 0.18);
    text-decoration: none;
    animation: app-cta-pulse 1.8s infinite ease-in-out;
    box-shadow:
        0 0 0 0 rgba(var(--brand-primary-rgb), 0.55),
        0 0 18px rgba(var(--brand-primary-rgb), 0.42);
    transition: opacity 0.12s ease;
}

.app-download-cta:hover {
    opacity: 0.88;
}

.listbox-options {
    background: var(--dropdown-bg);
    border: 1px solid var(--panel-border);
    color: var(--panel-text);
    list-style: none;
    margin: 0;
    padding: 0.25rem;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.24);
    backdrop-filter: blur(22px) saturate(150%);
    -webkit-backdrop-filter: blur(22px) saturate(150%);
    transform-origin: top center;
}

.listbox-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    color: var(--panel-text);
    cursor: pointer;
    transition: background 0.10s ease;
}

.listbox-option.is-active {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
}

.listbox-option.is-selected {
    background: var(--cluster-bg);
    color: #fff;
    font-weight: 600;
}

.listbox-option.is-selected.is-active {
    background: var(--cluster-bg);
}

.listbox-options::-webkit-scrollbar {
    width: 6px;
}

.listbox-options::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 999px;
}

.listbox-options::-webkit-scrollbar-track {
    background: transparent;
}

.listbox-options {
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.15) transparent;
}

:global(.master-marker-wrapper) {
    background: transparent;
    border: none;
    will-change: transform;
}

:global(.master-marker) {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 9999px;
    border: 2px solid rgba(255, 255, 255, 0.80);
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.24);
    transform: translateZ(0);
    transition:
        transform 0.18s ease,
        box-shadow 0.18s ease,
        border-color 0.18s ease;
}

:global(.master-marker::after) {
    content: '';
    position: absolute;
    inset: -7px;
    border-radius: 9999px;
    border: 2px solid transparent;
    opacity: 0;
    transform: scale(0.9);
    transition:
        opacity 0.18s ease,
        transform 0.18s ease,
        border-color 0.18s ease;
    pointer-events: none;
}

:global(.master-marker.available-marker) {
    border-color: #34d399;
    box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.28), 0 4px 14px rgba(15, 23, 42, 0.24);
    animation: marker-pulse 1.8s infinite ease-in-out;
}

:global(.master-marker.unavailable-marker) {
    border-color: #f87171;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.45);
}

:global(.master-marker.active-marker) {
    transform: translateY(-4px) scale(1.24);
    border-color: rgba(255, 255, 255, 0.98);
    box-shadow:
        0 0 0 4px rgba(var(--brand-primary-rgb), 0.22),
        0 10px 26px rgba(15, 23, 42, 0.30);
}

:global(.master-marker.active-marker::after) {
    opacity: 1;
    transform: scale(1);
    border-color: rgba(var(--brand-primary-rgb), 0.46);
}

:global(.master-marker:hover) {
    transform: translateY(-2px) scale(1.1);
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
    width: 22px;
    height: 22px;
}

:global(.cluster-marker .cluster-inner) {
    width: 44px;
    height: 44px;
    border-radius: 9999px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    background: var(--cluster-bg);
    border: 2px solid rgba(255, 255, 255, 0.65);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.45);
    transition: transform 0.14s ease;
}

:global(.cluster-marker.cluster-md .cluster-inner) {
    width: 50px;
    height: 50px;
    font-size: 14px;
}

:global(.cluster-marker.cluster-lg .cluster-inner) {
    width: 56px;
    height: 56px;
    font-size: 15px;
}

:global(.cluster-marker .cluster-inner:hover) {
    transform: scale(1.08);
}

:global(.cluster-marker.cluster-has-available .cluster-inner) {
    border-color: #34d399;
    box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.32), 0 2px 8px rgba(0, 0, 0, 0.45);
    animation: marker-pulse 1.8s infinite ease-in-out;
}

@keyframes marker-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.60), 0 2px 8px rgba(0, 0, 0, 0.45);
    }
    65% {
        box-shadow: 0 0 0 12px rgba(52, 211, 153, 0), 0 2px 8px rgba(0, 0, 0, 0.45);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(52, 211, 153, 0), 0 2px 8px rgba(0, 0, 0, 0.45);
    }
}

@keyframes app-cta-pulse {
    0% {
        box-shadow:
            0 0 0 0 rgba(var(--brand-primary-rgb), 0.60),
            0 0 16px rgba(var(--brand-primary-rgb), 0.38);
    }
    50% {
        box-shadow:
            0 0 0 14px rgba(var(--brand-primary-rgb), 0.12),
            0 0 28px rgba(var(--brand-primary-rgb), 0.62);
    }
    100% {
        box-shadow:
            0 0 0 20px rgba(var(--brand-primary-rgb), 0),
            0 0 18px rgba(var(--brand-primary-rgb), 0.22);
    }
}

.master-card-enter-active,
.master-card-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}

.master-card-enter-from,
.master-card-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

.gallery-thumb {
    border: 1px solid rgba(255, 255, 255, 0.12);
    transition: opacity 0.12s ease;
}

.gallery-thumb:hover {
    opacity: 0.82;
}

.lightbox-backdrop {
    background: rgba(0, 0, 0, 0.82);
}

.lightbox-close {
    background: rgba(11, 15, 25, 0.56);
    border: 1px solid rgba(255, 255, 255, 0.14);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    transition: opacity 0.12s ease;
}

.lightbox-close:hover {
    opacity: 0.78;
}

.lightbox-image {
    border: 1px solid rgba(255, 255, 255, 0.10);
}

.lightbox-fade-enter-active,
.lightbox-fade-leave-active {
    transition: opacity 0.18s ease;
}

.lightbox-fade-enter-from,
.lightbox-fade-leave-to {
    opacity: 0;
}

.status-beacon {
    background: rgba(5, 18, 12, 0.54);
    border: 1px solid rgba(52, 211, 153, 0.34);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.22);
    backdrop-filter: blur(20px) saturate(150%);
    -webkit-backdrop-filter: blur(20px) saturate(150%);
}

.status-beacon-dot {
    background: #34d399;
    animation: status-beacon-ping 1.5s infinite ease-out;
}

.status-beacon-enter-active,
.status-beacon-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}

.status-beacon-enter-from,
.status-beacon-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

@keyframes status-beacon-ping {
    0% {
        box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.60);
    }
    72% {
        box-shadow: 0 0 0 10px rgba(52, 211, 153, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(52, 211, 153, 0);
    }
}

@media (max-width: 640px) {
    .guest-map-root {
        min-height: 100dvh;
    }

    .listbox-options {
        max-height: 55vh;
    }
}

</style>
