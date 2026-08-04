<template>
    <div
        class="flex min-h-screen items-center justify-center bg-white text-gray-900"
    >
        <div class="text-center">
            <h1 class="text-9xl font-bold text-emerald-500">{{ status }}</h1>
            <p class="mt-4 text-2xl font-light text-gray-800 md:text-3xl">
                {{ title }}
            </p>
            <p class="text-md mt-2 text-gray-600">{{ description }}</p>
            <a
                :href="homeHref"
                class="mt-8 inline-block rounded-full bg-slate-900 px-8 py-4 text-lg font-semibold text-white hover:bg-slate-800"
            >
                Повернутись на головну
            </a>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue';
import type { route as routeFn } from 'ziggy-js';

const props = defineProps<{
    status: number;
}>();

// Error pages can render before HandleInertiaRequests has shared the Ziggy
// config (e.g. when an earlier middleware throws), in which case app.ts/
// ssr.ts skip registering ZiggyVue and `route` is never injected — fall
// back to a plain path so the page still renders instead of throwing.
const route = inject<typeof routeFn>('route');
const homeHref = computed(() => (route ? route('landing') : '/'));

const title = computed(() => {
    return {
        503: 'Сервіс недоступний',
        500: 'Помилка сервера',
        404: 'Сторінку не знайдено',
        403: 'Доступ заборонено',
    }[props.status];
});

const description = computed(() => {
    return {
        503: 'Вибачте, ми проводимо технічне обслуговування. Будь ласка, спробуйте пізніше.',
        500: 'Ой, щось пішло не так.',
        404: 'Вибачте, сторінку, яку ви шукаєте, не вдалося знайти.',
        403: 'Вибачте, у вас немає доступу до цієї сторінки.',
    }[props.status];
});
</script>
