<script setup lang="ts">
import {
    masterPrimaryServiceName,
    masterServiceColor,
    masterServiceEmoji,
} from '@/lib/master-display';
import type { MasterDetails } from '@/types/guest-map';

const props = defineProps<{
    masters: MasterDetails[];
    loading?: boolean;
    photoUrl: (path?: string | null) => string | null;
    serviceNameById?: Record<number, string>;
    selectedMasterId?: number | null;
}>();

const emit = defineEmits<{
    masterClick: [id: number];
    close: [];
}>();

const SKELETON_ROW_COUNT = 8;

function extraServicesCount(master: MasterDetails): number {
    return Math.max((master.services?.length ?? 1) - 1, 0);
}

function formatDistance(km?: number): string | null {
    if (km === undefined || km === null || Number.isNaN(km)) return null;
    if (km < 1) return `${Math.round(km * 1000)} м`;
    return `${km.toFixed(1)} км`;
}

function callMaster(event: MouseEvent, phone?: string | null): void {
    if (!phone) event.preventDefault();
    event.stopPropagation();
}
</script>

<template>
    <div class="glass-panel flex h-full flex-col rounded-2xl">
        <div
            class="flex shrink-0 items-center justify-between gap-2 border-b px-4 py-3"
            style="border-color: var(--panel-border)"
        >
            <span
                v-if="!loading"
                class="text-sm font-bold"
                style="color: var(--panel-text)"
            >
                {{ masters.length }} майстрів поблизу
            </span>
            <span
                v-else
                class="skeleton-block h-4 w-32 rounded"
                aria-hidden="true"
            ></span>
            <button
                type="button"
                class="flex items-center gap-1 text-sm font-semibold"
                style="color: var(--brand-primary)"
                @click="emit('close')"
            >
                На карті ↓
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
            <template v-if="loading && masters.length === 0">
                <div
                    v-for="n in SKELETON_ROW_COUNT"
                    :key="`skeleton-${n}`"
                    class="master-row-skeleton mb-1.5 flex items-center gap-3 rounded-xl p-2"
                    aria-hidden="true"
                >
                    <div
                        class="skeleton-block h-11 w-11 shrink-0 rounded-lg"
                    ></div>
                    <div class="min-w-0 flex-1">
                        <div
                            class="skeleton-block mb-1.5 h-3 w-2/3 rounded"
                        ></div>
                        <div class="skeleton-block h-2.5 w-1/3 rounded"></div>
                    </div>
                </div>
            </template>

            <div
                v-else-if="masters.length === 0"
                class="flex h-full items-center justify-center px-4 text-center text-sm"
                style="color: var(--panel-muted-text)"
            >
                Майстрів не знайдено в цій області карти
            </div>

            <template v-else>
                <button
                    v-for="master in masters"
                    :key="master.id"
                    type="button"
                    class="master-row mb-1.5 flex w-full items-center gap-3 rounded-xl p-2 text-left"
                    :class="{
                        'master-row-selected': master.id === selectedMasterId,
                    }"
                    @click="emit('masterClick', master.id)"
                >
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg text-lg"
                        :style="{ background: masterServiceColor(master) }"
                    >
                        <img
                            v-if="
                                photoUrl(
                                    master.main_thumb_url ?? master.main_photo,
                                )
                            "
                            :src="
                                photoUrl(
                                    master.main_thumb_url ?? master.main_photo,
                                ) ?? ''
                            "
                            class="h-full w-full object-cover"
                            loading="lazy"
                            :alt="master.name"
                        />
                        <span v-else>{{
                            masterServiceEmoji(master, props.serviceNameById)
                        }}</span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span
                                class="truncate text-sm font-bold"
                                style="color: var(--panel-text)"
                            >
                                {{ master.name }}
                            </span>
                            <span
                                v-if="master.available"
                                class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"
                                aria-hidden="true"
                            ></span>
                        </div>
                        <div
                            class="truncate text-xs"
                            style="color: var(--panel-muted-text)"
                        >
                            {{
                                masterPrimaryServiceName(
                                    master,
                                    props.serviceNameById,
                                ) ?? 'Майстер'
                            }}
                            <span v-if="extraServicesCount(master) > 0">
                                +{{ extraServicesCount(master) }}
                            </span>
                        </div>
                        <div
                            class="mt-0.5 flex items-center gap-2 text-[11px]"
                            style="color: var(--panel-muted-text)"
                        >
                            <span class="flex items-center gap-0.5">
                                <span class="text-amber-500">★</span>
                                <span class="font-semibold">{{
                                    (master.rating ?? 0).toFixed(1)
                                }}</span>
                                <span v-if="master.reviews_count"
                                    >({{ master.reviews_count }})</span
                                >
                            </span>
                            <span v-if="formatDistance(master.distance)">
                                {{ formatDistance(master.distance) }}
                            </span>
                        </div>
                    </div>

                    <a
                        v-if="master.phone"
                        :href="`tel:${master.phone}`"
                        class="call-btn flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                        aria-label="Подзвонити"
                        @click="callMaster($event, master.phone)"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"
                            />
                        </svg>
                    </a>
                </button>
            </template>
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

.master-row,
.master-row-skeleton {
    background: var(--surface-bg);
    border: 1px solid var(--surface-border);
    cursor: pointer;
    transition:
        background 0.12s ease,
        border-color 0.12s ease;
}

.master-row:hover {
    background: var(--surface-bg-hover);
}

.master-row-selected {
    background: var(--surface-bg-hover);
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 2px rgba(var(--brand-primary-rgb), 0.18);
}

.master-row-skeleton {
    cursor: default;
}

.call-btn {
    background: var(--brand-primary);
    color: #fff;
    transition: opacity 0.12s ease;
}

.call-btn:hover {
    opacity: 0.88;
}

.skeleton-block {
    background: var(--surface-border);
    animation: skeleton-pulse 1.2s ease-in-out infinite;
}

@keyframes skeleton-pulse {
    0%,
    100% {
        opacity: 0.5;
    }
    50% {
        opacity: 1;
    }
}
</style>
