<script setup lang="ts">
import type { Lang, UiTextKey } from '@/composables/useGuestLang';
import type { Flavor } from '@/types/guest-map';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{
    flavor: Flavor;
    currentLang: Lang;
    availableOnly: boolean;
    searchQuery: string;
    brandName: string;
    mobileAppUrl: string;
    loading?: boolean;
    t: (key: UiTextKey) => string;
}>();

const emit = defineEmits<{
    'update:availableOnly': [val: boolean];
    'update:searchQuery': [val: string];
    setLanguage: [lang: Lang];
    openOnboard: [];
}>();

const isFloxcity = computed(() => props.flavor === 'floxcity');

// Instead of a separate "Завантаження" pill, the panel's own background
// repaints itself: a gray track appears immediately, then a fill matching
// the normal panel background grows left-to-right as the request runs.
const showLoadingVeil = ref(false);
const loadingProgress = ref(0);
let trickleTimer: number | null = null;
let finishTimer: number | null = null;

function clearLoadingTimers(): void {
    if (trickleTimer !== null) {
        window.clearInterval(trickleTimer);
        trickleTimer = null;
    }
    if (finishTimer !== null) {
        window.clearTimeout(finishTimer);
        finishTimer = null;
    }
}

watch(
    () => props.loading,
    (isLoading) => {
        clearLoadingTimers();
        if (isLoading) {
            showLoadingVeil.value = true;
            loadingProgress.value = 0;
            trickleTimer = window.setInterval(() => {
                loadingProgress.value = Math.min(
                    92,
                    loadingProgress.value +
                        (92 - loadingProgress.value) * 0.15 +
                        2,
                );
            }, 200);
        } else if (showLoadingVeil.value) {
            loadingProgress.value = 100;
            finishTimer = window.setTimeout(() => {
                showLoadingVeil.value = false;
                loadingProgress.value = 0;
            }, 260);
        }
    },
);

onBeforeUnmount(() => clearLoadingTimers());
</script>

<template>
    <div class="glass-panel rounded-2xl p-3">
        <div
            class="loading-track absolute inset-0 rounded-2xl"
            :class="{ 'loading-track-visible': showLoadingVeil }"
            aria-hidden="true"
        >
            <div
                class="loading-fill h-full"
                :style="{ width: loadingProgress + '%' }"
            ></div>
        </div>

        <div class="relative z-10 mb-2 flex items-center justify-between gap-2">
            <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                :class="
                    isFloxcity
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-sky-100 text-sky-700'
                "
            >
                {{ brandName }}
            </span>
            <a
                :href="mobileAppUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="app-download-cta rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white"
            >
                {{ t('appDownloadCta') }}
            </a>
            <button
                type="button"
                class="become-master-cta rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide"
                @click="emit('openOnboard')"
            >
                {{ t('becomeMaster') }}
            </button>
            <div
                class="inline-flex items-center gap-1 rounded-lg p-0.5"
                :class="isFloxcity ? 'bg-emerald-50' : 'bg-sky-50'"
            >
                <button
                    v-for="lang in ['en', 'uk', 'de'] as Lang[]"
                    :key="lang"
                    type="button"
                    class="rounded-md px-2 py-1 text-xs font-semibold"
                    :class="
                        currentLang === lang
                            ? 'bg-white text-slate-900'
                            : 'bg-white/70 text-slate-600'
                    "
                    @click="emit('setLanguage', lang)"
                >
                    {{ lang.toUpperCase() }}
                </button>
            </div>
        </div>

        <div class="relative z-10 flex items-stretch gap-2">
            <button
                type="button"
                class="quick-action-btn flex w-16 shrink-0 flex-col items-center justify-center gap-0.5 rounded-xl"
                :class="availableOnly ? 'quick-action-btn-active' : ''"
                :aria-pressed="availableOnly"
                @click="emit('update:availableOnly', !availableOnly)"
            >
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    stroke="none"
                >
                    <path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z" />
                </svg>
                <span class="quick-action-label">{{ t('availableOnly') }}</span>
            </button>

            <input
                :value="searchQuery"
                type="text"
                placeholder="Пошук послуг або майстрів"
                class="search-input h-full min-w-0 flex-1 rounded-xl px-4 text-sm"
                @input="
                    emit(
                        'update:searchQuery',
                        ($event.target as HTMLInputElement).value,
                    )
                "
            />
        </div>
    </div>
