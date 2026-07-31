<script setup lang="ts">
import type { Flavor, SeoContentPayload, SeoPayload } from '@/types/guest-map';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    flavor?: Flavor;
    mapPath?: string;
    seo: SeoPayload;
    content: SeoContentPayload;
}>();

const flavor = computed<Flavor>(() => props.flavor ?? 'carbeat');
const isFloxcity = computed(() => flavor.value === 'floxcity');
const brandName = computed(() => (isFloxcity.value ? 'Floxcity' : 'Carbeat'));
const logoInitials = computed(() => (isFloxcity.value ? 'FC' : 'CB'));
const mapHref = computed(() => props.mapPath ?? '/');
const isIndex = computed(() => props.content.type === 'guide_index');

const ctaLabel = computed(() =>
    isFloxcity.value
        ? 'Знайти майстра на карті Floxcity'
        : 'Знайти СТО на карті Carbeat',
);

const accentSolid = computed(() =>
    isFloxcity.value
        ? 'bg-emerald-600 hover:bg-emerald-700'
        : 'bg-sky-600 hover:bg-sky-700',
);
const accentText = computed(() =>
    isFloxcity.value
        ? 'text-emerald-700 hover:text-emerald-800'
        : 'text-sky-700 hover:text-sky-800',
);
const accentBadge = computed(() =>
    isFloxcity.value
        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
        : 'bg-sky-50 text-sky-700 ring-sky-200',
);
const accentDot = computed(() =>
    isFloxcity.value ? 'bg-emerald-500' : 'bg-sky-500',
);

const structuredDataJson = computed(() =>
    props.seo.structuredData ? JSON.stringify(props.seo.structuredData) : '',
);
</script>

<template>
    <div class="min-h-screen bg-white text-gray-900">
        <Head>
            <title>{{ seo.title }}</title>
            <meta name="description" :content="seo.description" />
            <meta name="robots" :content="seo.robots ?? 'index, follow'" />
            <link rel="canonical" :href="seo.canonical" />
            <meta property="og:type" content="article" />
            <meta property="og:url" :content="seo.canonical" />
            <meta property="og:title" :content="seo.title" />
            <meta property="og:description" :content="seo.description" />
            <meta property="og:site_name" :content="brandName" />
            <meta
                v-if="seo.ogImage"
                property="og:image"
                :content="seo.ogImage"
            />
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" :content="seo.title" />
            <meta name="twitter:description" :content="seo.description" />
            <component
                :is="'script'"
                v-if="structuredDataJson"
                type="application/ld+json"
                v-text="structuredDataJson"
            />
        </Head>

        <header
            class="sticky top-0 z-50 border-b border-gray-100 bg-white/90 backdrop-blur-sm"
        >
            <nav
                class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4"
                aria-label="Головна навігація"
            >
                <a :href="mapHref" class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl text-sm font-bold text-white"
                        :class="accentSolid"
                    >
                        {{ logoInitials }}
                    </div>
                    <span class="text-xl font-bold tracking-tight">{{
                        brandName
                    }}</span>
                </a>
                <a
                    :href="mapHref"
                    class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-white transition"
                    :class="accentSolid"
                >
                    Знайти майстра
                    <span aria-hidden="true">→</span>
                </a>
            </nav>
        </header>

        <main class="mx-auto max-w-3xl px-6 py-10 sm:py-14">
            <nav
                v-if="content.breadcrumbs.length"
                class="mb-4 flex flex-wrap items-center gap-2 text-sm text-gray-500"
                aria-label="Breadcrumb"
            >
                <template
                    v-for="(crumb, index) in content.breadcrumbs"
                    :key="`${crumb.href}-${index}`"
                >
                    <a
                        :href="crumb.href"
                        class="transition hover:text-gray-900"
                        >{{ crumb.label }}</a
                    >
                    <span
                        v-if="index < content.breadcrumbs.length - 1"
                        aria-hidden="true"
                        >/</span
                    >
                </template>
            </nav>

            <span
                class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset"
                :class="accentBadge"
            >
                <span class="h-1.5 w-1.5 rounded-full" :class="accentDot" />
                {{ isIndex ? 'Гайди' : 'Гайд' }}
            </span>

            <h1
                class="mt-4 text-3xl font-extrabold leading-tight tracking-tight text-gray-900 sm:text-4xl"
            >
                {{ content.title }}
            </h1>
            <p
                v-if="content.intro"
                class="mt-4 text-lg leading-relaxed text-gray-600"
            >
                {{ content.intro }}
            </p>

            <!-- Prominent CTA -->
            <a
                :href="mapHref"
                class="mt-8 flex items-center justify-between gap-4 rounded-2xl px-6 py-5 text-white shadow-lg transition hover:opacity-95 active:scale-[.99]"
                :class="accentSolid"
            >
                <span class="text-base font-semibold sm:text-lg">{{
                    ctaLabel
                }}</span>
                <span class="text-2xl" aria-hidden="true">→</span>
            </a>

            <div v-if="content.sections?.length" class="mt-10 space-y-8">
                <section
                    v-for="section in content.sections"
                    :key="section.heading"
                >
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ section.heading }}
                    </h2>
                    <p class="mt-2 text-base leading-7 text-gray-600">
                        {{ section.body }}
                    </p>
                </section>
            </div>

            <div v-if="content.relatedLinks.length" class="mt-10">
                <h2 class="text-xl font-semibold text-gray-900">
                    {{ isIndex ? 'Усі гайди' : 'Дивіться також' }}
                </h2>
                <div
                    class="mt-4 grid gap-3"
                    :class="isIndex ? 'sm:grid-cols-2' : ''"
                >
                    <a
                        v-for="link in content.relatedLinks"
                        :key="link.href"
                        :href="link.href"
                        class="rounded-2xl border border-gray-200 p-4 font-medium text-gray-900 transition hover:border-gray-300 hover:shadow-sm"
                    >
                        {{ link.label }}
                    </a>
                </div>
            </div>

            <div
                v-if="content.faq.length"
                class="mt-10 rounded-2xl border border-gray-200 bg-gray-50 p-6"
            >
                <h2 class="text-lg font-semibold text-gray-900">
                    Питання, що часто ставлять
                </h2>
                <div class="mt-4 space-y-4">
                    <div v-for="item in content.faq" :key="item.q">
                        <h3 class="text-sm font-semibold text-gray-900">
                            {{ item.q }}
                        </h3>
                        <p class="mt-1 text-sm leading-6 text-gray-600">
                            {{ item.a }}
                        </p>
                    </div>
                </div>
            </div>

            <a
                :href="mapHref"
                class="mt-12 flex items-center justify-center gap-2 rounded-full px-6 py-3.5 text-base font-semibold text-white shadow-lg transition hover:opacity-95 active:scale-[.99]"
                :class="accentSolid"
            >
                {{ ctaLabel }}
                <span aria-hidden="true">→</span>
            </a>
        </main>

        <footer class="border-t border-gray-100 py-8">
            <div
                class="mx-auto max-w-3xl px-6 text-center text-sm text-gray-500"
            >
                <a :href="mapHref" class="font-medium" :class="accentText"
                    >← Повернутись на карту {{ brandName }}</a
                >
            </div>
        </footer>
    </div>
</template>
