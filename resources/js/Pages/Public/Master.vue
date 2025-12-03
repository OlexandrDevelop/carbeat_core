<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import QrcodeVue from 'qrcode.vue';

type Service = {
    id: number;
    name: string;
    is_primary: boolean;
};

type GalleryItem = {
    id: number;
    url: string;
};

type WorkingHours = Record<string, { from: string; to: string } | string | null | undefined>;

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
    canUseShare.value = typeof navigator !== 'undefined' && typeof navigator.share === 'function';
});

const telLink = computed(() => {
    if (!props.master.phone) return null;

    const clean = props.master.phone.replace(/\s+/g, '');
    return clean.startsWith('tel:') ? clean : `tel:${clean}`;
});

const mapsLink = computed(() => {
    // не вбиваємо координати 0, тільки null/undefined
    if (props.master.latitude !== null && props.master.latitude !== undefined &&
        props.master.longitude !== null && props.master.longitude !== undefined) {
        return `https://www.google.com/maps/search/?api=1&query=${props.master.latitude},${props.master.longitude}`;
    }

    if (props.master.address) {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(props.master.address)}`;
    }

    return null;
});

const services = computed(() => props.master.services ?? []);
const mainService = computed(() => services.value.find((s) => s.is_primary));
const secondaryServices = computed(() => services.value.filter((s) => !s.is_primary));
const gallery = computed(() => props.master.gallery ?? []);
const workingSchedule = computed(() => formatWorkingHours(props.master.working_hours));
const canClaim = computed(() => !props.master.is_claimed && !!props.master.claim_link);

const hasRating = computed(
    () => props.master.rating !== null && props.master.rating !== undefined
);
const ratingLabel = computed(() =>
    hasRating.value ? props.master.rating!.toFixed(1) : null
);

const hasExperience = computed(
    () => props.master.experience !== null && props.master.experience !== undefined
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

function formatWorkingHours(hours: WorkingHours | null): Array<{ day: string; value: string }> {
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
        text: 'Мій профіль автомайстра в Carbeat',
        url: fallbackUrl,
    };

    if (canUseShare.value && typeof navigator !== 'undefined' && typeof navigator.share === 'function') {
        try {
            await navigator.share(shareData);
            return;
        } catch (error) {
            // користувач відмінив — йдемо далі до копіювання
        }
    }

    if (typeof navigator !== 'undefined' && navigator.clipboard && navigator.clipboard.writeText) {
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
    <Head :title="`${master.name} · Carbeat`" />

    <div class="min-h-screen bg-slate-950 text-white">
        <!-- HERO -->
        <header
            class="relative isolate overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950"
        >
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute -top-10 right-20 h-72 w-72 rounded-full bg-sky-500/25 blur-3xl" />
                <div class="absolute bottom-10 left-3 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl" />
            </div>

            <div class="relative mx-auto max-w-6xl px-4 py-20 lg:py-24">
                <div class="flex items-center justify-between gap-4 pb-8 text-xs text-white/60">
                    <span class="rounded-full bg-white/5 px-3 py-1 uppercase tracking-[0.35em]">
                        публічний профіль
                    </span>
                    <span class="hidden items-center gap-2 rounded-full bg-white/5 px-3 py-1 sm:inline-flex">
                        <span class="h-2 w-2 rounded-full bg-emerald-400/80" />
                        <span class="uppercase tracking-[0.35em] text-white/70">carbeat</span>
                    </span>
                </div>

                <div class="grid items-center gap-10 lg:grid-cols-[1.35fr_0.65fr]">
                    <!-- LEFT SIDE -->
                    <div class="space-y-7">
                        <div>
                            <h1
                                class="text-balance text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl"
                            >
                                {{ master.name }}
                            </h1>
                            <p
                                v-if="locationLine"
                                class="mt-4 text-base font-medium text-white/80 sm:text-lg"
                            >
                                {{ locationLine }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3 text-sm text-white/80">
                            <span
                                v-if="hasRating"
                                class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 ring-1 ring-white/15"
                            >
                                <span
                                    class="inline-flex items-center justify-center rounded-full bg-amber-400/20 px-2 py-0.5 text-xs font-semibold text-amber-100"
                                >
                                    ★
                                </span>
                                <span class="text-base font-semibold text-amber-100">
                                    {{ ratingLabel }}
                                </span>
                                <span v-if="master.reviews_count" class="text-xs text-white/70">
                                    · {{ master.reviews_count }} відгуків
                                </span>
                            </span>

                            <span
                                v-if="hasExperience"
                                class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 ring-1 ring-white/15"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-white/60" />
                                {{ master.experience }} років досвіду
                            </span>

                            <span
                                :class="[
                                    'inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-semibold ring-1 ring-white/20',
                                    master.is_claimed
                                        ? 'bg-emerald-400/20 text-emerald-100'
                                        : 'bg-amber-400/20 text-amber-100',
                                ]"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="master.is_claimed ? 'bg-emerald-300' : 'bg-amber-300'"
                                />
                                {{ master.is_claimed ? 'Профіль підтверджено' : 'Очікує підтвердження' }}
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-4">
                            <a
                                v-if="telLink"
                                :href="telLink"
                                class="inline-flex items-center gap-2 rounded-full bg-white px-7 py-3 text-sm font-semibold uppercase tracking-wide text-slate-900 shadow-lg shadow-slate-900/30 transition hover:-translate-y-0.5 hover:bg-slate-100"
                            >
                                Подзвонити
                            </a>
                            <a
                                v-if="mapsLink"
                                :href="mapsLink"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 rounded-full border border-white/30 px-6 py-3 text-sm font-semibold uppercase tracking-wide text-white/90 transition hover:-translate-y-0.5 hover:bg-white/10"
                            >
                                Маршрут
                            </a>
                            <button
                                type="button"
                                @click="shareProfile"
                                class="inline-flex items-center gap-2 rounded-full border border-white/30 px-6 py-3 text-sm font-semibold uppercase tracking-wide text-white/90 transition hover:-translate-y-0.5 hover:bg-white/10"
                            >
                                {{ canUseShare ? 'Поділитися профілем' : 'Скопіювати посилання' }}
                            </button>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                <p class="text-xs uppercase tracking-[0.35em] text-white/60">
                                    Основний напрям
                                </p>
                                <p class="mt-2 text-lg font-semibold text-white">
                                    {{ mainService ? mainService.name : 'Послуга не вказана' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                <p class="text-xs uppercase tracking-[0.35em] text-white/60">Місто</p>
                                <p class="mt-2 text-lg font-semibold text-white">
                                    {{ master.city ?? 'Не вказано' }}
                                </p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                                <p class="text-xs uppercase tracking-[0.35em] text-white/60">
                                    Кількість послуг
                                </p>
                                <p class="mt-2 text-lg font-semibold text-white">
                                    {{ services.length }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT CARD -->
                    <div
                        class="rounded-[32px] border border-white/10 bg-white/5 p-8 text-center shadow-2xl shadow-blue-900/40 ring-1 ring-white/10 backdrop-blur-xl"
                    >
                        <div class="mx-auto w-44">
                            <img
                                v-if="master.main_photo"
                                :src="master.main_photo"
                                :alt="master.name"
                                class="h-44 w-44 rounded-[28px] object-cover ring-2 ring-white/40"
                            />
                            <div
                                v-else
                                class="flex h-44 w-44 items-center justify-center rounded-[28px] bg-gradient-to-br from-slate-700 via-blue-600 to-indigo-500 text-5xl font-semibold tracking-wide text-white ring-2 ring-white/20"
                            >
                                {{ masterInitials }}
                            </div>
                        </div>
                        <p v-if="master.description" class="mt-6 text-sm text-white/80 line-clamp-4">
                            {{ master.description }}
                        </p>
                        <p v-else class="mt-6 text-sm text-white/60">
                            Майстер ще не додав короткий опис.
                        </p>

                        <button
                            v-if="canClaim"
                            @click="claimProfile"
                            class="mt-6 w-full rounded-2xl bg-emerald-400 px-4 py-3 text-sm font-semibold uppercase tracking-wide text-slate-900 shadow-md shadow-emerald-500/40 transition hover:bg-emerald-300"
                        >
                            Це я
                        </button>
                        <p
                            v-else
                            class="mt-6 text-xs uppercase tracking-[0.35em] text-emerald-100/80"
                        >
                            Профіль підтверджено
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="relative z-10 -mt-16 pb-20">
            <div class="mx-auto max-w-6xl space-y-10 px-4">
                <!-- CONTACT & SHARE -->
                <section class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                    <!-- CONTACTS -->
                    <div
                        class="rounded-3xl bg-white p-8 text-slate-900 shadow-xl shadow-slate-900/10 ring-1 ring-slate-100"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.35em] text-slate-400">
                                    Способи зв'язку
                                </p>
                                <h2 class="mt-2 text-2xl font-semibold text-slate-900">
                                    Контакти та керування
                                </h2>
                            </div>
                            <span
                                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"
                            >
                                {{
                                    services.length
                                        ? services.length + ' напрямків'
                                        : 'Послуги не додані'
                                }}
                            </span>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <a
                                v-if="telLink"
                                :href="telLink"
                                class="group rounded-2xl border border-slate-200/80 bg-slate-50/80 p-5 transition hover:-translate-y-1 hover:border-slate-300 hover:bg-white"
                            >
                                <div
                                    class="text-sm font-semibold uppercase tracking-wide text-slate-500"
                                >
                                    Подзвонити
                                </div>
                                <p class="mt-2 text-lg font-medium text-slate-900">
                                    {{ master.phone }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Відкриє застосунок телефону
                                </p>
                            </a>
                            <div
                                v-else
                                class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 p-5 text-sm text-slate-500"
                            >
                                Номер телефону відсутній
                            </div>

                            <a
                                v-if="mapsLink"
                                :href="mapsLink"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="group rounded-2xl border border-slate-200/80 bg-slate-50/80 p-5 transition hover:-translate-y-1 hover:border-slate-300 hover:bg-white"
                            >
                                <div
                                    class="text-sm font-semibold uppercase tracking-wide text-slate-500"
                                >
                                    Маршрут
                                </div>
                                <p class="mt-2 text-lg font-medium text-slate-900">
                                    Відкрити у Google Maps
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Окрема вкладка зі схемою проїзду
                                </p>
                            </a>
                            <div
                                v-else
                                class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 p-5 text-sm text-slate-500"
                            >
                                Адреса або координати поки що не вказані
                            </div>
                        </div>

                        <div
                            v-if="master.claim_link"
                            class="mt-6 rounded-2xl border border-blue-100 bg-blue-50/70 p-5 text-blue-900 transition hover:-translate-y-1 hover:border-blue-200 hover:bg-blue-50"
                        >
                            <a :href="master.claim_link" class="flex items-center justify-between">
                                <div>
                                    <p
                                        class="text-xs font-semibold uppercase tracking-wide text-blue-800/90"
                                    >
                                        Відкрити у застосунку
                                    </p>
                                    <p class="text-sm text-blue-800/90">
                                        Перейдіть до Carbeat, щоб керувати профілем
                                    </p>
                                </div>
                                <span
                                    class="rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white"
                                >
                                    Carbeat
                                </span>
                            </a>
                        </div>
                    </div>

                    <!-- SHARE -->
                    <div
                        class="rounded-3xl bg-gradient-to-br from-sky-600 to-indigo-600 p-8 text-white shadow-2xl shadow-slate-900/30 ring-1 ring-white/10"
                    >
                        <div class="flex items-center justify-between">
                            <h3 class="text-2xl font-semibold">Поширити профіль</h3>
                            <span class="text-xs uppercase tracking-[0.35em] text-white/70">
                                QR · LINK
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-white/80">
                            Поділіться посиланням із клієнтом або роздрукуйте QR-код для майстерні.
                        </p>

                        <button
                            type="button"
                            @click="shareProfile"
                            class="mt-6 w-full rounded-2xl bg-white/95 px-4 py-3 text-sm font-semibold uppercase tracking-wide text-slate-900 transition hover:bg-white"
                        >
                            {{ canUseShare ? 'Поділитися' : 'Копіювати посилання' }}
                        </button>

                        <p
                            v-if="copyFeedback"
                            class="mt-3 text-center text-sm text-emerald-200 break-all"
                        >
                            {{ copyFeedback }}
                        </p>

                        <div class="mt-6 flex justify-center">
                            <QrcodeVue
                                v-if="currentUrl"
                                :value="currentUrl"
                                :size="160"
                                class="rounded-2xl bg-white p-4 text-slate-900 shadow-lg shadow-slate-900/30"
                            />
                        </div>
                        <p class="mt-3 text-center text-xs text-white/70">
                            Відскануйте, щоб миттєво перейти до профілю
                        </p>
                    </div>
                </section>

                <!-- ABOUT + SCHEDULE -->
                <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                    <article
                        class="rounded-3xl bg-white p-8 shadow-xl shadow-slate-900/10 ring-1 ring-slate-100"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="h-12 w-0.5 rounded-full bg-gradient-to-b from-blue-500 to-indigo-500"
                            />
                            <div>
                                <p class="text-xs uppercase tracking-[0.35em] text-slate-400">
                                    Про майстра
                                </p>
                                <h2 class="text-2xl font-semibold text-slate-900">
                                    Що важливо знати
                                </h2>
                            </div>
                        </div>
                        <p
                            v-if="master.description"
                            class="mt-5 whitespace-pre-line text-base leading-relaxed text-slate-600"
                        >
                            {{ master.description }}
                        </p>
                        <p v-else class="mt-5 text-sm text-slate-500">
                            Майстер ще не заповнив опис. Зв'яжіться напряму, щоб уточнити деталі.
                        </p>
                    </article>

                    <article
                        class="rounded-3xl bg-white p-8 shadow-xl shadow-slate-900/10 ring-1 ring-slate-100"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.35em] text-slate-400">
                                    Графік
                                </p>
                                <h2 class="text-2xl font-semibold text-slate-900">Режим роботи</h2>
                            </div>
                            <span
                                class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400"
                            >
                                UTC+2
                            </span>
                        </div>

                        <div v-if="workingSchedule.length" class="mt-6 space-y-2">
                            <div
                                v-for="item in workingSchedule"
                                :key="item.day"
                                class="flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-700"
                            >
                                <span class="font-semibold text-slate-900">{{ item.day }}</span>
                                <span>{{ item.value }}</span>
                            </div>
                        </div>
                        <p
                            v-else
                            class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-6 text-sm text-slate-500"
                        >
                            Розклад поки не вказано.
                        </p>
                    </article>
                </section>

                <!-- SERVICES -->
                <section
                    class="rounded-3xl bg-white p-8 shadow-xl shadow-slate-900/10 ring-1 ring-slate-100"
                >
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Послуги</p>
                            <h2 class="text-2xl font-semibold text-slate-900">
                                Компетенції та напрями
                            </h2>
                        </div>
                        <span class="text-xs uppercase tracking-[0.35em] text-slate-400">
                            Carbeat
                        </span>
                    </div>

                    <div v-if="mainService" class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/80 p-5">
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-500">
                            Основний напрям
                        </p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">
                            {{ mainService.name }}
                        </p>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <span
                            v-for="service in secondaryServices"
                            :key="service.id"
                            class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm"
                        >
                            {{ service.name }}
                        </span>
                        <p v-if="!secondaryServices.length && !mainService" class="text-sm text-slate-500">
                            Послуги наразі не додані.
                        </p>
                    </div>
                </section>

                <!-- GALLERY -->
                <section
                    v-if="gallery.length"
                    class="rounded-3xl bg-white p-8 shadow-xl shadow-slate-900/10 ring-1 ring-slate-100"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Портфоліо</p>
                            <h2 class="text-2xl font-semibold text-slate-900">Роботи майстра</h2>
                        </div>
                        <span class="text-sm text-slate-500">{{ gallery.length }} фото</span>
                    </div>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <img
                            v-for="photo in gallery"
                            :key="photo.id"
                            :src="photo.url"
                            :alt="`Робота #${photo.id}`"
                            class="h-48 w-full rounded-2xl object-cover shadow-sm ring-1 ring-slate-100"
                        />
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>
