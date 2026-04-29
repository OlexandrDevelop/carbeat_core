<template>
    <div class="min-h-screen bg-white text-gray-900">
        <Head>
            <title>
                Carbeat — Пошук автосервісів та майстрів для ремонту авто
            </title>
            <meta
                name="description"
                content="Знайдіть перевірений автосервіс (СТО) або майстра для ремонту авто поряд з вами. Відгуки, послуги, контакти — все в безкоштовному додатку Carbeat."
            />
            <meta
                name="keywords"
                content="автосервіс, СТО, ремонт авто, шиномонтаж, розвал-сход, заміна масла, запис на СТО, carbeat, пошук автосервісу, Україна"
            />
            <link rel="canonical" href="https://carbeat.online/" />
            <meta name="robots" content="index, follow" />
            <meta property="og:type" content="website" />
            <meta property="og:url" content="https://carbeat.online/" />
            <meta
                property="og:title"
                content="Carbeat — Пошук автосервісів та майстрів для ремонту авто"
            />
            <meta
                property="og:description"
                content="Знайдіть перевірений автосервіс (СТО) або майстра для ремонту авто поряд з вами. Відгуки, послуги, контакти — безкоштовно."
            />
            <meta
                property="og:image"
                content="https://carbeat.online/og-image.jpg"
            />
            <meta property="og:site_name" content="Carbeat" />
            <meta property="og:locale" content="uk_UA" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:url" content="https://carbeat.online/" />
            <meta
                name="twitter:title"
                content="Carbeat — Пошук автосервісів та майстрів для ремонту авто"
            />
            <meta
                name="twitter:description"
                content="Знайдіть перевірений автосервіс або майстра для ремонту авто поряд з вами."
            />
            <meta
                name="twitter:image"
                content="https://carbeat.online/og-image.jpg"
            />
            <component
                :is="'script'"
                type="application/ld+json"
                v-text="jsonLd"
            />
        </Head>

        <!-- HEADER -->
        <header
            class="sticky top-0 z-50 border-b border-gray-100 bg-white/90 backdrop-blur-sm"
        >
            <nav
                class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4"
                aria-label="Головна навігація"
            >
                <a href="/" class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-600 text-sm font-bold text-white"
                    >
                        CB
                    </div>
                    <span class="text-xl font-bold tracking-tight">{{
                        appName
                    }}</span>
                </a>
                <div
                    class="hidden gap-6 text-sm font-medium text-gray-600 sm:flex"
                >
                    <a href="#features" class="transition hover:text-sky-600"
                        >Можливості</a
                    >
                    <a
                        href="#how-it-works"
                        class="transition hover:text-sky-600"
                        >Як це працює</a
                    >
                    <a href="#faq" class="transition hover:text-sky-600">FAQ</a>
                </div>
                <a
                    :href="playMarketUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="hidden rounded-full bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700 sm:inline-flex"
                >
                    Завантажити
                </a>
            </nav>
        </header>

        <main>
            <!-- HERO -->
            <section
                id="hero"
                class="relative overflow-hidden bg-white py-16 sm:py-24"
                aria-label="Головна секція"
            >
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_70%_-10%,theme(colors.sky.50),transparent)]"
                ></div>
                <div class="relative mx-auto max-w-6xl px-6">
                    <div class="grid items-center gap-12 lg:grid-cols-2">
                        <!-- Left -->
                        <div>
                            <span
                                class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-4 py-1.5 text-sm font-medium text-sky-700 ring-1 ring-inset ring-sky-200"
                            >
                                🔧 Пошук автосервісів в Україні
                            </span>

                            <h1
                                class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-gray-900 sm:text-5xl lg:text-6xl"
                            >
                                Знайдіть
                                <span class="text-sky-600">автосервіс</span>
                                поряд з вами
                            </h1>

                            <p
                                class="mt-5 text-lg leading-relaxed text-gray-600 sm:text-xl"
                            >
                                Carbeat — безкоштовна платформа для пошуку
                                перевірених СТО та майстрів для ремонту авто по
                                всій Україні.
                            </p>

                            <p
                                class="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-emerald-500"
                                    aria-hidden="true"
                                ></span>
                                Майстер вільний прямо зараз
                            </p>

                            <ul
                                class="mt-6 space-y-2 text-sm text-gray-600"
                                aria-label="Переваги"
                            >
                                <li class="flex items-center gap-2">
                                    <svg
                                        class="h-5 w-5 flex-shrink-0 text-sky-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                    Відгуки клієнтів та рейтинги СТО
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg
                                        class="h-5 w-5 flex-shrink-0 text-sky-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                    Пошук за геолокацією — сервіси поряд
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg
                                        class="h-5 w-5 flex-shrink-0 text-sky-500"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                    Безкоштовно для клієнтів
                                </li>
                            </ul>

                            <!-- Google Play CTA -->
                            <div class="mt-8">
                                <a
                                    :href="playMarketUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-3 rounded-2xl bg-gray-900 px-6 py-3.5 text-white shadow-lg transition hover:bg-gray-700 active:scale-[.98]"
                                >
                                    <svg
                                        class="h-7 w-7 flex-shrink-0"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 289.789 289.789"
                                        aria-hidden="true"
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
                                    <div class="text-left">
                                        <div class="text-xs text-gray-300">
                                            Завантажити в
                                        </div>
                                        <div
                                            class="text-base font-semibold leading-none"
                                        >
                                            Google Play
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div
                                class="mt-6 flex flex-wrap gap-4 text-sm text-gray-400"
                            >
                                <a
                                    :href="termsUrl"
                                    class="underline underline-offset-2 hover:text-gray-600"
                                    >Умови використання</a
                                >
                                <a
                                    :href="privacyUrl"
                                    class="underline underline-offset-2 hover:text-gray-600"
                                    >Конфіденційність</a
                                >
                                <a
                                    :href="dataDeletionUrl"
                                    class="underline underline-offset-2 hover:text-gray-600"
                                    >Видалення даних</a
                                >
                            </div>
                        </div>

                        <!-- Right: Phone Mockup -->
                        <div
                            class="flex justify-center lg:justify-end"
                            aria-hidden="true"
                        >
                            <div class="relative">
                                <div
                                    class="absolute -inset-6 rounded-[56px] bg-sky-100 opacity-60 blur-3xl"
                                ></div>
                                <div
                                    class="relative mx-auto w-[264px] overflow-hidden rounded-[40px] border-[8px] border-gray-800 bg-gray-900 shadow-2xl ring-1 ring-gray-700"
                                >
                                    <!-- Status bar -->
                                    <div
                                        class="flex items-center justify-between bg-gray-900 px-5 py-2"
                                    >
                                        <span
                                            class="text-xs font-semibold text-white"
                                            >9:41</span
                                        >
                                        <div class="flex items-center gap-1">
                                            <div
                                                class="h-2 w-2 rounded-full bg-sky-400"
                                            ></div>
                                            <div
                                                class="h-2 w-5 rounded-full bg-white/30"
                                            ></div>
                                            <div
                                                class="h-2 w-3 rounded-full bg-white/30"
                                            ></div>
                                        </div>
                                    </div>
                                    <!-- App bar -->
                                    <div class="bg-slate-800 px-5 py-3">
                                        <p class="text-sm font-bold text-white">
                                            Carbeat
                                        </p>
                                        <p class="text-xs text-sky-300">
                                            Автосервіси поряд
                                        </p>
                                    </div>
                                    <!-- Search bar -->
                                    <div class="bg-slate-900 px-3 pb-3">
                                        <div
                                            class="rounded-lg bg-white/10 px-3 py-2 text-xs text-white/50"
                                        >
                                            🔍 Послуга або СТО...
                                        </div>
                                    </div>
                                    <!-- Cards -->
                                    <div class="space-y-2 bg-gray-50 p-3">
                                        <div
                                            class="rounded-xl bg-white p-3 shadow-sm"
                                        >
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <div
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-sky-100 text-sm font-bold text-sky-700"
                                                >
                                                    АС
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div
                                                        class="truncate text-xs font-semibold text-gray-900"
                                                    >
                                                        АвтоСервіс Плюс
                                                    </div>
                                                    <div
                                                        class="text-xs text-gray-500"
                                                    >
                                                        Шиномонтаж · 0.4 км
                                                    </div>
                                                </div>
                                                <div
                                                    class="text-xs font-bold text-yellow-500"
                                                >
                                                    ★ 4.7
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="rounded-xl bg-white p-3 shadow-sm"
                                        >
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <div
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-orange-100 text-sm font-bold text-orange-700"
                                                >
                                                    МС
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div
                                                        class="truncate text-xs font-semibold text-gray-900"
                                                    >
                                                        Майстер Стас
                                                    </div>
                                                    <div
                                                        class="text-xs text-gray-500"
                                                    >
                                                        Розвал-сход · 0.8 км
                                                    </div>
                                                    <div
                                                        class="mt-1 text-[11px] font-semibold text-emerald-600"
                                                    >
                                                        Майстер вільний прямо
                                                        зараз
                                                    </div>
                                                </div>
                                                <div
                                                    class="text-xs font-bold text-yellow-500"
                                                >
                                                    ★ 4.9
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="rounded-xl bg-white p-3 shadow-sm"
                                        >
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <div
                                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-700"
                                                >
                                                    СТО
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div
                                                        class="truncate text-xs font-semibold text-gray-900"
                                                    >
                                                        СТО Кравченко
                                                    </div>
                                                    <div
                                                        class="text-xs text-gray-500"
                                                    >
                                                        Діагностика · 1.1 км
                                                    </div>
                                                </div>
                                                <div
                                                    class="text-xs font-bold text-yellow-500"
                                                >
                                                    ★ 4.8
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="h-12 rounded-xl bg-white/50"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- STATS -->
            <section class="bg-slate-900" aria-label="Статистика платформи">
                <div class="mx-auto max-w-6xl px-6 py-14">
                    <div
                        class="grid grid-cols-1 gap-10 text-center text-white sm:grid-cols-3"
                    >
                        <div>
                            <div class="text-5xl font-extrabold text-sky-400">
                                1000+
                            </div>
                            <div class="mt-2 text-slate-300">
                                Автосервісів та майстрів
                            </div>
                        </div>
                        <div>
                            <div class="text-5xl font-extrabold text-sky-400">
                                10+
                            </div>
                            <div class="mt-2 text-slate-300">
                                Видів послуг для авто
                            </div>
                        </div>
                        <div>
                            <div class="text-5xl font-extrabold">🇺🇦</div>
                            <div class="mt-2 text-slate-300">
                                По всій Україні
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SERVICE CATEGORIES -->
            <section class="py-20" aria-labelledby="categories-heading">
                <div class="mx-auto max-w-6xl px-6">
                    <h2
                        id="categories-heading"
                        class="text-center text-3xl font-bold text-gray-900 sm:text-4xl"
                    >
                        Послуги для авто в Carbeat
                    </h2>
                    <p class="mt-4 text-center text-gray-600">
                        Знаходьте фахівців з будь-якого виду ремонту та
                        обслуговування авто
                    </p>
                    <div class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div
                            v-for="cat in categories"
                            :key="cat.name"
                            class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-sky-300 hover:shadow-md"
                        >
                            <span class="text-2xl">{{ cat.emoji }}</span>
                            <span class="text-sm font-medium text-gray-800">{{
                                cat.name
                            }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FEATURES -->
            <section
                id="features"
                class="bg-gray-50 py-20"
                aria-labelledby="features-heading"
            >
                <div class="mx-auto max-w-6xl px-6">
                    <div class="text-center">
                        <h2
                            id="features-heading"
                            class="text-3xl font-bold text-gray-900 sm:text-4xl"
                        >
                            Чому обирають Carbeat
                        </h2>
                        <p class="mt-4 text-gray-600">
                            Все необхідне для власників авто та автосервісів — в
                            одному безкоштовному додатку
                        </p>
                    </div>
                    <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="feat in features"
                            :key="feat.title"
                            class="rounded-2xl bg-white p-6 shadow-sm transition hover:shadow-md"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-50 text-2xl"
                            >
                                {{ feat.icon }}
                            </div>
                            <h3
                                class="mt-4 text-base font-semibold text-gray-900"
                            >
                                {{ feat.title }}
                            </h3>
                            <p
                                class="mt-2 text-sm leading-relaxed text-gray-600"
                            >
                                {{ feat.desc }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- HOW IT WORKS -->
            <section
                id="how-it-works"
                class="py-20"
                aria-labelledby="how-heading"
            >
                <div class="mx-auto max-w-6xl px-6">
                    <div class="text-center">
                        <h2
                            id="how-heading"
                            class="text-3xl font-bold text-gray-900 sm:text-4xl"
                        >
                            Як знайти автосервіс через Carbeat
                        </h2>
                        <p class="mt-4 text-gray-600">
                            Три прості кроки — і ваше авто вже у надійних руках
                        </p>
                    </div>
                    <div class="relative mt-14 grid gap-10 sm:grid-cols-3">
                        <div
                            class="absolute left-1/2 top-8 hidden h-0.5 w-2/3 -translate-x-1/2 bg-sky-100 sm:block"
                            aria-hidden="true"
                        ></div>
                        <div
                            v-for="(step, i) in steps"
                            :key="step.title"
                            class="relative text-center"
                        >
                            <div
                                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-900 text-xl font-extrabold text-white shadow-lg"
                            >
                                {{ i + 1 }}
                            </div>
                            <h3
                                class="mt-5 text-lg font-semibold text-gray-900"
                            >
                                {{ step.title }}
                            </h3>
                            <p
                                class="mt-2 text-sm leading-relaxed text-gray-600"
                            >
                                {{ step.desc }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FOR MASTERS / SERVICE OWNERS -->
            <section
                class="bg-slate-900 py-20 text-white"
                aria-label="Для власників СТО"
            >
                <div class="mx-auto max-w-6xl px-6">
                    <div class="grid items-center gap-12 lg:grid-cols-2">
                        <div>
                            <span
                                class="inline-block rounded-full bg-sky-500/20 px-3 py-1 text-sm font-medium text-sky-300"
                            >
                                Для власників СТО та майстрів
                            </span>
                            <h2 class="mt-4 text-3xl font-bold sm:text-4xl">
                                Залучайте нових клієнтів через Carbeat
                            </h2>
                            <p class="mt-4 text-slate-300">
                                Створіть профіль свого автосервісу, вкажіть
                                перелік послуг та ціни. Клієнти знайдуть вас
                                через пошук за геолокацією.
                            </p>
                            <ul class="mt-6 space-y-3 text-slate-300">
                                <li
                                    v-for="benefit in masterBenefits"
                                    :key="benefit"
                                    class="flex items-start gap-3"
                                >
                                    <svg
                                        class="mt-0.5 h-5 w-5 flex-shrink-0 text-sky-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                    {{ benefit }}
                                </li>
                            </ul>
                            <div class="mt-8">
                                <a
                                    :href="playMarketUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-6 py-3 text-sm font-semibold text-white shadow transition hover:bg-sky-400"
                                >
                                    Додати свій автосервіс
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3"
                                        />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <div class="grid grid-cols-2 gap-4 text-center">
                                <div
                                    class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10"
                                >
                                    <div
                                        class="text-4xl font-extrabold text-sky-400"
                                    >
                                        1000+
                                    </div>
                                    <div class="mt-1 text-sm text-slate-400">
                                        автосервісів
                                    </div>
                                </div>
                                <div
                                    class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10"
                                >
                                    <div
                                        class="text-4xl font-extrabold text-sky-400"
                                    >
                                        4.7★
                                    </div>
                                    <div class="mt-1 text-sm text-slate-400">
                                        середній рейтинг
                                    </div>
                                </div>
                                <div
                                    class="col-span-2 rounded-2xl bg-white/5 p-6 ring-1 ring-white/10"
                                >
                                    <div class="text-4xl font-extrabold">
                                        🇺🇦
                                    </div>
                                    <div class="mt-2 text-sm text-slate-400">
                                        Вся Україна — один додаток
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FAQ -->
            <section
                id="faq"
                class="bg-gray-50 py-20"
                aria-labelledby="faq-heading"
            >
                <div class="mx-auto max-w-3xl px-6">
                    <div class="text-center">
                        <h2
                            id="faq-heading"
                            class="text-3xl font-bold text-gray-900 sm:text-4xl"
                        >
                            Часті запитання про Carbeat
                        </h2>
                        <p class="mt-4 text-gray-600">
                            Відповіді на найпопулярніші питання
                        </p>
                    </div>
                    <div class="mt-10 space-y-3">
                        <details
                            v-for="faq in faqs"
                            :key="faq.q"
                            class="group rounded-2xl bg-white shadow-sm"
                        >
                            <summary
                                class="flex cursor-pointer list-none items-center justify-between px-6 py-5 font-semibold text-gray-900 [&::-webkit-details-marker]:hidden"
                            >
                                {{ faq.q }}
                                <svg
                                    class="h-5 w-5 flex-shrink-0 text-gray-400 transition-transform duration-200 group-open:rotate-180"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </summary>
                            <p
                                class="px-6 pb-5 text-sm leading-relaxed text-gray-600"
                            >
                                {{ faq.a }}
                            </p>
                        </details>
                    </div>
                </div>
            </section>

            <!-- DOWNLOAD CTA -->
            <section class="py-24" aria-labelledby="download-heading">
                <div class="mx-auto max-w-4xl px-6 text-center">
                    <h2
                        id="download-heading"
                        class="text-3xl font-bold text-gray-900 sm:text-4xl"
                    >
                        Завантажте Carbeat вже сьогодні
                    </h2>
                    <p class="mt-4 text-lg text-gray-600">
                        Безкоштовно для Android. Знайдіть потрібний автосервіс
                        за лічені секунди.
                    </p>
                    <div
                        class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row"
                    >
                        <a
                            :href="playMarketUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-3 rounded-2xl bg-gray-900 px-7 py-4 text-white shadow-xl transition hover:bg-gray-700 active:scale-[.98]"
                        >
                            <svg
                                class="h-8 w-8 flex-shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 289.789 289.789"
                                aria-hidden="true"
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
                            <div class="text-left">
                                <div class="text-xs text-gray-300">
                                    Завантажити в
                                </div>
                                <div class="text-lg font-bold leading-none">
                                    Google Play
                                </div>
                            </div>
                        </a>
                    </div>
                    <p class="mt-6 text-sm text-gray-400">
                        Безкоштовно · Android · Вся Україна
                    </p>
                </div>
            </section>
        </main>

        <!-- FOOTER -->
        <footer class="border-t border-gray-100 py-10" role="contentinfo">
            <div class="mx-auto max-w-6xl px-6">
                <div
                    class="flex flex-col items-center gap-6 sm:flex-row sm:justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-600 text-xs font-bold text-white"
                        >
                            CB
                        </div>
                        <span class="font-semibold text-gray-900">{{
                            appName
                        }}</span>
                    </div>
                    <nav
                        class="flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm text-gray-500"
                        aria-label="Підвал навігація"
                    >
                        <a :href="termsUrl" class="hover:text-gray-700"
                            >Умови використання</a
                        >
                        <a :href="privacyUrl" class="hover:text-gray-700"
                            >Конфіденційність</a
                        >
                        <a :href="dataDeletionUrl" class="hover:text-gray-700"
                            >Видалення даних</a
                        >
                    </nav>
                    <p class="text-sm text-gray-400">
                        © {{ year }} {{ appName }}
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    appName: string;
    adminUrl: string;
    termsUrl: string;
    privacyUrl: string;
    dataDeletionUrl: string;
    playMarketUrl: string;
    structuredData: object;
}>();

const year = new Date().getFullYear();

const categories = [
    { emoji: '🔧', name: 'Ремонт двигуна' },
    { emoji: '🛞', name: 'Шиномонтаж' },
    { emoji: '⚙️', name: 'Розвал-сход' },
    { emoji: '🛢️', name: 'Заміна масла' },
    { emoji: '🚗', name: 'Кузовний ремонт' },
    { emoji: '💡', name: 'Електрика авто' },
    { emoji: '❄️', name: 'Кондиціонер авто' },
    { emoji: '🔍', name: 'Діагностика' },
];

const features = [
    {
        icon: '🗺️',
        title: 'Пошук за геолокацією',
        desc: 'Знаходьте автосервіси та СТО поряд з вашим розташуванням на інтерактивній карті.',
    },
    {
        icon: '⭐',
        title: 'Відгуки та рейтинги СТО',
        desc: 'Реальні відгуки клієнтів та рейтинги допоможуть обрати перевірений автосервіс.',
    },
    {
        icon: '📋',
        title: 'Список послуг та ціни',
        desc: 'Дивіться повний перелік послуг та орієнтовні ціни прямо в профілі автосервісу.',
    },
    {
        icon: '📞',
        title: 'Прямий контакт',
        desc: "Телефон та адреса СТО — зв'яжіться напряму без зайвих посередників.",
    },
    {
        icon: '🕐',
        title: 'Графік роботи',
        desc: 'Дивіться актуальний графік роботи автосервісу та обирайте зручний час.',
    },
    {
        icon: '🆓',
        title: 'Повністю безкоштовно',
        desc: 'Carbeat безкоштовний для клієнтів. Пошук та перегляд профілів СТО — без платежів.',
    },
];

const steps = [
    {
        title: 'Завантажте додаток',
        desc: 'Безкоштовно встановіть Carbeat з Google Play на свій Android-смартфон.',
    },
    {
        title: 'Знайдіть автосервіс',
        desc: 'Оберіть послугу та знайдіть перевірені СТО поряд з вами — з відгуками та цінами.',
    },
    {
        title: "Зв'яжіться та запишіться",
        desc: 'Зателефонуйте напряму або запишіться на зручний для вас час.',
    },
];

const masterBenefits = [
    'Безкоштовний профіль з послугами та цінами',
    'Клієнти знаходять вас через пошук за геолокацією',
    'Відгуки та рейтинг для підвищення довіри',
    'Управління своєю доступністю та графіком роботи',
];

const faqs = [
    {
        q: 'Як знайти автосервіс через Carbeat?',
        a: 'Завантажте додаток Carbeat, дозвольте доступ до геолокації — система покаже найближчі СТО та майстрів. Можна фільтрувати за послугою, рейтингом та відстанню.',
    },
    {
        q: 'Чи безкоштовне використання Carbeat для клієнтів?',
        a: 'Так, Carbeat повністю безкоштовний для власників авто. Ви можете шукати СТО, переглядати відгуки та контакти без будь-яких платежів.',
    },
    {
        q: 'Які послуги для авто можна знайти в Carbeat?',
        a: 'В Carbeat представлені шиномонтажі, майстри з розвалу-сходу, заміни масла, ремонту двигуна, кузовного ремонту, електрики авто, обслуговування кондиціонерів та загальної діагностики.',
    },
    {
        q: 'Як додати свій автосервіс в Carbeat?',
        a: 'Завантажте додаток Carbeat, створіть профіль свого СТО, вкажіть послуги та ціни, додайте фото — і клієнти почнуть знаходити вас через пошук за геолокацією.',
    },
];

const jsonLd = computed(() =>
    JSON.stringify([
        {
            '@context': 'https://schema.org',
            '@type': 'MobileApplication',
            name: 'Carbeat',
            operatingSystem: 'ANDROID',
            applicationCategory: 'AutomotiveApplication',
            offers: { '@type': 'Offer', price: '0', priceCurrency: 'UAH' },
            aggregateRating: {
                '@type': 'AggregateRating',
                ratingValue: '4.5',
                ratingCount: '123',
            },
            url: 'https://carbeat.online/',
            description:
                'Безкоштовна платформа для пошуку автосервісів (СТО) та майстрів для ремонту авто по всій Україні.',
        },
        {
            '@context': 'https://schema.org',
            '@type': 'FAQPage',
            mainEntity: faqs.map((f) => ({
                '@type': 'Question',
                name: f.q,
                acceptedAnswer: { '@type': 'Answer', text: f.a },
            })),
        },
        {
            '@context': 'https://schema.org',
            '@type': 'Organization',
            name: 'Carbeat',
            url: 'https://carbeat.online/',
            contactPoint: {
                '@type': 'ContactPoint',
                email: 'admin@carbeat.online',
                contactType: 'customer support',
            },
        },
    ]),
);
</script>
