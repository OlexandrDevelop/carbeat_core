<script setup lang="ts">
import type { SeoContentPayload } from '@/types/guest-map';

defineProps<{
    seoContent: SeoContentPayload;
    isFloxcity: boolean;
    buildMasterPath: (slug?: string | null) => string;
}>();
</script>

<template>
    <section class="border-t border-slate-200 bg-white">
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

            <div
                class="grid gap-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.8fr)]"
            >
                <div>
                    <h1
                        class="text-3xl font-semibold tracking-tight text-slate-900"
                    >
                        {{ seoContent.title }}
                    </h1>
                    <p
                        v-if="seoContent.intro"
                        class="mt-3 max-w-3xl text-base leading-7 text-slate-600"
                    >
                        {{ seoContent.intro }}
                    </p>

                    <div
                        v-if="seoContent.stats.length"
                        class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        <div
                            v-for="stat in seoContent.stats"
                            :key="stat.label"
                            class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3"
                        >
                            <div
                                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                            >
                                {{ stat.label }}
                            </div>
                            <div
                                class="mt-1 text-lg font-semibold text-slate-900"
                            >
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

                    <div v-if="seoContent.serviceLinks.length" class="mt-8">
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
                                :href="buildMasterPath(master.slug)"
                                class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 hover:shadow-sm"
                            >
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div>
                                        <div
                                            class="text-base font-semibold text-slate-900"
                                        >
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
                            <div v-for="item in seoContent.faq" :key="item.q">
                                <h3
                                    class="text-sm font-semibold text-slate-900"
                                >
                                    {{ item.q }}
                                </h3>
                                <p
                                    class="mt-1 text-sm leading-6 text-slate-600"
                                >
                                    {{ item.a }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
