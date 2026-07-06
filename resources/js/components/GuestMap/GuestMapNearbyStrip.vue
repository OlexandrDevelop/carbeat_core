<script setup lang="ts">
import { useHorizontalVirtualList } from '@/composables/useHorizontalVirtualList';
import { masterServiceColor, masterServiceEmoji } from '@/lib/master-display';
import type { MasterDetails } from '@/types/guest-map';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    masters: MasterDetails[];
    loading?: boolean;
    photoUrl: (path?: string | null) => string | null;
}>();

const emit = defineEmits<{
    masterClick: [id: number];
}>();

const SKELETON_CARD_COUNT = 6;

const scrollContainerRef = ref<HTMLElement | null>(null);

const { startIndex, endIndex, leftSpacerPx, rightSpacerPx, recompute } =
    useHorizontalVirtualList({
        containerRef: scrollContainerRef,
        itemSelector: '.nearby-card',
        itemCount: () => props.masters.length,
    });

watch(
    () => props.masters,
    () => recompute(),
);

const visibleMasters = computed(() =>
    props.masters.slice(startIndex.value, endIndex.value),
);
</script>

<template>
    <div class="nearby-panel rounded-t-3xl px-4 pb-4 pt-4">
        <div class="mb-3 flex items-center justify-between">
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
            <span
                class="text-sm font-semibold"
                style="color: var(--brand-primary)"
            >
                Списком ↑
            </span>
        </div>
        <div
            ref="scrollContainerRef"
            class="nearby-cards-scroll flex gap-3 overflow-x-auto pb-1"
        >
            <template v-if="loading">
                <div
                    v-for="n in SKELETON_CARD_COUNT"
                    :key="`skeleton-${n}`"
                    class="nearby-card nearby-card-skeleton flex-shrink-0 rounded-2xl p-3"
                    aria-hidden="true"
                >
                    <div
                        class="skeleton-block mx-auto mb-2 h-[52px] w-[52px] rounded-xl"
                    ></div>
                    <div class="w-[72px]">
                        <div
                            class="skeleton-block mb-1 h-3 w-full rounded"
                        ></div>
                        <div
                            class="skeleton-block mb-1 h-2 w-2/3 rounded"
                        ></div>
                        <div class="skeleton-block h-2 w-1/2 rounded"></div>
                    </div>
                </div>
            </template>
            <template v-else>
                <div
                    v-if="leftSpacerPx > 0"
                    class="flex-shrink-0"
                    :style="{ width: `${leftSpacerPx}px` }"
                    aria-hidden="true"
                ></div>
                <button
                    v-for="master in visibleMasters"
                    :key="master.id"
                    type="button"
                    class="nearby-card flex-shrink-0 rounded-2xl p-3 text-left"
                    @click="emit('masterClick', master.id)"
                >
                    <div
                        class="nearby-card-icon mx-auto mb-2 flex h-[52px] w-[52px] items-center justify-center overflow-hidden rounded-xl text-2xl"
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
                        <span v-else>{{ masterServiceEmoji(master) }}</span>
                    </div>
                    <div class="w-[72px]">
                        <div
                            class="truncate text-xs font-bold"
                            style="color: var(--panel-text)"
                        >
                            {{ master.name.split(' ')[0] }}
                        </div>
                        <div
                            class="truncate text-[10px]"
                            style="color: var(--panel-muted-text)"
                        >
                            {{ master.services?.[0]?.name ?? 'Майстер' }}
                        </div>
                        <div class="mt-1 flex items-center gap-1">
                            <span class="text-[10px] text-amber-500">★</span>
                            <span
                                class="text-[10px] font-semibold"
                                style="color: var(--panel-muted-text)"
                            >
                                {{ (master.rating ?? 0).toFixed(1) }}
                            </span>
                            <span
                                v-if="master.available"
                                class="ml-auto h-1.5 w-1.5 rounded-full bg-emerald-500"
                            ></span>
                        </div>
                    </div>
                </button>
                <div
                    v-if="rightSpacerPx > 0"
                    class="flex-shrink-0"
                    :style="{ width: `${rightSpacerPx}px` }"
                    aria-hidden="true"
                ></div>
            </template>
        </div>
    </div>
</template>

<style scoped>
.nearby-panel {
    background: var(--panel-bg);
    border-top: 1px solid var(--panel-border);
    box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.18);
    backdrop-filter: blur(24px) saturate(160%);
    -webkit-backdrop-filter: blur(24px) saturate(160%);
}

.nearby-cards-scroll {
    scrollbar-width: none;
    -ms-overflow-style: none;
    touch-action: pan-x;
}

.nearby-cards-scroll::-webkit-scrollbar {
    display: none;
}

.nearby-card {
    background: var(--surface-bg);
    border: 1px solid var(--surface-border);
    border-radius: 1rem;
    cursor: pointer;
    transition:
        background 0.12s ease,
        border-color 0.12s ease,
        transform 0.12s ease;
}

.nearby-card:hover {
    background: var(--surface-bg-hover);
    transform: translateY(-1px);
}

.nearby-card-skeleton {
    cursor: default;
}

.nearby-card-skeleton:hover {
    background: var(--surface-bg);
    transform: none;
}

.nearby-card-icon {
    font-size: 1.5rem;
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
