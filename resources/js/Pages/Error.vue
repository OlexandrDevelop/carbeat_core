<template>
    <div class="min-h-screen bg-white text-gray-900 flex items-center justify-center">
        <div class="text-center">
            <h1 class="text-9xl font-bold text-sky-500">{{ status }}</h1>
            <p class="text-2xl md:text-3xl font-light text-gray-800 mt-4">{{ title }}</p>
            <p class="text-md text-gray-600 mt-2">{{ description }}</p>
            <a :href="route('landing')" class="mt-8 inline-block rounded-full bg-slate-900 px-8 py-4 text-lg font-semibold text-white hover:bg-slate-800">
                Повернутись на головну
            </a>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: number;
}>();

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

