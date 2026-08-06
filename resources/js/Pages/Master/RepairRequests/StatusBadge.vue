<template>
    <span
        class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
        :class="config.classes"
    >
        <span class="h-1.5 w-1.5 rounded-full" :class="config.dot" />
        {{ config.label }}
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: 'pending' | 'called' | 'rejected';
}>();

const config = computed(() => {
    switch (props.status) {
        case 'called':
            return {
                label: 'Дзвонив',
                classes: 'bg-amber-100 text-amber-700',
                dot: 'bg-amber-500',
            };
        case 'rejected':
            return {
                label: 'Відхилено',
                classes: 'bg-red-100 text-red-600',
                dot: 'bg-red-500',
            };
        default:
            return {
                label: 'Нова',
                classes: 'bg-emerald-100 text-emerald-700',
                dot: 'bg-emerald-500',
            };
    }
});
</script>
