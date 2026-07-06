<script setup lang="ts">
import type { Lang, UiTextKey } from '@/composables/useGuestLang';
import { masterServiceColor, masterServiceEmoji } from '@/lib/master-display';
import { SERVICE_LABELS } from '@/shared/guest-map-display-labels';
import {
    formatNextEventLabel,
    formatOpenLabel,
    getShortDayLabel,
    scheduleAfterEnter,
    scheduleAfterLeave,
    scheduleEnter,
    scheduleLeave,
    type WorkStatus,
} from '@/shared/workingHours';
import type { MasterDetails, MasterService } from '@/types/guest-map';
import { onBeforeUnmount, onMounted, ref } from 'vue';

type ScheduleEntry = { dayKey: string; value: string | null };

const props = defineProps<{
    master: MasterDetails;
    photos: string[];
    primaryService: MasterService | null;
    extraServices: MasterService[];
    workStatus: WorkStatus | null;
    schedule: ScheduleEntry[];
    scheduleOpen: boolean;
    currentLang: Lang;
    isFloxcity: boolean;
    isMasterSeoContent: boolean;
    canRequestStatus: boolean;
    isSendingStatusRequest: boolean;
    statusRequestMessage: string;
    t: (key: UiTextKey) => string;
    photoUrl: (path?: string | null) => string | null;
}>();

const emit = defineEmits<{
    close: [];
    photoClick: [photo: string];
    requestStatus: [];
    'update:scheduleOpen': [val: boolean];
}>();

function claimProfile(): void {
    const link = props.master.claim_link;
    if (typeof link === 'string') window.location.href = link;
}

const extraServicesExpanded = ref(false);
const extraServicesOverflowing = ref(false);
const extraServicesRowEl = ref<HTMLElement | null>(null);
let extraServicesResizeObserver: ResizeObserver | null = null;

function checkExtraServicesOverflow(): void {
    const el = extraServicesRowEl.value;
    if (!el) return;
    extraServicesOverflowing.value = el.scrollWidth > el.clientWidth + 1;
}

onMounted(() => {
    if (!extraServicesRowEl.value) return;
    extraServicesResizeObserver = new ResizeObserver(
        checkExtraServicesOverflow,
    );
    extraServicesResizeObserver.observe(extraServicesRowEl.value);
});

onBeforeUnmount(() => {
    extraServicesResizeObserver?.disconnect();
});
</script>

