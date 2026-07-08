<template>
    <div
        class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl font-mono font-bold text-white"
        :style="{ backgroundColor: color }"
    >
        <span class="text-[13px] leading-none">{{ hh }}</span>
        <span class="text-[10px] leading-none opacity-80">{{ mm }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { AppointmentKind } from '../../types/master-crm';

const props = defineProps<{
    time: string;
    kind?: AppointmentKind;
}>();

const hh = computed(() => {
    const date = new Date(props.time);
    return String(date.getHours()).padStart(2, '0');
});

const mm = computed(() => {
    const date = new Date(props.time);
    return String(date.getMinutes()).padStart(2, '0');
});

const tone = computed(() => {
    switch (props.kind) {
        case 'work':
            return 'busy';
        case 'request':
            return 'request';
        case 'next':
            return 'next';
        default:
            return 'free';
    }
});

const color = computed(() => `var(--status-${tone.value})`);
</script>
