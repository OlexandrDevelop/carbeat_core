<template>
    <span
        class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-wider"
        :style="{ color, backgroundColor: softColor }"
    >
        {{ label }}
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { AppointmentKind, PaymentStatus } from '../../types/master-crm';

const props = defineProps<{
    kind?: AppointmentKind;
    paymentStatus?: PaymentStatus;
    label?: string;
}>();

type StatusTone = 'busy' | 'request' | 'next' | 'free';

const tone = computed<StatusTone>(() => {
    if (props.kind) {
        return props.kind === 'work'
            ? 'busy'
            : props.kind === 'request'
              ? 'request'
              : 'next';
    }
    if (props.paymentStatus) {
        switch (props.paymentStatus) {
            case 'paid':
                return 'free';
            case 'partial':
                return 'next';
            case 'debt':
                return 'busy';
            default:
                return 'request';
        }
    }
    return 'free';
});

const color = computed(() => `var(--status-${tone.value})`);
const softColor = computed(() => `var(--status-${tone.value}-soft)`);

const defaultLabels: Record<string, string> = {
    work: 'В роботі',
    next: 'Далі',
    request: 'Заявка',
    pending: 'Не оплачено',
    partial: 'Частково',
    paid: 'Оплачено',
    debt: 'Борг',
};

const label = computed(
    () =>
        props.label ??
        defaultLabels[props.kind ?? props.paymentStatus ?? ''] ??
        '',
);
</script>
