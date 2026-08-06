<template>
    <div class="min-h-screen bg-gray-50">
        <header
            class="sticky top-0 z-10 border-b border-gray-200 bg-white/70 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4"
            >
                <h1 class="text-2xl font-semibold text-gray-900">
                    Заявки на ремонт
                </h1>
                <span class="text-sm text-gray-500">Всього: {{ total }}</span>
            </div>
        </header>

        <main class="mx-auto max-w-7xl space-y-6 px-6 py-6">
            <section
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Дата
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Ім'я
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Телефон
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Місто
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Авто
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Тип поломки
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Опис
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Статус
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                >
                                    Дії
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="isLoading">
                                <td
                                    colspan="9"
                                    class="px-6 py-8 text-center text-sm text-gray-500"
                                >
                                    Завантаження…
                                </td>
                            </tr>
                            <tr v-else-if="requests.length === 0">
                                <td
                                    colspan="9"
                                    class="px-6 py-8 text-center text-sm text-gray-500"
                                >
                                    Заявок поки немає
                                </td>
                            </tr>
                            <template v-for="item in requests" :key="item.id">
                                <tr class="hover:bg-gray-50">
                                    <td
                                        class="cursor-pointer whitespace-nowrap px-6 py-3 text-sm text-gray-600"
                                        @click="toggleExpanded(item.id)"
                                    >
                                        {{ formatDate(item.created_at) }}
                                    </td>
                                    <td
                                        class="cursor-pointer px-6 py-3 text-sm font-medium text-gray-900"
                                        @click="toggleExpanded(item.id)"
                                    >
                                        {{ item.name }}
                                    </td>
                                    <td
                                        class="cursor-pointer whitespace-nowrap px-6 py-3 font-mono text-sm text-gray-600"
                                        @click="toggleExpanded(item.id)"
                                    >
                                        {{ item.phone }}
                                    </td>
                                    <td
                                        class="cursor-pointer px-6 py-3 text-sm text-gray-600"
                                        @click="toggleExpanded(item.id)"
                                    >
                                        {{ item.city ?? '—' }}
                                    </td>
                                    <td
                                        class="cursor-pointer px-6 py-3 text-sm text-gray-600"
                                        @click="toggleExpanded(item.id)"
                                    >
                                        {{ formatCar(item) }}
                                    </td>
                                    <td
                                        class="cursor-pointer px-6 py-3 text-sm text-gray-600"
                                        @click="toggleExpanded(item.id)"
                                    >
                                        {{ item.service_name ?? 'Інше' }}
                                    </td>
                                    <td
                                        class="cursor-pointer px-6 py-3 text-sm text-gray-600"
                                        @click="toggleExpanded(item.id)"
                                    >
                                        <div class="max-w-sm truncate">
                                            {{ item.description }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-sm">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                            :class="
                                                statusBadgeClass(item.status)
                                            "
                                        >
                                            {{ statusLabel(item.status) }}
                                        </span>
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-6 py-3 text-sm"
                                    >
                                        <div
                                            v-if="item.status === 'pending'"
                                            class="flex items-center gap-2"
                                        >
                                            <button
                                                type="button"
                                                :disabled="
                                                    moderating === item.id
                                                "
                                                class="rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white disabled:opacity-50"
                                                @click="approve(item)"
                                            >
                                                Затвердити
                                            </button>
                                            <button
                                                type="button"
                                                :disabled="
                                                    moderating === item.id
                                                "
                                                class="rounded-lg border border-red-300 px-2.5 py-1 text-xs font-semibold text-red-600 disabled:opacity-50"
                                                @click="reject(item)"
                                            >
                                                Відхилити
                                            </button>
                                        </div>
                                        <button
                                            type="button"
                                            class="text-sky-600"
                                            @click="toggleExpanded(item.id)"
                                        >
                                            {{
                                                expandedId === item.id
                                                    ? 'Сховати ▲'
                                                    : 'Майстри ▼'
                                            }}
                                        </button>
                                    </td>
                                </tr>
                                <tr
                                    v-if="expandedId === item.id"
                                    class="bg-gray-50"
                                >
                                    <td colspan="9" class="px-6 py-4">
                                        <p
                                            v-if="
                                                matchedMasters[item.id] ===
                                                'loading'
                                            "
                                            class="text-sm text-gray-500"
                                        >
                                            Завантаження…
                                        </p>
                                        <p
                                            v-else-if="
                                                !matchedMasters[item.id] ||
                                                (
                                                    matchedMasters[
                                                        item.id
                                                    ] as MatchedMaster[]
                                                ).length === 0
                                            "
                                            class="text-sm text-gray-500"
                                        >
                                            Жодного майстра не підібрано (нема
                                            послуги або немає майстрів у радіусі
                                            50 км).
                                        </p>
                                        <table
                                            v-else
                                            class="min-w-full text-sm"
                                        >
                                            <thead>
                                                <tr
                                                    class="text-left text-xs uppercase text-gray-500"
                                                >
                                                    <th class="py-1 pr-4">
                                                        Майстер
                                                    </th>
                                                    <th class="py-1 pr-4">
                                                        Місто
                                                    </th>
                                                    <th class="py-1 pr-4">
                                                        Відстань
                                                    </th>
                                                    <th class="py-1 pr-4">
                                                        Канал
                                                    </th>
                                                    <th class="py-1 pr-4">
                                                        SMS-запрошень
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody
                                                class="divide-y divide-gray-100"
                                            >
                                                <tr
                                                    v-for="master in matchedMasters[
                                                        item.id
                                                    ] as MatchedMaster[]"
                                                    :key="master.id"
                                                >
                                                    <td
                                                        class="py-1.5 pr-4 font-medium text-gray-900"
                                                    >
                                                        {{ master.name }}
                                                    </td>
                                                    <td
                                                        class="py-1.5 pr-4 text-gray-600"
                                                    >
                                                        {{ master.city ?? '—' }}
                                                    </td>
                                                    <td
                                                        class="py-1.5 pr-4 text-gray-600"
                                                    >
                                                        {{
                                                            master.distance_km !==
                                                            null
                                                                ? `${master.distance_km} км`
                                                                : '—'
                                                        }}
                                                    </td>
                                                    <td class="py-1.5 pr-4">
                                                        <span
                                                            class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                                            :class="
                                                                channelBadgeClass(
                                                                    master.channel,
                                                                )
                                                            "
                                                        >
                                                            {{
                                                                channelLabel(
                                                                    master.channel,
                                                                )
                                                            }}
                                                        </span>
                                                    </td>
                                                    <td
                                                        class="py-1.5 pr-4 text-gray-600"
                                                    >
                                                        {{
                                                            master.sms_invite_count
                                                        }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="lastPage > 1"
                    class="flex items-center justify-between border-t border-gray-200 px-6 py-3"
                >
                    <button
                        type="button"
                        :disabled="page <= 1"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 disabled:opacity-50"
                        @click="goToPage(page - 1)"
                    >
                        Назад
                    </button>
                    <span class="text-sm text-gray-500"
                        >Сторінка {{ page }} з {{ lastPage }}</span
                    >
                    <button
                        type="button"
                        :disabled="page >= lastPage"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 disabled:opacity-50"
                        @click="goToPage(page + 1)"
                    >
                        Далі
                    </button>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref } from 'vue';

