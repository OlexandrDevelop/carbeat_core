<template>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[380px_1fr]">
        <GlassPanel padding="none" class="flex max-h-[80vh] flex-col overflow-hidden">
            <div class="flex gap-1 overflow-x-auto p-3">
                <button
                    v-for="tabItem in tabs"
                    :key="tabItem.key"
                    type="button"
                    class="flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                    :class="
                        tab === tabItem.key
                            ? 'text-white'
                            : 'glass-surface text-slate-600 hover:bg-white/50'
                    "
                    :style="
                        tab === tabItem.key
                            ? { backgroundColor: 'var(--brand-primary)' }
                            : {}
                    "
                    @click="selectTab(tabItem.key)"
                >
                    {{ tabItem.label }}
                    <span
                        class="rounded-full px-1.5 text-[11px]"
                        :class="
                            tab === tabItem.key
                                ? 'bg-white/25'
                                : 'bg-slate-900/10 text-slate-500'
                        "
                    >
                        {{ counts[tabItem.key] ?? 0 }}
                    </span>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto border-t border-white/40">
                <p
                    v-if="isLoading && items.length === 0"
                    class="p-4 text-center text-xs text-slate-400"
                >
                    Завантаження…
                </p>
                <p
                    v-else-if="items.length === 0"
                    class="p-4 text-center text-xs text-slate-400"
                >
                    Заявок немає
                </p>
                <button
                    v-for="item in items"
                    :key="item.id"
                    type="button"
                    class="block w-full border-t border-white/40 px-4 py-3 text-left text-sm transition first:border-t-0"
                    :class="
                        selectedId === item.id ? 'bg-white/50' : 'hover:bg-white/30'
                    "
                    @click="selectRequest(item.id)"
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="font-semibold text-slate-800">
                            {{ item.car_make }}
                            <template v-if="item.car_model"
                                >{{ item.car_model }}
                            </template>
                            <span
                                v-if="item.car_year"
                                class="font-normal text-slate-400"
                                >({{ item.car_year }})</span
                            >
                        </p>
                        <StatusBadge :status="item.status" />
                    </div>
                    <p class="mt-0.5 truncate text-xs text-slate-500">
                        {{ item.description }}
                    </p>
                    <p class="mt-1 text-[11px] text-slate-400">
                        {{ formatRelative(item.created_at) }}
                    </p>
                </button>
            </div>

            <div
                v-if="lastPage > 1"
                class="flex items-center justify-between border-t border-white/40 px-3 py-2"
            >
                <button
                    type="button"
                    :disabled="page <= 1"
                    class="rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-600 disabled:opacity-40"
                    @click="goToPage(page - 1)"
                >
                    ‹ Назад
                </button>
                <span class="text-[11px] text-slate-400"
                    >{{ page }} / {{ lastPage }}</span
                >
                <button
                    type="button"
                    :disabled="page >= lastPage"
                    class="rounded-lg px-2.5 py-1 text-xs font-semibold text-slate-600 disabled:opacity-40"
                    @click="goToPage(page + 1)"
                >
                    Далі ›
                </button>
            </div>
        </GlassPanel>

        <GlassPanel v-if="selected" class="space-y-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-slate-500">
                        Заявка №{{ selected.id }} ·
                        {{ formatRelative(selected.created_at) }}
                    </p>
                    <h1 class="text-lg font-extrabold text-slate-900">
                        {{ selected.car_make
                        }}<template v-if="selected.car_model"
                            >{{ ' ' }}{{ selected.car_model }}</template
                        >
                        <template v-if="selected.car_year"
                            >({{ selected.car_year }})</template
                        >
                    </h1>
                    <p v-if="selected.service_name" class="text-xs text-slate-500">
                        {{ selected.service_name }}
                    </p>
                </div>
                <StatusBadge :status="selected.status" />
            </div>

            <div>
                <p class="mb-1 text-xs font-semibold text-slate-500">
                    Опис проблеми
                </p>
                <p class="text-sm text-slate-800">{{ selected.description }}</p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <p class="mb-1 text-xs font-semibold text-slate-500">
                        Клієнт
                    </p>
                    <p class="text-sm font-semibold text-slate-800">
                        {{ selected.name }}
                    </p>
                </div>
                <div>
                    <p class="mb-1 text-xs font-semibold text-slate-500">
                        Телефон
                    </p>
                    <a
                        :href="`tel:${selected.phone}`"
                        class="text-sm font-semibold"
                        :style="{ color: 'var(--brand-primary)' }"
                    >
                        {{ selected.phone }}
                    </a>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
                <a
                    :href="`tel:${selected.phone}`"
                    class="rounded-xl px-4 py-2.5 text-center text-sm font-semibold text-white"
                    :style="{ backgroundColor: 'var(--brand-primary)' }"
                >
                    Зателефонувати
                </a>
                <button
                    v-if="selected.status !== 'called'"
                    type="button"
                    :disabled="isUpdating"
                    class="glass-surface rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-50"
                    @click="setStatus('called')"
                >
                    Відмітити, що дзвонив
                </button>
                <button
                    v-if="selected.status !== 'rejected'"
                    type="button"
                    :disabled="isUpdating"
                    class="rounded-xl px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 disabled:opacity-50"
                    @click="setStatus('rejected')"
                >
                    Відхилити
                </button>
                <button
                    v-if="selected.status !== 'pending'"
                    type="button"
                    :disabled="isUpdating"
                    class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-500 hover:bg-white/40 disabled:opacity-50"
                    @click="setStatus('pending')"
                >
                    Повернути в нові
                </button>
            </div>
        </GlassPanel>

        <GlassPanel v-else class="flex items-center justify-center text-sm text-slate-400">
            Обери заявку зі списку
        </GlassPanel>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import GlassPanel from '@/components/MasterCrm/GlassPanel.vue';
