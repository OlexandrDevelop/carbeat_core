<script setup lang="ts">
import {
    detectLanguageByRegion,
    SERVICE_LABELS,
    type Lang,
} from '@/shared/guest-map-display-labels';
import { dayLabel, masterText } from '@/shared/public-master-display-labels';
import { Head } from '@inertiajs/vue3';
import QrcodeVue from 'qrcode.vue';
import { computed, onMounted, ref } from 'vue';

type Service = {
    id: number;
    name: string;
    is_primary: boolean;
};

type GalleryItem = {
    id: number;
    url: string;
};

type WorkingHours = Record<
    string,
    { from: string; to: string } | string | null | undefined
>;

interface MasterPayload {
    id: number;
    name: string;
    description: string | null;
    address: string | null;
    city: string | null;
    phone: string | null;
    latitude: number | null;
    longitude: number | null;
    experience: number | null;
    services: Service[] | null;
    gallery: GalleryItem[] | null;
    main_photo: string | null;
    working_hours: WorkingHours | null;
    is_claimed: boolean;
    claim_link: string | null;
    rating: number | null;
    reviews_count: number | null;
}

const props = defineProps<{
    master: MasterPayload;
}>();

const currentUrl = ref<string>('');
const canUseShare = ref(false);
const copyFeedback = ref<string | null>(null);
const currentLang = ref<Lang>('en');

onMounted(() => {
    if (typeof window !== 'undefined') {
        currentUrl.value = window.location.href;
    }
    canUseShare.value =
        typeof navigator !== 'undefined' &&
        typeof navigator.share === 'function';

    const saved = localStorage.getItem('site_lang');
    if (saved === 'en' || saved === 'uk' || saved === 'de') {
        currentLang.value = saved;
    } else {
        currentLang.value = detectLanguageByRegion();
    }
});

const telLink = computed(() => {
    if (!props.master.phone) return null;

    const clean = props.master.phone.replace(/\s+/g, '');
    return clean.startsWith('tel:') ? clean : `tel:${clean}`;
});