type RequestStatus = 'pending' | 'approved' | 'rejected';

interface RepairRequestRow {
    id: number;
    name: string;
    phone: string;
    car_make: string;
    car_model: string | null;
    car_year: string | null;
    description: string;
    service_name: string | null;
    city: string | null;
    status: RequestStatus;
    created_at: string | null;
}

type NotificationChannel =
    | 'telegram'
    | 'sms'
    | 'invite_limit_reached'
    | 'unreachable';

interface MatchedMaster {
    id: number;
    name: string;
    city: string | null;
    distance_km: number | null;
    channel: NotificationChannel;
    sms_invite_count: number;
}

const requests = ref<RepairRequestRow[]>([]);
const total = ref(0);
const page = ref(1);
const lastPage = ref(1);
const isLoading = ref(false);

const expandedId = ref<number | null>(null);
const matchedMasters = ref<Record<number, MatchedMaster[] | 'loading'>>({});

async function toggleExpanded(id: number) {
    if (expandedId.value === id) {
        expandedId.value = null;
        return;
    }

    expandedId.value = id;
    if (matchedMasters.value[id]) return;

    matchedMasters.value[id] = 'loading';
    try {
        const response = await axios.get(
            `/admin-api/repair-requests/${id}/matched-masters`,
        );
        matchedMasters.value[id] = response.data.data;
    } catch (error) {
        console.error('Failed to fetch matched masters:', error);
        delete matchedMasters.value[id];
    }
}