import StatusBadge from './StatusBadge.vue';

type RequestStatus = 'pending' | 'called' | 'rejected';

interface RepairRequestItem {
    id: number;
    car_make: string;
    car_model: string | null;
    car_year: number | null;
    description: string;
    name: string;
    phone: string;
    service_name: string | null;
    created_at: string | null;
    status: RequestStatus;
}

const props = defineProps<{
    initialSelectedId: number | null;
}>();

const tabs: { key: string; label: string }[] = [
    { key: 'all', label: 'Всі' },
    { key: 'new', label: 'Нові' },
    { key: 'active', label: 'Активні' },
    { key: 'called', label: 'Дзвонив' },
    { key: 'rejected', label: 'Відхилені' },
];

const tab = ref('all');
const items = ref<RepairRequestItem[]>([]);
const counts = ref<Record<string, number>>({});
const page = ref(1);
const lastPage = ref(1);
const isLoading = ref(false);
const isUpdating = ref(false);
const selectedId = ref<number | null>(props.initialSelectedId);

const selected = computed(
    () => items.value.find((item) => item.id === selectedId.value) ?? null,
);

async function fetchData(targetPage = 1) {
    isLoading.value = true;
    try {
        const response = await axios.get('/master-api/repair-requests', {
            params: { tab: tab.value, page: targetPage },
        });
        items.value = response.data.data;
        page.value = response.data.current_page;
        lastPage.value = response.data.last_page;
        counts.value = response.data.counts;

        if (
            selectedId.value !== null &&
            !items.value.some((item) => item.id === selectedId.value)
        ) {
            // Deep-linked request isn't on this page/tab (e.g. someone else
            // already acted on it) — don't leave a dangling empty detail panel.
            selectedId.value = items.value[0]?.id ?? null;
        } else if (selectedId.value === null && items.value.length > 0) {
            selectedId.value = items.value[0].id;
        }
    } finally {
        isLoading.value = false;
    }
}

function selectTab(key: string) {
    tab.value = key;
}

function selectRequest(id: number) {
    selectedId.value = id;
}

function goToPage(target: number) {
    if (target < 1 || target > lastPage.value) return;
    fetchData(target);
}

async function setStatus(status: RequestStatus) {
    if (!selected.value) return;
    isUpdating.value = true;
    try {
        await axios.patch(
            `/master-api/repair-requests/${selected.value.id}/status`,
            { status },
        );
        await fetchData(page.value);
    } finally {
        isUpdating.value = false;
    }
}

function formatRelative(value: string | null): string {
    if (!value) return '';
    const diffMs = Date.now() - new Date(value).getTime();
    const diffMin = Math.round(diffMs / 60000);
    if (diffMin < 1) return 'щойно';
    if (diffMin < 60) return `${diffMin} хв тому`;
    const diffHours = Math.round(diffMin / 60);
    if (diffHours < 24) return `${diffHours} год тому`;
    const diffDays = Math.round(diffHours / 24);
    return `${diffDays} дн тому`;
}

watch(tab, () => fetchData(1));
onMounted(() => fetchData(1));
</script>
