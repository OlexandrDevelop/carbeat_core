<template>
    <div
        ref="trackEl"
        class="timeline-track"
        :style="{ height: totalHeight + 'px' }"
        @click="onTrackClick"
        @mousemove="onTrackHover"
        @mouseleave="hoverY = null"
    >
        <div class="timeline-spine" />

        <div
            v-for="mark in hourMarks"
            :key="mark.hour"
            class="timeline-tick"
            :style="{ top: mark.top + 'px' }"
        >
            <span class="timeline-tick-dot" />
            <span v-if="mark.showLabel" class="timeline-tick-label">{{
                mark.label
            }}</span>
        </div>

        <div
            v-if="nowTop !== null"
            class="timeline-now-wash"
            :style="{ top: nowTop + 'px' }"
        />
        <div
            v-if="nowTop !== null"
            class="timeline-now"
            :style="{ top: nowTop + 'px' }"
        >
            <span class="timeline-now-dot" />
            <span class="timeline-now-label">Зараз</span>
        </div>

        <div
            v-if="hoverY !== null"
            class="timeline-hover-line"
            :style="{ top: hoverY + 'px' }"
        >
            <span class="timeline-hover-badge">{{ hoverLabel }}</span>
        </div>

        <button
            v-for="block in blocks"
            :key="block.appointment.id"
            type="button"
            class="timeline-block"
            :class="{ 'timeline-block--active': block.isActive }"
            :style="block.style"
            @click.stop="$emit('select', block.appointment)"
            @mouseenter.stop="hoverY = null"
        >
            <span
                class="timeline-block-dot"
                :style="{ background: block.color }"
            />
            <span class="timeline-block-body">
                <span class="timeline-block-head">
                    <span class="timeline-block-title">{{ block.title }}</span>
                    <span class="timeline-block-time">{{
                        block.timeRange
                    }}</span>
                </span>
                <span
                    v-if="block.showService && block.appointment.serviceName"
                    class="timeline-block-sub"
                >
                    {{ block.appointment.serviceName }}
                </span>
            </span>
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import type { AppointmentKind, CrmAppointment } from '../../types/master-crm';

const props = defineProps<{
    appointments: CrmAppointment[];
    isToday: boolean;
    businessDay: string;
    startHour?: number;
    endHour?: number;
}>();

const emit = defineEmits<{
    select: [CrmAppointment];
    create: [startsAt?: string];
}>();

const START_HOUR = computed(() => props.startHour ?? 8);
const END_HOUR = computed(() => props.endHour ?? 21);
const HOUR_HEIGHT = 46;
const MIN_BLOCK_HEIGHT = 34;
const GUTTER = 52; // spine + hour labels reserve this much on the left

const totalHeight = computed(
    () => (END_HOUR.value - START_HOUR.value) * HOUR_HEIGHT,
);

const hourMarks = computed(() => {
    const marks: {
        hour: number;
        top: number;
        label: string;
        showLabel: boolean;
    }[] = [];
    for (let h = START_HOUR.value; h <= END_HOUR.value; h++) {
        const isEdge = h === START_HOUR.value || h === END_HOUR.value;
        marks.push({
            hour: h,
            top: (h - START_HOUR.value) * HOUR_HEIGHT,
            label: `${String(h).padStart(2, '0')}:00`,
            showLabel: isEdge || h % 2 === 0,
        });
    }
    return marks;
});

function hourOf(iso: string): number {
    const d = new Date(iso);
    return d.getHours() + d.getMinutes() / 60;
}

function toneColor(kind: AppointmentKind): { color: string; soft: string } {
    const tone =
        kind === 'work' ? 'busy' : kind === 'request' ? 'request' : 'next';
    return {
        color: `var(--status-${tone})`,
        soft: `var(--status-${tone}-soft)`,
    };
}

