<template>
    <div
        :class="[
            variant === 'surface' ? 'glass-surface' : 'glass-panel',
            roundedClass,
            paddingClass,
        ]"
    >
        <slot />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'panel' | 'surface';
        rounded?: 'xl' | '2xl';
        padding?: 'none' | 'sm' | 'md' | 'lg';
    }>(),
    {
        variant: 'panel',
        rounded: '2xl',
        padding: 'md',
    },
);

const roundedClass = computed(() =>
    props.rounded === 'xl' ? 'rounded-xl' : 'rounded-2xl',
);

const paddingClass = computed(() => {
    switch (props.padding) {
        case 'none':
            return '';
        case 'sm':
            return 'p-3';
        case 'lg':
            return 'p-8';
        default:
            return 'p-5';
    }
});
</script>
