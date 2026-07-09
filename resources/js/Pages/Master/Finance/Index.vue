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
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-bold"
                    @click="shiftPeriod(-1)"
                >
                    ‹
                </button>
                <span
                    class="min-w-[150px] text-center font-mono text-sm font-bold text-slate-800"
                >
                    {{ periodLabel }}
                </span>
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-bold"
                    @click="shiftPeriod(1)"
                >
                    ›
                </button>
            </GlassPanel>

            <GlassPanel
                variant="surface"
                rounded="xl"
                padding="sm"
                class="flex items-center gap-1"
            >
                <button
                    v-for="preset in presets"
                    :key="preset.key"
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-xs font-bold"
                    :class="
                        activePreset === preset.key
                            ? 'bg-white/60 text-slate-900'
                            : 'text-slate-500'
                    "
                    @click="applyPreset(preset.key)"
                >
                    {{ preset.label }}
                </button>
            </GlassPanel>

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
import { computed, defineComponent, h, onMounted, ref, watch } from 'vue';
import GlassPanel from '../../../components/MasterCrm/GlassPanel.vue';
import { toDateInput } from '../../../lib/date';
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

function addDays(date: Date, days: number): Date {
    const d = new Date(date);
    d.setDate(d.getDate() + days);
    return d;
}

type PresetKey = 'today' | 'week' | 'month';

const presets: { key: PresetKey; label: string }[] = [
    { key: 'today', label: 'Сьогодні' },
    { key: 'week', label: 'Тиждень' },
    { key: 'month', label: 'Місяць' },
];

const today = new Date();
const monthAgo = addDays(today, -29);

const from = ref(toDateInput(monthAgo));
const to = ref(toDateInput(today));
const activePreset = ref<PresetKey | null>('month');
const report = ref<FinanceReport | null>(null);
const isLoading = ref(false);
const error = ref<string | null>(null);

function applyPreset(key: PresetKey) {
    const end = new Date();
    const start =
        key === 'today'
            ? new Date()
            : key === 'week'
              ? addDays(end, -6)
              : addDays(end, -29);

    activePreset.value = key;
    from.value = toDateInput(start);
    to.value = toDateInput(end);
}

function shiftPeriod(direction: 1 | -1) {
    const start = new Date(`${from.value}T00:00:00`);
    const end = new Date(`${to.value}T00:00:00`);
    const spanDays =
        Math.round((end.getTime() - start.getTime()) / 86_400_000) + 1;

    activePreset.value = null;
    from.value = toDateInput(addDays(start, direction * spanDays));
    to.value = toDateInput(addDays(end, direction * spanDays));
}

const periodLabel = computed(() => {
    const fmt = (iso: string) =>
        new Date(`${iso}T00:00:00`).toLocaleDateString('uk-UA', {
            day: '2-digit',
            month: 'short',
        });
    return from.value === to.value
        ? fmt(from.value)
        : `${fmt(from.value)} – ${fmt(to.value)}`;
});

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

watch([from, to], load);
onMounted(load);
</script>
