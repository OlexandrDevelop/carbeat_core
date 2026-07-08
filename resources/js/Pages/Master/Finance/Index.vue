<template>
    <div>
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <h1 class="text-lg font-extrabold text-slate-900">Фінанси</h1>
            <GlassPanel
                variant="surface"
                rounded="xl"
                padding="sm"
                class="flex items-center gap-2"
            >
                <input
                    v-model="from"
                    type="date"
                    class="bg-transparent font-mono text-sm"
                />
                <span class="text-slate-400">—</span>
                <input
                    v-model="to"
                    type="date"
                    class="bg-transparent font-mono text-sm"
                />
                <button
                    type="button"
                    class="ml-2 rounded-lg px-3 py-1 text-xs font-semibold text-slate-600"
                    :style="{ backgroundColor: 'var(--surface-bg-hover)' }"
                    @click="load"
                >
                    Оновити
                </button>
            </GlassPanel>
        </div>

        <p
            v-if="error"
            class="mb-3 text-sm font-semibold"
            style="color: var(--status-busy)"
        >
            {{ error }}
        </p>
        <div v-if="isLoading" class="py-16 text-center text-slate-500">
            Завантаження…
        </div>

        <div v-else-if="report" class="space-y-6">
            <section>
                <h2
                    class="mb-2 text-sm font-bold uppercase tracking-wide text-slate-500"
                >
                    Каса
                </h2>
                <div
                    class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4"
                >
                    <MetricTile
                        title="Готівка"
                        :value="`${report.cash.cashRevenue} ₴`"
                    />
                    <MetricTile
                        title="Картка"
                        :value="`${report.cash.cardRevenue} ₴`"
                    />
                    <MetricTile
                        title="QR"
                        :value="`${report.cash.qrRevenue} ₴`"
                    />
                    <MetricTile
                        title="Частково оплачено"
                        :value="String(report.cash.partialOrders)"
                        :subtitle="`${report.cash.partialOutstanding} ₴ залишок`"
                    />
                    <MetricTile
                        title="Не оплачено"
                        :value="String(report.cash.pendingOrders)"
                        :subtitle="`${report.cash.pendingAmount} ₴`"
                    />
                    <MetricTile
                        title="Борги"
                        :value="String(report.cash.debtOrders)"
                        :subtitle="`${report.cash.debtAmount} ₴`"
                    />
                    <MetricTile
                        title="Отримано"
                        :value="`${report.cash.paidRevenue} ₴`"
                    />
                    <MetricTile
                        title="Залишок"
                        :value="`${report.cash.outstandingRevenue} ₴`"
                    />
                </div>
            </section>

            <section>
                <h2
                    class="mb-2 text-sm font-bold uppercase tracking-wide text-slate-500"
                >
                    Прибутковість
                </h2>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <MetricTile
                        title="Виручка"
                        :value="`${report.profitability.totalRevenue} ₴`"
                        :subtitle="`${report.profitability.completedOrders} робіт`"
                    />
                    <MetricTile
                        title="Середній чек"
                        :value="`${report.profitability.averageCheck} ₴`"
                    />
                    <MetricTile
                        title="Топ бокс"
                        :value="report.profitability.topBayLabel"
                        :subtitle="`${report.profitability.topBayRevenue} ₴`"
                    />
                    <MetricTile
                        title="Топ технік"
                        :value="report.profitability.topTechnicianLabel"
                        :subtitle="`${report.profitability.topTechnicianRevenue} ₴`"
                    />
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <GlassPanel variant="surface" rounded="xl">
                        <h3
                            class="mb-2 text-xs font-bold uppercase text-slate-500"
                        >
                            По боксах
                        </h3>
                        <div
                            v-for="(value, label) in report.profitability
                                .revenueByBay"
                            :key="label"
                            class="flex justify-between py-1 text-sm"
                        >
                            <span class="text-slate-700">{{ label }}</span>
                            <span class="font-mono font-semibold text-slate-800"
                                >{{ value }} ₴</span
                            >
                        </div>
                    </GlassPanel>
                    <GlassPanel variant="surface" rounded="xl">
                        <h3
                            class="mb-2 text-xs font-bold uppercase text-slate-500"
                        >
                            По майстрах
                        </h3>
                        <div
                            v-for="(value, label) in report.profitability
                                .revenueByTechnician"
                            :key="label"
                            class="flex justify-between py-1 text-sm"
                        >
                            <span class="text-slate-700">{{ label }}</span>
                            <span class="font-mono font-semibold text-slate-800"
                                >{{ value }} ₴</span
                            >
                        </div>
                    </GlassPanel>
                </div>
            </section>

            <section>
                <h2
                    class="mb-2 text-sm font-bold uppercase tracking-wide text-slate-500"
                >
                    KPI
                </h2>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <MetricTile
                        title="Виконано робіт"
                        :value="String(report.kpi.completedOrders)"
                    />
                    <MetricTile
                        title="Середній чек"
                        :value="`${report.kpi.averageCheck} ₴`"
                    />
                    <MetricTile
                        title="Частково оплачено"
                        :value="String(report.kpi.partialOrders)"
                    />
                    <MetricTile
                        title="Борги"
                        :value="String(report.kpi.debtOrders)"
                        :subtitle="`${report.kpi.debtAmount} ₴`"
                    />
                </div>
            </section>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { defineComponent, h, onMounted, ref } from 'vue';
import GlassPanel from '../../../components/MasterCrm/GlassPanel.vue';
import type { FinanceReport } from '../../../types/master-crm';

const MetricTile = defineComponent({
    props: { title: String, value: String, subtitle: String },
    setup(props) {
        return () =>
            h(
                GlassPanel,
                { variant: 'surface', rounded: 'xl', padding: 'sm' },
                () => [
                    h(
                        'p',
                        { class: 'text-xs font-semibold text-slate-500' },
                        props.title,
                    ),
                    h(
                        'p',
                        { class: 'mt-1 text-lg font-extrabold text-slate-900' },
                        props.value,
                    ),
                    props.subtitle
                        ? h(
                              'p',
                              {
                                  class: 'mt-0.5 font-mono text-[11px] font-semibold text-slate-400',
                              },
                              props.subtitle,
                          )
                        : null,
                ],
            );
    },
});

function toDateInput(date: Date): string {
    return date.toISOString().slice(0, 10);
}

const today = new Date();
const monthAgo = new Date();
monthAgo.setDate(monthAgo.getDate() - 30);

const from = ref(toDateInput(monthAgo));
const to = ref(toDateInput(today));
const report = ref<FinanceReport | null>(null);
const isLoading = ref(false);
const error = ref<string | null>(null);

async function load() {
    isLoading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get<FinanceReport>(
            '/master-api/crm/finance',
            {
                params: { from: from.value, to: to.value },
            },
        );
        report.value = data;
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ?? 'Не вдалося завантажити фінанси';
    } finally {
        isLoading.value = false;
    }
}

onMounted(load);
</script>