<template>
    <div
        class="glass-panel max-h-[72vh] overflow-auto rounded-2xl md:max-h-[78vh]"
    >
        <!-- Photo area -->
        <div class="detail-photo-area relative overflow-hidden rounded-t-2xl">
            <div
                v-if="photos.length"
                class="detail-photo-scroll flex h-20 gap-1.5 overflow-x-auto p-1.5"
            >
                <button
                    v-for="(photo, idx) in photos.slice(0, 6)"
                    :key="idx"
                    type="button"
                    class="detail-photo-thumb h-full flex-shrink-0 overflow-hidden rounded-xl"
                    @click="emit('photoClick', photo)"
                >
                    <img
                        :src="photoUrl(photo) ?? ''"
                        class="h-full w-auto object-contain"
                        :loading="idx === 0 ? 'eager' : 'lazy'"
                        decoding="async"
                        :alt="`${master.name} фото ${idx + 1}`"
                    />
                </button>
            </div>
            <div
                v-else
                class="detail-photo-placeholder flex h-20 items-center justify-center"
            >
                <div class="text-center">
                    <div class="mb-0.5 text-2xl">
                        {{ masterServiceEmoji(master) }}
                    </div>
                    <div class="text-[10px] font-medium text-slate-400">
                        ФОТО МАЙСТРА / РОБІТ
                    </div>
                </div>
            </div>

            <div
                v-if="(master.rating ?? 0) > 0"
                class="rating-badge absolute bottom-2 right-2 flex items-center gap-1 rounded-full px-2.5 py-1"
            >
                <svg
                    class="h-3 w-3 flex-shrink-0"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    style="color: #fbbf24"
                >
                    <polygon
                        points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"
                    />
                </svg>
                <span class="text-xs font-bold text-white">{{
                    (master.rating ?? 0).toFixed(1)
                }}</span>
            </div>

            <button
                class="detail-close absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full text-sm text-white"
                aria-label="Закрити"
                @click="emit('close')"
            >
                ✕
            </button>
        </div>

        <!-- Info section -->
        <div class="p-3">
            <component
                :is="isMasterSeoContent ? 'h1' : 'h2'"
                class="text-[15px] font-bold leading-tight"
                style="color: var(--panel-text)"
            >
                {{ master.name }}
            </component>
            <p
                v-if="master.address"
                class="mt-0.5 text-xs leading-tight"
                style="color: var(--panel-muted-text)"
            >
                {{ master.address }}
            </p>

            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                <span
                    class="h-2 w-2 flex-shrink-0 rounded-full"
                    :class="
                        master.available ? 'bg-emerald-500' : 'bg-slate-400'
                    "
                ></span>
                <span
                    class="text-xs font-semibold"
                    :class="
                        master.available ? 'text-emerald-600' : 'text-slate-500'
                    "
                >
                    {{
                        master.available
                            ? formatOpenLabel(currentLang, true)
                            : 'Зайнятий'
                    }}
                </span>
                <template v-if="workStatus">
                    <span class="text-slate-400">·</span>
                    <span
                        class="text-xs"
                        style="color: var(--panel-muted-text)"
                    >
                        {{ formatNextEventLabel(currentLang, workStatus) }}
                    </span>
                </template>
            </div>

            <div class="mt-2 flex items-start gap-2">
                <div
                    v-if="primaryService || extraServices.length"
                    class="min-w-0 flex-1"
                >
                    <div
                        v-if="primaryService || extraServices.length"
                        class="mb-1 flex items-center gap-1.5"
                    >
                        <span
                            v-if="primaryService"
                            class="inline-flex flex-shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                            :style="{ background: masterServiceColor(master) }"
                        >
                            {{ masterServiceEmoji(master) }}
                            {{
                                SERVICE_LABELS[primaryService.name]?.[
                                    currentLang
                                ] ?? primaryService.name
                            }}
                        </span>
                        <span
                            v-if="extraServices.length"
                            class="text-[10px] font-semibold uppercase tracking-wide text-slate-500"
                        >
                            {{ t('extraServices') }}
                        </span>
                    </div>
                    <div
                        v-if="extraServices.length"
                        class="flex cursor-pointer items-center gap-1"
                        role="button"
                        tabindex="0"
                        @click="extraServicesExpanded = !extraServicesExpanded"
                        @keydown.enter="
                            extraServicesExpanded = !extraServicesExpanded
                        "
                        @keydown.space.prevent="
                            extraServicesExpanded = !extraServicesExpanded
                        "
                    >
                        <div
                            ref="extraServicesRowEl"
                            class="flex min-w-0 gap-1"
                            :class="
                                extraServicesExpanded
                                    ? 'flex-wrap'
                                    : 'flex-nowrap overflow-hidden'
                            "
                        >
                            <span
                                v-for="service in extraServices"
                                :key="service.id"
                                class="flex-shrink-0 rounded-full px-2 py-0.5 text-xs"
                                :class="
                                    isFloxcity
                                        ? 'bg-emerald-50 text-slate-700'
                                        : 'bg-sky-50 text-slate-700'
                                "
                            >
                                {{
                                    SERVICE_LABELS[service.name]?.[
                                        currentLang
                                    ] ?? service.name
                                }}
                            </span>
                        </div>
                        <span
                            v-if="
                                extraServicesOverflowing &&
                                !extraServicesExpanded
                            "
                            class="flex-shrink-0 text-xs font-semibold text-slate-400"
                        >
                            …
                        </span>
                    </div>
                </div>

                <div class="ml-auto flex flex-shrink-0 gap-1.5">
                    <a
                        :href="`https://www.google.com/maps/search/?api=1&query=${master.latitude},${master.longitude}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="action-icon-btn action-icon-primary"
                        :aria-label="t('route')"
                    >
                        <svg
                            class="action-icon-svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <polygon points="3 11 22 2 13 21 11 13 3 11" />
                        </svg>
                    </a>
                    <a
                        v-if="master.phone"
                        :href="`tel:${master.phone}`"
                        class="action-icon-btn action-icon-secondary"
                        :aria-label="t('call')"
                    >
                        <svg
                            class="action-icon-svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.56 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"
                            />
                        </svg>
                    </a>
                    <button
                        v-if="!master.available"
                        type="button"
                        class="action-icon-btn"
                        :class="
                            canRequestStatus
                                ? 'action-icon-secondary'
                                : 'action-icon-muted'
                        "
                        :disabled="!canRequestStatus || isSendingStatusRequest"
                        :aria-label="t('askStatus')"
                        @click="emit('requestStatus')"
                    >
                        <svg
                            v-if="canRequestStatus"
                            class="action-icon-svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"
                            />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                        <svg
                            v-else
                            class="action-icon-svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    </button>
                    <button
                        v-if="!master.is_claimed && master.claim_link"
                        type="button"
                        class="action-icon-btn action-icon-claim"
                        :aria-label="t('claim')"
                        @click="claimProfile"
                    >
                        <svg
                            class="action-icon-svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div
                v-if="statusRequestMessage"
                class="mt-1.5 rounded-lg px-3 py-1.5 text-xs"
                :class="
                    isFloxcity
                        ? 'bg-emerald-50 text-slate-800'
                        : 'bg-sky-50 text-slate-800'
                "
            >
                {{ statusRequestMessage }}
            </div>
        </div>

        <!-- Schedule section -->
        <div v-if="workStatus || schedule.length" class="mt-1 px-3">
            <button
                v-if="workStatus"
                type="button"
                class="flex w-full items-center gap-2 rounded-xl px-3 py-1.5 text-left text-sm transition-colors duration-150 hover:brightness-95 active:brightness-90"
                :class="[
                    'bg-white/70',
                    schedule.length ? 'rounded-b-none' : '',
                ]"
                @click="emit('update:scheduleOpen', !scheduleOpen)"
            >
                <span
                    class="h-2 w-2 flex-shrink-0 rounded-full transition-colors duration-300"
                    :class="workStatus.isOpen ? 'bg-emerald-500' : 'bg-red-500'"
                />
                <span
                    class="font-semibold"
                    :class="
                        workStatus.isOpen ? 'text-emerald-700' : 'text-red-600'
                    "
                >
                    {{ formatOpenLabel(currentLang, workStatus.isOpen) }}
                </span>
                <span class="text-slate-500">
                    · {{ formatNextEventLabel(currentLang, workStatus) }}
                </span>
                <svg
                    class="ml-auto h-3.5 w-3.5 flex-shrink-0 text-slate-400 transition-transform duration-300"
                    :style="{
                        transform: scheduleOpen
                            ? 'rotate(180deg)'
                            : 'rotate(0deg)',
                    }"
                    viewBox="0 0 12 12"
                    fill="none"
                >
                    <path
                        d="M2 4l4 4 4-4"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </button>

            <Transition
                :css="false"
                @enter="scheduleEnter"
                @after-enter="scheduleAfterEnter"
                @leave="scheduleLeave"
                @after-leave="scheduleAfterLeave"
            >
                <div
                    v-if="schedule.length && (!workStatus || scheduleOpen)"
                    class="space-y-px rounded-xl"
                    :class="workStatus ? 'rounded-t-none' : ''"
                >
                    <div
                        v-for="item in schedule"
                        :key="item.dayKey"
                        class="flex items-center justify-between bg-white/70 px-3 py-1 text-xs"
                    >
                        <span class="text-slate-500">{{
                            getShortDayLabel(currentLang, item.dayKey)
                        }}</span>
                        <span
                            class="font-medium"
                            :class="
                                item.value ? 'text-slate-900' : 'text-slate-400'
                            "
                        >
                            {{ item.value ?? 'Вихідний' }}
                        </span>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- Reviews section -->
        <div class="mt-2 px-3 pb-3">
            <div class="mb-1 flex flex-wrap items-center gap-1.5">
                <span
                    v-if="(master.rating ?? 0) > 0"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-amber-500"
                >
                    ★ {{ (master.rating ?? 0).toFixed(1) }}
                    <span
                        v-if="master.reviews_count"
                        class="font-normal text-slate-500"
                    >
                        ({{ master.reviews_count }})
                    </span>
                </span>
                <span class="text-xs font-semibold text-slate-900">{{
                    t('reviews')
                }}</span>
            </div>
            <div v-if="master.reviews?.length" class="space-y-1.5">
                <div
                    v-for="review in master.reviews"
                    :key="review.id"
                    class="rounded-lg bg-white/70 p-2 text-xs"
                >
                    <div class="font-medium text-slate-900">
                        {{ review.user?.name || t('anonymous') }}
                    </div>
                    <div
                        :class="
                            isFloxcity ? 'text-yellow-500' : 'text-yellow-300'
                        "
                    >
                        ★ {{ review.rating }}
                    </div>
                    <div class="text-slate-700">{{ review.review || '—' }}</div>
                </div>
            </div>
            <div v-else class="text-xs text-slate-500">
                {{ t('noReviews') }}
            </div>
        </div>
    </div>
