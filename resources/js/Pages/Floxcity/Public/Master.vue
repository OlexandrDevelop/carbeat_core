<script setup lang="ts">
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

onMounted(() => {
    if (typeof window !== 'undefined') {
        currentUrl.value = window.location.href;
    }
    canUseShare.value =
        typeof navigator !== 'undefined' &&
        typeof navigator.share === 'function';
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

function formatWorkingHours(
    hours: WorkingHours | null,
): Array<{ day: string; value: string }> {
    if (!hours || typeof hours !== 'object') {
        return [];
    }

    const order = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    const dayLabels: Record<string, string> = {
        mon: 'Пн',
        tue: 'Вт',
        wed: 'Ср',
        thu: 'Чт',
        fri: 'Пт',
        sat: 'Сб',
        sun: 'Нд',
    };

    return order
        .filter((key) => key in hours)
        .map((key) => {
            const dayLabel = dayLabels[key] ?? key;
            const value = hours[key];

            if (!value) {
                return { day: dayLabel, value: 'Вихідний' };
            }

            if (typeof value === 'string') {
                return { day: dayLabel, value };
            }

            if (typeof value === 'object' && 'from' in value && 'to' in value) {
                return { day: dayLabel, value: `${value.from} – ${value.to}` };
            }

            return { day: dayLabel, value: '—' };
        });
}

async function shareProfile() {
    copyFeedback.value = null;

    const fallbackUrl =
        currentUrl.value ||
        props.master.claim_link ||
        (typeof window !== 'undefined' ? window.location.href : '');

    const shareData = {
        title: props.master.name,
        text: 'Мій профіль майстра краси у Floxcity',
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
            copyFeedback.value = 'Посилання скопійовано';
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

    <!-- Dark theme wrapper matching the app style -->
    <div class="min-h-screen bg-[#111315] font-sans text-[#E1E3E5]">
        <!-- HERO / HEADER -->
        <header class="relative px-4 pb-12 pt-6">
            <div class="mx-auto max-w-lg">
                <!-- Top bar -->
                <div class="mb-8 flex items-center justify-between">
                    <span class="text-lg font-bold tracking-tight text-white"
                        >Floxcity</span
                    >
                    <span
                        v-if="master.is_claimed"
                        class="inline-flex items-center gap-1.5 rounded-full bg-[#1C1F22] px-3 py-1.5 text-xs font-medium text-[#4CD964]"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-[#4CD964]" />
                        Підтверджено
                    </span>
                </div>

                <!-- Master Card / Avatar Area -->
                <div class="flex flex-col items-center text-center">
                    <div class="relative mb-6">
                        <img
                            v-if="master.main_photo"
                            :src="master.main_photo"
                            :alt="master.name"
                            class="h-32 w-32 rounded-full object-cover ring-4 ring-[#1C1F22]"
                        />
                        <div
                            v-else
                            class="flex h-32 w-32 items-center justify-center rounded-full bg-[#1C1F22] text-4xl font-bold text-white ring-4 ring-[#1C1F22]"
                        >
                            {{ masterInitials }}
                        </div>
                        <div
                            v-if="hasRating"
                            class="absolute -bottom-2 -right-2 flex items-center gap-1 rounded-xl bg-[#1C1F22] px-2.5 py-1.5 text-sm font-bold text-white shadow-lg"
                        >
                            <span class="text-[#FFD60A]">★</span>
                            {{ ratingLabel }}
                        </div>
                    </div>

                    <h1 class="mb-2 text-2xl font-bold text-white">
                        {{ master.name }}
                    </h1>
                    <p
                        v-if="mainService"
                        class="mb-1 text-[15px] font-medium text-[#8E8E93]"
                    >
                        {{ mainService.name }}
                    </p>
                    <p v-if="locationLine" class="text-sm text-[#636366]">
                        {{ locationLine }}
                    </p>

                    <!-- Primary Action -->
                    <div class="mt-8 flex w-full gap-3">
                        <a
                            v-if="telLink"
                            :href="telLink"
                            class="flex flex-1 items-center justify-center rounded-2xl bg-[#0A84FF] px-4 py-3.5 text-[15px] font-semibold text-white transition active:opacity-80"
                        >
                            Подзвонити
                        </a>
                        <button
                            v-if="canClaim"
                            @click="claimProfile"
                            class="flex flex-1 items-center justify-center rounded-2xl bg-[#32D74B] px-4 py-3.5 text-[15px] font-semibold text-black transition active:opacity-80"
                        >
                            Це я
                        </button>
                        <button
                            v-else
                            @click="shareProfile"
                            class="flex flex-1 items-center justify-center rounded-2xl bg-[#1C1F22] px-4 py-3.5 text-[15px] font-semibold text-white transition active:bg-[#2C2F33]"
                        >
                            Поділитися
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <main
            class="relative z-10 -mt-4 rounded-t-[32px] bg-[#1C1F22] px-4 pb-12 pt-8 shadow-[0_-4px_24px_rgba(0,0,0,0.4)]"
        >
            <div class="mx-auto max-w-lg space-y-8">
                <!-- Actions Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <a
                        v-if="mapsLink"
                        :href="mapsLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex flex-col items-center gap-2 rounded-2xl bg-[#2C2F33] p-4 text-center active:opacity-80"
                    >
                        <div
                            class="grid h-10 w-10 place-items-center rounded-full bg-[#3A3D41] text-[#0A84FF]"
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
                        <span class="text-sm font-medium text-white"
                            >Маршрут</span
                        >
                    </a>

                    <button
                        @click="shareProfile"
                        class="flex flex-col items-center gap-2 rounded-2xl bg-[#2C2F33] p-4 text-center active:opacity-80"
                    >
                        <div
                            class="grid h-10 w-10 place-items-center rounded-full bg-[#3A3D41] text-[#0A84FF]"
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
                        <span class="text-sm font-medium text-white"
                            >Поділитися</span
                        >
                    </button>
                </div>

                <!-- Deep Link Banner -->
                <div
                    v-if="master.claim_link"
                    class="overflow-hidden rounded-2xl bg-gradient-to-br from-[#0A84FF] to-[#0055D4] p-5 shadow-lg"
                >
                    <a
                        :href="master.claim_link"
                        class="flex items-center justify-between"
                    >
                        <div>
                            <p
                                class="text-xs font-bold uppercase tracking-wider text-white/70"
                            >
                                Власник?
                            </p>
                            <p
                                class="mt-1 text-[15px] font-semibold text-white"
                            >
                                Відкрити у додатку
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
                        class="mb-3 text-[13px] font-bold uppercase tracking-wider text-[#8E8E93]"
                    >
                        Про майстра
                    </h3>
                    <p
                        class="whitespace-pre-line text-[15px] leading-relaxed text-[#E1E3E5]"
                    >
                        {{ master.description }}
                    </p>
                </section>

                <!-- Info Grid -->
                <section>
                    <h3
                        class="mb-3 text-[13px] font-bold uppercase tracking-wider text-[#8E8E93]"
                    >
                        Деталі
                    </h3>
                    <div
                        class="space-y-px overflow-hidden rounded-2xl bg-[#2C2F33]"
                    >
                        <div
                            v-if="master.experience"
                            class="flex items-center justify-between bg-[#232629] p-4"
                        >
                            <span class="text-[15px] text-[#8E8E93]"
                                >Досвід</span
                            >
                            <span class="text-[15px] font-medium text-white"
                                >{{ master.experience }} років</span
                            >
                        </div>
                        <div
                            class="flex items-center justify-between bg-[#232629] p-4"
                        >
                            <span class="text-[15px] text-[#8E8E93]"
                                >Послуги</span
                            >
                            <span class="text-[15px] font-medium text-white">{{
                                services.length
                            }}</span>
                        </div>
                        <div
                            v-if="master.city"
                            class="flex items-center justify-between bg-[#232629] p-4"
                        >
                            <span class="text-[15px] text-[#8E8E93]"
                                >Місто</span
                            >
                            <span class="text-[15px] font-medium text-white">{{
                                master.city
                            }}</span>
                        </div>
                    </div>
                </section>

                <!-- Schedule -->
                <section v-if="workingSchedule.length">
                    <h3
                        class="mb-3 text-[13px] font-bold uppercase tracking-wider text-[#8E8E93]"
                    >
                        Графік роботи
                    </h3>
                    <div
                        class="space-y-px overflow-hidden rounded-2xl bg-[#2C2F33]"
                    >
                        <div
                            v-for="item in workingSchedule"
                            :key="item.day"
                            class="flex items-center justify-between bg-[#232629] p-4"
                        >
                            <span class="text-[15px] text-[#8E8E93]">{{
                                item.day
                            }}</span>
                            <span class="text-[15px] font-medium text-white">{{
                                item.value
                            }}</span>
                        </div>
                    </div>
                </section>

                <!-- Gallery -->
                <section v-if="gallery.length">
                    <h3
                        class="mb-3 text-[13px] font-bold uppercase tracking-wider text-[#8E8E93]"
                    >
                        Портфоліо
                    </h3>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <img
                            v-for="photo in gallery"
                            :key="photo.id"
                            :src="photo.url"
                            :alt="`Робота #${photo.id}`"
                            class="aspect-square w-full rounded-xl bg-[#2C2F33] object-cover"
                        />
                    </div>
                </section>

                <!-- Services Tags -->
                <section v-if="secondaryServices.length">
                    <h3
                        class="mb-3 text-[13px] font-bold uppercase tracking-wider text-[#8E8E93]"
                    >
                        Всі послуги
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="service in secondaryServices"
                            :key="service.id"
                            class="rounded-lg bg-[#2C2F33] px-3 py-1.5 text-[14px] text-[#E1E3E5]"
                        >
                            {{ service.name }}
                        </span>
                    </div>
                </section>

                <!-- Footer -->
                <div class="flex flex-col items-center gap-6 pt-8 text-center">
                    <div v-if="currentUrl" class="rounded-2xl bg-white p-3">
                        <QrcodeVue :value="currentUrl" :size="120" />
                    </div>
                    <p class="text-xs font-medium text-[#636366]">
                        Відскануйте, щоб відкрити у Floxcity
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>