</template>

<style scoped>
.glass-panel {
    position: relative;
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    color: var(--panel-text);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.24);
    backdrop-filter: blur(24px) saturate(160%);
    -webkit-backdrop-filter: blur(24px) saturate(160%);
}

.loading-track {
    overflow: hidden;
    background: var(--loading-track-bg);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.15s ease;
}

.loading-track-visible {
    opacity: 1;
}

.loading-fill {
    background: var(--panel-bg);
    border-radius: 0;
    transition: width 0.2s ease-out;
}

.search-input {
    color: var(--panel-text);
    background: var(--surface-bg);
    border: 1px solid var(--surface-border);
    box-shadow: var(--surface-shadow);
    backdrop-filter: blur(18px) saturate(140%);
    -webkit-backdrop-filter: blur(18px) saturate(140%);
    outline: none;
    transition: border-color 0.12s ease;
}

.search-input:focus {
    border-color: rgba(var(--glass-accent-rgb), 0.35);
}

.search-input::placeholder {
    color: var(--panel-muted-text);
    opacity: 0.75;
}

.quick-action-btn {
    color: var(--panel-muted-text);
    background: var(--surface-bg);
    border: 1px solid var(--surface-border);
    box-shadow: var(--surface-shadow);
    backdrop-filter: blur(18px) saturate(140%);
    -webkit-backdrop-filter: blur(18px) saturate(140%);
    cursor: pointer;
    transition:
        background 0.12s ease,
        color 0.12s ease,
        border-color 0.12s ease;
}

.quick-action-btn:hover {
    background: var(--surface-bg-hover);
    color: var(--panel-text);
}

.quick-action-btn-active {
    background: var(--brand-primary);
    border-color: transparent;
    color: #fff;
}

.quick-action-btn-active:hover {
    background: var(--brand-primary);
    color: #fff;
}

.quick-action-label {
    font-size: 10px;
    line-height: 1;
    font-weight: 600;
    white-space: nowrap;
}

.app-download-cta {
    background: var(--brand-primary);
    border: 1px solid rgba(255, 255, 255, 0.18);
    text-decoration: none;
    animation: app-cta-pulse 1.8s infinite ease-in-out;
    box-shadow:
        0 0 0 0 rgba(var(--brand-primary-rgb), 0.55),
        0 0 18px rgba(var(--brand-primary-rgb), 0.42);
    transition: opacity 0.12s ease;
}

.app-download-cta:hover {
    opacity: 0.88;
}

.become-master-cta {
    background: transparent;
    border: 1px solid rgba(var(--brand-primary-rgb), 0.55);
    color: var(--brand-primary);
    cursor: pointer;
    transition:
        background 0.12s ease,
        color 0.12s ease;
}

.become-master-cta:hover {
    background: var(--brand-primary);
    color: #fff;
}

@keyframes app-cta-pulse {
    0% {
        box-shadow:
            0 0 0 0 rgba(var(--brand-primary-rgb), 0.6),
            0 0 16px rgba(var(--brand-primary-rgb), 0.38);
    }
    50% {
        box-shadow:
            0 0 0 14px rgba(var(--brand-primary-rgb), 0.12),
            0 0 28px rgba(var(--brand-primary-rgb), 0.62);
    }
    100% {
        box-shadow:
            0 0 0 20px rgba(var(--brand-primary-rgb), 0),
            0 0 18px rgba(var(--brand-primary-rgb), 0.22);
    }
}
</style>