</template>

<style scoped>
.glass-panel {
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    color: var(--panel-text);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.24);
    backdrop-filter: blur(24px) saturate(160%);
    -webkit-backdrop-filter: blur(24px) saturate(160%);
}

.detail-photo-placeholder {
    background: repeating-linear-gradient(
        -45deg,
        rgba(0, 0, 0, 0.04),
        rgba(0, 0, 0, 0.04) 8px,
        rgba(0, 0, 0, 0.02) 8px,
        rgba(0, 0, 0, 0.02) 16px
    );
}

.detail-photo-scroll {
    scrollbar-width: none;
}

.detail-photo-thumb {
    cursor: pointer;
    transition: opacity 0.12s ease;
}

.detail-photo-thumb:hover {
    opacity: 0.88;
}

.detail-service-chip {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.detail-close {
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    transition: background 0.12s ease;
}

.detail-close:hover {
    background: rgba(0, 0, 0, 0.62);
}

.rating-badge {
    background: rgba(0, 0, 0, 0.52);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.14);
}

.action-icon-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    flex-shrink: 0;
    cursor: pointer;
    text-decoration: none;
    border: 1px solid transparent;
    transition:
        opacity 0.14s ease,
        transform 0.12s ease,
        box-shadow 0.14s ease;
}

.action-icon-btn:hover {
    opacity: 0.82;
}

.action-icon-btn:active {
    transform: scale(0.93);
}

.action-icon-btn:disabled {
    pointer-events: none;
}

.action-icon-svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.action-icon-primary {
    background: var(--brand-primary);
    color: #fff;
    box-shadow: 0 4px 14px rgba(var(--brand-primary-rgb), 0.32);
}

.action-icon-secondary {
    background: rgba(var(--glass-accent-rgb), 0.1);
    border-color: rgba(var(--glass-accent-rgb), 0.22);
    color: var(--brand-primary);
}

.action-icon-claim {
    background: var(--brand-success);
    color: #fff;
    box-shadow: 0 4px 12px rgba(4, 120, 87, 0.28);
}

.action-icon-muted {
    background: rgba(0, 0, 0, 0.04);
    border-color: rgba(0, 0, 0, 0.08);
    color: rgba(0, 0, 0, 0.25);
}
</style>