function formatTime(iso: string): string {
    const d = new Date(iso);
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

const nowMs = props.isToday ? Date.now() : null;

const blocks = computed(() =>
    props.appointments.map((appointment) => {
        const startHour = Math.max(
            START_HOUR.value,
            hourOf(appointment.startsAt),
        );
        const endHour = Math.min(END_HOUR.value, hourOf(appointment.endsAt));
        const top = (startHour - START_HOUR.value) * HOUR_HEIGHT;
        const height = Math.max(
            MIN_BLOCK_HEIGHT,
            (endHour - startHour) * HOUR_HEIGHT - 4,
        );
        const { color, soft } = toneColor(appointment.kind);
        const isActive =
            nowMs !== null &&
            nowMs >= new Date(appointment.startsAt).getTime() &&
            nowMs <= new Date(appointment.endsAt).getTime();

        return {
            appointment,
            color,
            isActive,
            timeRange: `${formatTime(appointment.startsAt)}–${formatTime(appointment.endsAt)}`,
            title: appointment.customerName || appointment.carModel || 'Запис',
            showService: height >= 54,
            style: {
                top: `${top}px`,
                height: `${height}px`,
                background: soft,
                boxShadow: isActive
                    ? `0 0 0 2px ${color}, 0 8px 18px rgba(20, 30, 50, 0.16)`
                    : undefined,
            },
        };
    }),
);

const nowTop = computed(() => {
    if (!props.isToday) return null;
    const now = new Date();
    const hour = now.getHours() + now.getMinutes() / 60;
    if (hour < START_HOUR.value || hour > END_HOUR.value) return null;
    return (hour - START_HOUR.value) * HOUR_HEIGHT;
});

const trackEl = ref<HTMLElement | null>(null);
const hoverY = ref<number | null>(null);

function hourFromClientY(clientY: number): number {
    const rect = trackEl.value!.getBoundingClientRect();
    const y = clientY - rect.top;
    const hour = START_HOUR.value + y / HOUR_HEIGHT;
    return Math.min(END_HOUR.value, Math.max(START_HOUR.value, hour));
}

function snappedLabel(hour: number): string {
    const totalMinutes = Math.round((hour * 60) / 15) * 15;
    const hh = String(Math.floor(totalMinutes / 60) % 24).padStart(2, '0');
    const mm = String(totalMinutes % 60).padStart(2, '0');
    return `${hh}:${mm}`;
}

const hoverLabel = computed(() =>
    hoverY.value === null
        ? ''
        : snappedLabel(START_HOUR.value + hoverY.value / HOUR_HEIGHT),
);

function onTrackHover(event: MouseEvent) {
    if (!trackEl.value) return;
    hoverY.value = event.clientY - trackEl.value.getBoundingClientRect().top;
}

function onTrackClick(event: MouseEvent) {
    if (!trackEl.value) return;
    const hour = hourFromClientY(event.clientY);
    emit('create', `${props.businessDay}T${snappedLabel(hour)}:00`);
}
</script>

<style scoped>
.timeline-track {
    position: relative;
    cursor: pointer;
    padding-left: v-bind('GUTTER + "px"');
}

.timeline-spine {
    position: absolute;
    top: 4px;
    bottom: 4px;
    left: 22px;
    width: 1px;
    background: linear-gradient(
        to bottom,
        transparent,
        rgba(148, 163, 184, 0.35) 6%,
        rgba(148, 163, 184, 0.35) 94%,
        transparent
    );
}

.timeline-tick {
    position: absolute;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    transform: translateY(-50%);
    pointer-events: none;
}

.timeline-tick-dot {
    width: 5px;
    height: 5px;
    border-radius: 9999px;
    background: rgba(148, 163, 184, 0.55);
    margin-left: 19px;
}

.timeline-tick-label {
    font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo,
        monospace;
    font-size: 10px;
    font-weight: 700;
    color: #94a3b8;
    white-space: nowrap;
}

.timeline-now-wash {
    position: absolute;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(
        to right,
        rgba(var(--brand-primary-rgb), 0.28),
        transparent 70%
    );
    pointer-events: none;
}

.timeline-now {
    position: absolute;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    transform: translateY(-50%);
    z-index: 2;
    pointer-events: none;
}

.timeline-now-dot {
    width: 9px;
    height: 9px;
    border-radius: 9999px;
    background: var(--brand-primary);
    margin-left: 17px;
    box-shadow: 0 0 0 4px rgba(var(--brand-primary-rgb), 0.18);
}

.timeline-now-label {
    font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo,
        monospace;
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--brand-primary);
}

.timeline-hover-line {
    position: absolute;
    left: v-bind('GUTTER + "px"');
    right: 4px;
    height: 1px;
    border-top: 1.5px dashed rgba(100, 116, 139, 0.55);
    pointer-events: none;
    z-index: 3;
}

.timeline-hover-badge {
    position: absolute;
    right: 0;
    top: -9px;
    transform: translateY(-100%);
    background: #1e293b;
    color: #fff;
    font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo,
        monospace;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
    white-space: nowrap;
}

.timeline-block {
    position: absolute;
    left: v-bind('GUTTER + "px"');
    right: 4px;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    gap: 7px;
    overflow: hidden;
    border-radius: 12px;
    padding: 5px 10px 5px 9px;
    text-align: left;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(20, 30, 50, 0.05);
    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease;
}

.timeline-block:hover {
    transform: translateX(2px);
    box-shadow: 0 8px 18px rgba(20, 30, 50, 0.16) !important;
}

.timeline-block--active {
    animation: timeline-pulse 2.4s ease-in-out infinite;
}

.timeline-block-dot {
    width: 7px;
    height: 7px;
    border-radius: 9999px;
    margin-top: 4px;
    flex-shrink: 0;
}

.timeline-block-body {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.timeline-block-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 6px;
}

.timeline-block-time {
    font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo,
        monospace;
    font-size: 9px;
    font-weight: 700;
    opacity: 0.65;
    flex-shrink: 0;
}

.timeline-block-title {
    font-size: 12px;
    font-weight: 800;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
}

.timeline-block-sub {
    font-size: 10px;
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

@keyframes timeline-pulse {
    0%,
    100% {
        filter: brightness(1);
    }
    50% {
        filter: brightness(1.04);
    }
}
</style>
