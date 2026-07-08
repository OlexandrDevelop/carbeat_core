<script setup lang="ts">
import type { Lang, UiTextKey } from '@/composables/useGuestLang';
import type { Flavor } from '@/types/guest-map';
import { computed, ref } from 'vue';

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
const showServiceFilter = ref(false);

function selectService(id: number | null): void {
    emit('update:selectedServiceId', id);
    showServiceFilter.value = false;
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

        <div class="mb-2 flex items-stretch gap-2">
            <button
                type="button"
                class="available-toggle-btn flex w-11 shrink-0 items-center justify-center rounded-xl"
                :class="availableOnly ? 'available-toggle-btn-active' : ''"
                :aria-label="t('availableOnly')"
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
            </button>

            <div class="relative flex-1">
                <input
                    :value="searchQuery"
                    type="text"
                    placeholder="Пошук послуг або майстрів"
                    class="search-input h-full w-full rounded-xl py-2.5 pl-4 pr-12 text-sm"
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

            <button
                type="button"
                class="service-filter-btn flex w-11 shrink-0 items-center justify-center rounded-xl"
                :class="
                    selectedServiceId !== null
                        ? 'service-filter-btn-active'
                        : ''
                "
                :aria-label="t('filters')"
                :aria-expanded="showServiceFilter"
                @click="showServiceFilter = !showServiceFilter"
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
                    <line x1="4" y1="6" x2="20" y2="6" />
                    <line x1="4" y1="12" x2="20" y2="12" />
                    <line x1="4" y1="18" x2="20" y2="18" />
                    <circle
                        cx="9"
                        cy="6"
                        r="2"
                        fill="currentColor"
                        stroke="none"
                    />
                    <circle
                        cx="16"
                        cy="12"
                        r="2"
                        fill="currentColor"
                        stroke="none"
                    />
                    <circle
                        cx="11"
                        cy="18"
                        r="2"
                        fill="currentColor"
                        stroke="none"
                    />
                </svg>
            </button>
        </div>

        <Transition name="service-filter-expand">
            <div
                v-if="showServiceFilter"
                class="service-filter-panel rounded-xl"
            >
                <div
                    class="service-filter-panel-inner max-h-64 overflow-y-auto p-1.5"
                >
                    <div
                        class="service-filter-header px-2.5 py-1.5 text-[11px] font-bold uppercase tracking-wide"
                    >
                        {{ t('filters') }}
                    </div>
                    <button
                        type="button"
                        class="service-filter-item"
                        :class="
                            selectedServiceId === null
                                ? 'service-filter-item-active'
                                : ''
                        "
                        @click="selectService(null)"
                    >
                        {{ t('allServices') }}
                    </button>
                    <button
                        v-for="service in services"
                        :key="service.id"
                        type="button"
                        class="service-filter-item"
                        :class="
                            selectedServiceId === service.id
                                ? 'service-filter-item-active'
                                : ''
                        "
                        @click="selectService(service.id)"
                    >
                        {{ service.label }}
                    </button>
                </div>
            </div>
        </Transition>

        <div v-if="geoErrorMessage" class="text-xs font-medium text-red-600">
            {{ geoErrorMessage }}
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

.available-toggle-btn {
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

.available-toggle-btn:hover {
    background: var(--surface-bg-hover);
    color: var(--panel-text);
}

.available-toggle-btn-active {
    background: var(--brand-primary);
    border-color: transparent;
    color: #fff;
}

.available-toggle-btn-active:hover {
    background: var(--brand-primary);
    color: #fff;
}

.service-filter-btn {
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

.service-filter-btn:hover {
    background: var(--surface-bg-hover);
    color: var(--panel-text);
}

.service-filter-btn-active {
    background: var(--brand-primary);
    border-color: transparent;
    color: #fff;
}

.service-filter-btn-active:hover {
    background: var(--brand-primary);
    color: #fff;
}

.service-filter-panel {
    background: var(--dropdown-bg);
    border: 1px solid var(--panel-border);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35);
    overflow: hidden;
}

.service-filter-panel-inner {
    scrollbar-width: thin;
}

.service-filter-header {
    color: var(--panel-muted-text);
}

.service-filter-item {
    display: block;
    width: 100%;
    text-align: left;
    padding: 0.5rem 0.625rem;
    border-radius: 0.75rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--panel-text);
    cursor: pointer;
    transition:
        background 0.12s ease,
        color 0.12s ease;
}

.service-filter-item:hover {
    background: var(--surface-bg-hover);
}

.service-filter-item-active {
    background: var(--brand-primary);
    color: #fff;
}

.service-filter-item-active:hover {
    background: var(--brand-primary);
    color: #fff;
}

.service-filter-expand-enter-active,
.service-filter-expand-leave-active {
    transition:
        max-height 0.22s ease,
        opacity 0.18s ease,
        margin-bottom 0.22s ease;
}

.service-filter-expand-enter-from,
.service-filter-expand-leave-to {
    max-height: 0;
    opacity: 0;
    margin-bottom: 0;
}

.service-filter-expand-enter-to,
.service-filter-expand-leave-from {
    max-height: 20rem;
    opacity: 1;
    margin-bottom: 0.5rem;
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
