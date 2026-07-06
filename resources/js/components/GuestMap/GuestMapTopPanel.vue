<script setup lang="ts">
import type { Lang, UiTextKey } from '@/composables/useGuestLang';
import type { Flavor } from '@/types/guest-map';
import { computed } from 'vue';

interface ServiceOption {
    id: number;
    name: string;
    label: string;
}

const props = defineProps<{
    flavor: Flavor;
    currentLang: Lang;
    services: ServiceOption[];
    selectedServiceId: number | null;
    availableOnly: boolean;
    searchQuery: string;
    brandName: string;
    mobileAppUrl: string;
    geoErrorMessage?: string | null;
    t: (key: UiTextKey) => string;
}>();

const emit = defineEmits<{
    'update:selectedServiceId': [id: number | null];
    'update:availableOnly': [val: boolean];
    'update:searchQuery': [val: string];
    setLanguage: [lang: Lang];
    useMyLocation: [];
}>();

const isFloxcity = computed(() => props.flavor === 'floxcity');

function toggleService(id: number): void {
    emit(
        'update:selectedServiceId',
        props.selectedServiceId === id ? null : id,
    );
}
</script>

<template>
    <div class="glass-panel rounded-2xl p-3">
        <div class="mb-2 flex items-center justify-between gap-2">
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

        <div class="relative mb-2">
            <input
                :value="searchQuery"
                type="text"
                placeholder="Пошук послуг або майстрів"
                class="search-input w-full rounded-xl py-2.5 pl-4 pr-12 text-sm"
                @input="
                    emit(
                        'update:searchQuery',
                        ($event.target as HTMLInputElement).value,
                    )
                "
            />
            <button
                type="button"
                class="search-filter-btn absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg"
                :aria-label="t('myGeo')"
                @click="emit('useMyLocation')"
            >
                <svg
                    class="h-4 w-4 text-white"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <circle cx="12" cy="12" r="3" />
                    <path
                        d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"
                    />
                </svg>
            </button>
        </div>

        <div
            v-if="geoErrorMessage"
            class="mb-2 text-xs font-medium text-red-600"
        >
            {{ geoErrorMessage }}
        </div>

        <div class="chips-scroll flex gap-2 overflow-x-auto pb-1">
            <button
                type="button"
                class="filter-chip"
                :class="availableOnly ? 'filter-chip-active' : ''"
                @click="emit('update:availableOnly', !availableOnly)"
            >
                <span
                    class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full"
                    :class="availableOnly ? 'bg-emerald-400' : 'bg-slate-400'"
                ></span>
                Відкрито
            </button>
            <button
                v-for="service in services"
                :key="service.id"
                type="button"
                class="filter-chip"
                :class="
                    selectedServiceId === service.id ? 'filter-chip-active' : ''
                "
                @click="toggleService(service.id)"
            >
                {{ service.label }}
            </button>
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

.search-filter-btn {
    background: #ff5722;
    border: none;
    cursor: pointer;
    transition: opacity 0.12s ease;
}

.search-filter-btn:hover {
    opacity: 0.88;
}

.chips-scroll {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.chips-scroll::-webkit-scrollbar {
    display: none;
}

.filter-chip {
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    padding: 0.375rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    background: var(--surface-bg);
    border: 1px solid var(--surface-border);
    color: var(--panel-muted-text);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    transition:
        background 0.12s ease,
        border-color 0.12s ease,
        color 0.12s ease;
}

.filter-chip:hover {
    background: var(--surface-bg-hover);
    color: var(--panel-text);
}

.filter-chip-active {
    background: var(--cluster-bg);
    border-color: transparent;
    color: #fff;
}

.filter-chip-active:hover {
    background: var(--cluster-bg);
    color: #fff;
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
