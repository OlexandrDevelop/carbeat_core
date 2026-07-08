<template>
    <button
        type="button"
        class="flex w-full items-center gap-3 rounded-2xl border p-3 text-left transition hover:shadow-md"
        :style="{ backgroundColor: softColor, borderColor: color }"
        @click="$emit('click')"
    >
        <TimeBlockTile :time="appointment.startsAt" :kind="appointment.kind" />

        <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
                <p class="truncate text-sm font-bold text-slate-900">
                    {{ appointment.customerName || 'Без імені' }}
                </p>
                <span
                    class="shrink-0 font-mono text-[11px] font-semibold text-slate-500"
                >
                    {{ timeRange }}
                </span>
            </div>
            <p class="truncate text-xs text-slate-600">
                {{ appointment.serviceName || '—' }}
                <span v-if="appointment.carModel">
                    · {{ appointment.carModel }}</span
                >
                <span
                    v-if="appointment.plateNumber"
                    class="font-mono uppercase"
                >
                    · {{ appointment.plateNumber }}</span
                >
            </p>
            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                <StatusPill :kind="appointment.kind" />
                <StatusPill :payment-status="appointment.paymentStatus" />
                <span
                    v-if="appointment.priceUah !== null"
                    class="font-mono text-[11px] font-semibold text-slate-500"
                >
                    {{ appointment.priceUah }} ₴
                </span>
            </div>
        </div>
    </button>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { CrmAppointment } from '../../types/master-crm';
import StatusPill from './StatusPill.vue';
import TimeBlockTile from './TimeBlockTile.vue';

const props = defineProps<{
    appointment: CrmAppointment;
}>();

defineEmits<{ click: [] }>();

const tone = computed(() => {
    switch (props.appointment.kind) {
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
const softColor = computed(() => `var(--status-${tone.value}-soft)`);

function formatTime(iso: string): string {
    const date = new Date(iso);
    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}

const timeRange = computed(
    () =>
        `${formatTime(props.appointment.startsAt)}–${formatTime(props.appointment.endsAt)}`,
);
</script>