const mapsLink = computed(() => {
    // не вбиваємо координати 0, тільки null/undefined
    if (
        props.master.latitude !== null &&
        props.master.latitude !== undefined &&
        props.master.longitude !== null &&
        props.master.longitude !== undefined
    ) {
        return `https://www.google.com/maps/search/?api=1&query=${props.master.latitude},${props.master.longitude}`;
    }

    if (props.master.address) {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(props.master.address)}`;
    }

    return null;
});

const services = computed(() => props.master.services ?? []);
const mainService = computed(() => services.value.find((s) => s.is_primary));
const secondaryServices = computed(() =>
    services.value.filter((s) => !s.is_primary),
);
const gallery = computed(() => props.master.gallery ?? []);
const workingSchedule = computed(() =>
    formatWorkingHours(props.master.working_hours),
);
const canClaim = computed(
    () => !props.master.is_claimed && !!props.master.claim_link,
);

const hasRating = computed(
    () => props.master.rating !== null && props.master.rating !== undefined,
);
const ratingLabel = computed(() =>
    hasRating.value ? props.master.rating!.toFixed(1) : null,
);

const hasExperience = computed(
    () =>
        props.master.experience !== null &&
        props.master.experience !== undefined,
);

const locationLine = computed(() => {
    if (props.master.city && props.master.address) {
        return `${props.master.city} · ${props.master.address}`;
    }

    return props.master.city ?? props.master.address ?? null;
});

const masterInitials = computed(() => {
    const name = props.master.name ?? '';
    const initials = name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');

    return initials || 'CB';
});

function serviceLabel(serviceName: string): string {
    return SERVICE_LABELS[serviceName]?.[currentLang.value] ?? serviceName;
}

function formatWorkingHours(
    hours: WorkingHours | null,
): Array<{ day: string; value: string }> {
    if (!hours || typeof hours !== 'object') {
        return [];
    }

    const order = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    return order
        .filter((key) => key in hours)
        .map((key) => {
            const label = dayLabel(currentLang.value, key);
            const value = hours[key];

            if (!value) {
                return { day: label, value: t('dayOff') };
            }

            if (typeof value === 'string') {
                return { day: label, value };
            }

            if (typeof value === 'object' && 'from' in value && 'to' in value) {
                return { day: label, value: `${value.from} – ${value.to}` };
            }

            return { day: label, value: '—' };
        });
}

function t(key: Parameters<typeof masterText>[1]): string {
    return masterText(currentLang.value, key);
}

function setLanguage(lang: Lang): void {
    currentLang.value = lang;
    localStorage.setItem('site_lang', lang);
}

async function shareProfile() {
    copyFeedback.value = null;

    const fallbackUrl =
        currentUrl.value ||
        props.master.claim_link ||
        (typeof window !== 'undefined' ? window.location.href : '');

    const shareData = {
        title: props.master.name,
        text: t('shareProfileText'),
        url: fallbackUrl,
    };

    if (
        canUseShare.value &&
        typeof navigator !== 'undefined' &&
        typeof navigator.share === 'function'
    ) {
        try {
            await navigator.share(shareData);
            return;
        } catch (error) {
            // користувач відмінив — йдемо далі до копіювання
        }
    }

    if (
        typeof navigator !== 'undefined' &&
        navigator.clipboard &&
        navigator.clipboard.writeText
    ) {
        try {
            await navigator.clipboard.writeText(shareData.url);
            copyFeedback.value = t('copiedLink');
            return;
        } catch (_) {
            // падаємо до ручного варіанту нижче
        }
    }

    // якщо взагалі нічого не вийшло — показуємо URL, щоб можна було скопіпастити
    copyFeedback.value = shareData.url;
}

function claimProfile() {
    if (!props.master.claim_link) return;

    if (typeof window !== 'undefined') {
        window.location.href = props.master.claim_link;
    }
}
</script>

<template>
    <Head :title="`${master.name} · Floxcity`" />

    <div class="min-h-screen bg-slate-100 font-sans text-slate-800">
        <!-- HERO / HEADER -->
        <header class="relative px-4 pb-12 pt-6">
            <div class="mx-auto max-w-lg">
                <!-- Top bar -->
                <div class="mb-8 flex items-center justify-between">
                    <span class="text-lg font-bold tracking-tight text-slate-900"
                        >Floxcity</span
                    >
                    <div class="flex items-center gap-2">
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
                    <span
                        v-if="master.is_claimed"
                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-medium text-emerald-700"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                        {{ t('verified') }}
                    </span>
                </div>

                <!-- Master Card / Avatar Area -->
                <div class="flex flex-col items-center text-center">
                    <div class="relative mb-6">
                        <img
                            v-if="master.main_photo"
                            :src="master.main_photo"
                            :alt="master.name"
                            class="h-32 w-32 rounded-full object-cover ring-4 ring-white"
                        />
                        <div
                            v-else
                            class="flex h-32 w-32 items-center justify-center rounded-full bg-emerald-100 text-4xl font-bold text-emerald-700 ring-4 ring-white"
                        >
                            {{ masterInitials }}
                        </div>
                        <div
                            v-if="hasRating"
                            class="absolute -bottom-2 -right-2 flex items-center gap-1 rounded-xl bg-white px-2.5 py-1.5 text-sm font-bold text-slate-900 shadow-lg"
                        >
                            <span class="text-[#FFD60A]">★</span>
                            {{ ratingLabel }}
                        </div>
                    </div>

                    <h1 class="mb-2 text-2xl font-bold text-slate-900">
                        {{ master.name }}
                    </h1>
                    <p
                        v-if="mainService"
                        class="mb-1 text-[15px] font-medium text-slate-500"
                    >
                        {{ serviceLabel(mainService.name) }}
                    </p>
                    <p v-if="locationLine" class="text-sm text-slate-500">
                        {{ locationLine }}
                    </p>

                    <!-- Primary Action -->
                    <div class="mt-8 flex w-full gap-3">
                        <a
                            v-if="telLink"
                            :href="telLink"
                            class="flex flex-1 items-center justify-center rounded-2xl bg-[#10B981] px-4 py-3.5 text-[15px] font-semibold text-white transition active:opacity-80"
                        >
                            {{ t('call') }}
                        </a>
                        <button
                            v-if="canClaim"
                            @click="claimProfile"
                            class="flex flex-1 items-center justify-center rounded-2xl bg-[#32D74B] px-4 py-3.5 text-[15px] font-semibold text-black transition active:opacity-80"
                        >
                            {{ t('claim') }}
                        </button>
                        <button
                            v-else
                            @click="shareProfile"
                            class="flex flex-1 items-center justify-center rounded-2xl bg-white px-4 py-3.5 text-[15px] font-semibold text-slate-800 transition active:bg-slate-100"
                        >
                            {{ t('share') }}
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <main
            class="relative z-10 -mt-4 rounded-t-[32px] bg-white px-4 pb-12 pt-8 shadow-[0_-4px_24px_rgba(15,23,42,0.08)]"
        >
            <div class="mx-auto max-w-lg space-y-8">
                <!-- Actions Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <a
                        v-if="mapsLink"
                        :href="mapsLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex flex-col items-center gap-2 rounded-2xl bg-slate-100 p-4 text-center active:opacity-80"
                    >
                        <div
                            class="grid h-10 w-10 place-items-center rounded-full bg-emerald-100 text-[#10B981]"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-slate-900">{{
                            t('route')
                        }}</span>
                    </a>

                    <button
                        @click="shareProfile"
                        class="flex flex-col items-center gap-2 rounded-2xl bg-slate-100 p-4 text-center active:opacity-80"
                    >
                        <div
                            class="grid h-10 w-10 place-items-center rounded-full bg-emerald-100 text-[#10B981]"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"
                                />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-slate-900">{{
                            t('share')
                        }}</span>
                    </button>
                </div>

                <!-- Deep Link Banner -->
                <div
                    v-if="master.claim_link"
                    class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#10B981] to-[#059669] p-5 shadow-lg"
                >
                    <a
                        :href="master.claim_link"
                        class="flex items-center justify-between"
                    >
                        <div>
                            <p
                                class="text-xs font-bold uppercase tracking-wider text-white/80"
                            >
                                {{ t('owner') }}
                            </p>
                            <p
                                class="mt-1 text-[15px] font-semibold text-white"
                            >
                                {{ t('openInApp') }}
                            </p>
                        </div>
                        <div
                            class="grid h-8 w-8 place-items-center rounded-full bg-white/20 text-white"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </div>
                    </a>
                </div>

                <!-- About -->
                <section v-if="master.description">
                    <h3
                        class="mb-3 text-[13px] font-bold uppercase tracking-wider text-slate-500"
                    >
                        {{ t('aboutMaster') }}
                    </h3>
                    <p
                        class="whitespace-pre-line text-[15px] leading-relaxed text-slate-700"
                    >
                        {{ master.description }}
                    </p>
                </section>

                <!-- Info Grid -->
                <section>
                    <h3
                        class="mb-3 text-[13px] font-bold uppercase tracking-wider text-slate-500"
                    >
                        {{ t('details') }}
                    </h3>
                    <div
                        class="space-y-px overflow-hidden rounded-2xl bg-slate-200"
                    >
                        <div
                            v-if="master.experience"
                            class="flex items-center justify-between bg-slate-50 p-4"
                        >
                            <span class="text-[15px] text-slate-500">{{
                                t('experience')
                            }}</span>
                            <span class="text-[15px] font-medium text-slate-900"
                                >{{ master.experience }} {{ t('years') }}</span
                            >
                        </div>
                        <div
                            class="flex items-center justify-between bg-slate-50 p-4"
                        >
                            <span class="text-[15px] text-slate-500">{{
                                t('services')
                            }}</span>
                            <span class="text-[15px] font-medium text-slate-900">{{
                                services.length
                            }}</span>
                        </div>
                        <div
                            v-if="master.city"
                            class="flex items-center justify-between bg-slate-50 p-4"
                        >
                            <span class="text-[15px] text-slate-500">{{
                                t('city')
                            }}</span>
                            <span class="text-[15px] font-medium text-slate-900">{{
                                master.city
                            }}</span>
                        </div>
                    </div>
                </section>

                <!-- Schedule -->
                <section v-if="workingSchedule.length">
                    <h3
                        class="mb-3 text-[13px] font-bold uppercase tracking-wider text-slate-500"
                    >
                        {{ t('workSchedule') }}
                    </h3>
                    <div
                        class="space-y-px overflow-hidden rounded-2xl bg-slate-200"
                    >
                        <div
                            v-for="item in workingSchedule"
                            :key="item.day"
                            class="flex items-center justify-between bg-slate-50 p-4"
                        >
                            <span class="text-[15px] text-slate-500">{{
                                item.day
                            }}</span>
                            <span class="text-[15px] font-medium text-slate-900">{{
                                item.value
                            }}</span>
                        </div>
                    </div>
                </section>

                <!-- Gallery -->
                <section v-if="gallery.length">
                    <h3
                        class="mb-3 text-[13px] font-bold uppercase tracking-wider text-slate-500"
                    >
                        {{ t('portfolio') }}
                    </h3>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <img
                            v-for="photo in gallery"
                            :key="photo.id"
                            :src="photo.url"
                            :alt="`Робота #${photo.id}`"
                            class="aspect-square w-full rounded-xl bg-slate-100 object-cover"
                        />
                    </div>
                </section>

                <!-- Services Tags -->
                <section v-if="secondaryServices.length">
                    <h3
                        class="mb-3 text-[13px] font-bold uppercase tracking-wider text-slate-500"
                    >
                        {{ t('allServices') }}
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="service in secondaryServices"
                            :key="service.id"
                            class="rounded-lg bg-slate-100 px-3 py-1.5 text-[14px] text-slate-700"
                        >
                            {{ serviceLabel(service.name) }}
                        </span>
                    </div>
                </section>

                <!-- Footer -->
                <div class="flex flex-col items-center gap-6 pt-8 text-center">
                    <div v-if="currentUrl" class="rounded-2xl bg-white p-3">
                        <QrcodeVue :value="currentUrl" :size="120" />
                    </div>
                    <p class="text-xs font-medium text-slate-500">
                        {{ t('scanOpenInApp') }}
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>