const CHANNEL_LABELS: Record<NotificationChannel, string> = {
    telegram: 'Telegram',
    sms: 'SMS-запрошення',
    invite_limit_reached: 'Ліміт запрошень вичерпано',
    unreachable: 'Недоступний (міський номер)',
};

const CHANNEL_CLASSES: Record<NotificationChannel, string> = {
    telegram: 'bg-emerald-100 text-emerald-700',
    sms: 'bg-sky-100 text-sky-700',
    invite_limit_reached: 'bg-amber-100 text-amber-700',
    unreachable: 'bg-gray-200 text-gray-600',
};

function channelLabel(channel: NotificationChannel): string {
    return CHANNEL_LABELS[channel];
}

function channelBadgeClass(channel: NotificationChannel): string {
    return CHANNEL_CLASSES[channel];
}

const STATUS_LABELS: Record<RequestStatus, string> = {
    pending: 'Очікує підтвердження',
    approved: 'Затверджено',
    rejected: 'Відхилено',
};

const STATUS_CLASSES: Record<RequestStatus, string> = {
    pending: 'bg-amber-100 text-amber-700',
    approved: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-600',
};

function statusLabel(status: RequestStatus): string {
    return STATUS_LABELS[status];
}

function statusBadgeClass(status: RequestStatus): string {
    return STATUS_CLASSES[status];
}

const moderating = ref<number | null>(null);

async function approve(item: RepairRequestRow) {
    moderating.value = item.id;
    try {
        const response = await axios.post(
            `/admin-api/repair-requests/${item.id}/approve`,
        );
        item.status = response.data.status;
        // Notifications just went out based on the current matched-masters
        // query — drop any cached (pre-approval, non-authoritative) preview
        // so re-expanding this row fetches the real post-notify state.
        delete matchedMasters.value[item.id];
    } catch (error) {
        console.error('Failed to approve repair request:', error);
    } finally {
        moderating.value = null;
    }
}

async function reject(item: RepairRequestRow) {
    moderating.value = item.id;
    try {
        const response = await axios.post(
            `/admin-api/repair-requests/${item.id}/reject`,
        );
        item.status = response.data.status;
    } catch (error) {
        console.error('Failed to reject repair request:', error);
    } finally {
        moderating.value = null;
    }
}

async function fetchData(targetPage = 1) {
    isLoading.value = true;
    try {
        const response = await axios.get('/admin-api/repair-requests', {
            params: { page: targetPage },
        });
        requests.value = response.data.data;
        total.value = response.data.total;
        page.value = response.data.current_page;
        lastPage.value = response.data.last_page;
    } catch (error) {
        console.error('Failed to fetch repair requests:', error);
    } finally {
        isLoading.value = false;
    }
}

function formatCar(item: RepairRequestRow): string {
    const car = [item.car_make, item.car_model].filter(Boolean).join(' ');

    return item.car_year ? `${car} (${item.car_year})` : car;
}

function goToPage(target: number) {
    if (target < 1 || target > lastPage.value) return;
    fetchData(target);
}

function formatDate(value: string | null): string {
    if (!value) return '-';
    return new Date(value).toLocaleString('uk-UA', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

onMounted(() => fetchData());
</script>
