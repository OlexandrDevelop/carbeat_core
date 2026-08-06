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
                <span class="text-sm text-gray-500"
                    >Всього: {{ total }}</span
                >
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="isLoading">
                                <td
                                    colspan="6"
                                    class="px-6 py-8 text-center text-sm text-gray-500"
                                >
                                    Завантаження…
                                </td>
                            </tr>
                            <tr v-else-if="requests.length === 0">
                                <td
                                    colspan="6"
                                    class="px-6 py-8 text-center text-sm text-gray-500"
                                >
                                    Заявок поки немає
                                </td>
                            </tr>
                            <tr
                                v-for="item in requests"
                                :key="item.id"
                                class="hover:bg-gray-50"
                            >
                                <td
                                    class="whitespace-nowrap px-6 py-3 text-sm text-gray-600"
                                >
                                    {{ formatDate(item.created_at) }}
                                </td>
                                <td
                                    class="px-6 py-3 text-sm font-medium text-gray-900"
                                >
                                    {{ item.name }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-6 py-3 font-mono text-sm text-gray-600"
                                >
                                    {{ item.phone }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">
                                    {{ formatCar(item) }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">
                                    {{ item.service_name ?? 'Інше' }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">
                                    <div class="max-w-sm truncate">
                                        {{ item.description }}
                                    </div>
                                </td>
                            </tr>
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

interface RepairRequestRow {
    id: number;
    name: string;
    phone: string;
    car_make: string;
    car_model: string | null;
    car_year: string | null;
    description: string;
    service_name: string | null;
    created_at: string | null;
}

const requests = ref<RepairRequestRow[]>([]);
const total = ref(0);
const page = ref(1);
const lastPage = ref(1);
const isLoading = ref(false);

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
