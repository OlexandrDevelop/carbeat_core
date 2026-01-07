<template>
    <div class="min-h-screen bg-white text-gray-900">
        <Head>
            <title>Автосервіс та ремонт авто: Carbeat - пошук майстрів</title>
            <meta
                name="description"
                content="Швидкий пошук автосервісів (СТО) та майстрів для ремонту авто. Carbeat - це зручна платформа для керування записами, перегляду відгуків та вибору найкращих спеціалістів."
            />
            <meta
                name="keywords"
                content="автосервіс, сто, ремонт авто, пошук майстрів, запис на сто, carbeat"
            />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website" />
            <meta property="og:url" content="https://carbeat.online/" />
            <meta
                property="og:title"
                content="Автосервіс та ремонт авто: Carbeat - пошук майстрів"
            />
            <meta
                property="og:description"
                content="Швидкий пошук автосервісів (СТО) та майстрів для ремонту авто. Carbeat - це зручна платформа для керування записами, перегляду відгуків та вибору найкращих спеціалістів."
            />
            <meta
                property="og:image"
                content="https://carbeat.online/og-image.jpg"
            />
            <meta property="og:site_name" content="Carbeat" />

            <!-- Twitter -->
            <meta property="twitter:card" content="summary_large_image" />
            <meta property="twitter:url" content="https://carbeat.online/" />
            <meta
                property="twitter:title"
                content="Автосервіс та ремонт авто: Carbeat - пошук майстрів"
            />
            <meta
                property="twitter:description"
                content="Швидкий пошук автосервісів (СТО) та майстрів для ремонту авто. Carbeat - це зручна платформа для керування записами, перегляду відгуків та вибору найкращих спеціалістів."
            />
            <meta
                property="twitter:image"
                content="https://carbeat.online/og-image.jpg"
            />
        </Head>

        <header class="border-b border-gray-100">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="grid h-10 w-10 place-items-center rounded-2xl bg-sky-500 font-bold text-slate-900"
                    >
                        CB
                    </div>
                    <div>
                        <div class="text-4xl font-semibold leading-tight">
                            {{ appName }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Платформа для автосервісів та майстрів
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-10">
            <section class="grid gap-10 lg:grid-cols-2 lg:items-center">
                <div>
                    <h1 class="text-4xl font-bold tracking-tight md:text-4xl">
                        Carbeat: автосервіс, ремонт авто та швидкий пошук
                        майстрів
                    </h1>
                    <p class="mt-4 text-lg text-gray-600">
                        Зручний сервіс для клієнтів та СТО: профіль, послуги з
                        ремонту авто, відгуки, робочі години та керування
                        записами на автосервіс.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a
                            :href="termsUrl"
                            class="inline-flex items-center justify-center rounded-full border border-gray-300 px-5 py-3 text-sm font-semibold hover:bg-gray-50"
                        >
                            Умови користування
                        </a>
                        <a
                            :href="privacyUrl"
                            class="inline-flex items-center justify-center rounded-full border border-gray-300 px-5 py-3 text-sm font-semibold hover:bg-gray-50"
                        >
                            Політика конфіденційності
                        </a>
                        <a
                            :href="dataDeletionUrl"
                            class="inline-flex items-center justify-center rounded-full border border-gray-300 px-5 py-3 text-sm font-semibold hover:bg-gray-50"
                        >
                            Видалення даних
                        </a>
                    </div>

                    <div class="mt-8 text-center">
                        <a
                            :href="playMarketUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center rounded-full bg-slate-900 px-8 py-4 text-lg font-semibold text-white hover:bg-slate-800"
                        >
                            <svg
                                class="mr-3 h-7 w-7"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 289.789 289.789"
                            >
                                <g>
                                    <path
                                        style="fill: #349886"
                                        d="M13.424,13.689c-0.692,2.431-1.165,5.053-1.165,8.048v246.356c0,2.95,0.473,5.544,1.138,7.948 l138.267-130.993C151.664,145.047,13.424,13.689,13.424,13.689z"
                                    ></path>
                                    <path
                                        style="fill: #3db39e"
                                        d="M205.621,93.921L44.185,4.121C37.749,0.207,31.413-0.785,26.06,0.58l138.85,131.931 C164.91,132.511,205.621,93.921,205.621,93.921z"
                                    ></path>
                                    <path
                                        style="fill: #f4b459"
                                        d="M265.142,127.031l-43.088-23.97l-44.135,41.804l44.954,42.733l41.923-23.023 C285.261,152.913,277.796,134.141,265.142,127.031z"
                                    ></path>
                                    <path
                                        style="fill: #e2574c"
                                        d="M25.65,289.095c5.435,1.52,11.926,0.61,18.526-3.405l161.928-88.907L164.655,157.4 C164.655,157.401,25.65,289.095,25.65,289.095z"
                                    ></path>
                                </g>
                            </svg>
                            Завантажити з Google Play
                        </a>
                    </div>

                    <p class="mt-6 text-sm text-gray-500">
                        Потрібне публічне посилання на сторінку майстра?
                        Використовуйте формат
                        <code class="rounded bg-gray-100 px-1 py-0.5"
                            >/m/&lt;slug&gt;</code
                        >.
                    </p>
                </div>

                <div
                    class="rounded-3xl border border-gray-200 bg-gradient-to-br from-slate-900 to-slate-800 p-8 text-white"
                >
                    <h2 class="text-xl font-semibold">
                        Що є в додатку Carbeat для СТО та клієнтів
                    </h2>
                    <ul class="mt-4 space-y-3 text-sm text-slate-200">
                        <li class="flex gap-3">
                            <span
                                class="mt-1 inline-block h-2 w-2 rounded-full bg-sky-400"
                            ></span>
                            Публічні профілі майстрів із послугами та контактами
                            для ремонту авто
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="mt-1 inline-block h-2 w-2 rounded-full bg-sky-400"
                            ></span>
                            Посилання для підтвердження профілю (claim)
                        </li>
                    </ul>

                    <div
                        class="mt-8 rounded-2xl border border-white/10 bg-white/5 p-5"
                    >
                        <div class="text-sm font-semibold">Для майстрів</div>
                        <div class="mt-1 text-sm text-slate-200">
                            Створіть та наповніть профіль, додавайте фото,
                            працюйте з відгуками та керуйте доступністю для
                            запису на СТО.
                        </div>
                    </div>

                    <div
                        class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-5"
                    >
                        <div class="text-sm font-semibold">Для клієнтів</div>
                        <div class="mt-1 text-sm text-slate-200">
                            Знаходьте перевірених майстрів для ремонту авто,
                            переглядайте послуги й контакти та обирайте зручний
                            час для запису на автосервіс.
                        </div>
                    </div>
                </div>
            </section>

            <footer
                class="mt-16 flex flex-col gap-3 border-t border-gray-100 pt-8 text-sm text-gray-500 md:flex-row md:items-center md:justify-between"
            >
                <div>© 2026 {{ appName }}</div>
                <div class="flex gap-4">
                    <a :href="route('landing')" class="hover:text-gray-700"
                        >Головна</a
                    >
                    <a :href="termsUrl" class="hover:text-gray-700">Умови</a>
                    <a :href="privacyUrl" class="hover:text-gray-700"
                        >Конфіденційність</a
                    >
                    <a :href="dataDeletionUrl" class="hover:text-gray-700"
                        >Видалення даних</a
                    >
                </div>
            </footer>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    appName: string;
    adminUrl: string;
    termsUrl: string;
    privacyUrl: string;
    dataDeletionUrl: string;
    playMarketUrl: string;
    structuredData: object;
}>();
</script>
