<template>
    <div>
        <h1 class="mb-4 text-lg font-extrabold text-slate-900">Всі записи</h1>

        <div class="mb-4 flex flex-wrap items-center gap-3">
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
                    @change="applyFilters"
                />
                <span class="text-slate-400">—</span>
                <input
                    v-model="to"
                    type="date"
                    class="bg-transparent font-mono text-sm"
                    @change="applyFilters"
                />
            </GlassPanel>

            <GlassPanel variant="surface" rounded="xl" padding="sm">
                <select
                    v-model="bayId"
                    class="bg-transparent text-sm"
                    @change="applyFilters"
                >
                    <option value="">Всі боксі</option>
                    <option v-for="bay in bays" :key="bay.id" :value="bay.id">
                        {{ bay.title }}
                    </option>
                </select>
            </GlassPanel>

            <GlassPanel variant="surface" rounded="xl" padding="sm">
                <select
                    v-model="kind"
                    class="bg-transparent text-sm"
                    @change="applyFilters"
                >
                    <option value="">Всі типи</option>
                    <option value="work">В роботі</option>
                    <option value="next">Далі</option>
                    <option value="request">Заявка</option>
                </select>
            </GlassPanel>

            <GlassPanel variant="surface" rounded="xl" padding="sm">
                <select
                    v-model="paymentStatus"
                    class="bg-transparent text-sm"
                    @change="applyFilters"
                >
                    <option value="">Всі оплати</option>
                    <option value="pending">Не оплачено</option>
                    <option value="partial">Частково</option>
                    <option value="paid">Оплачено</option>
                    <option value="debt">Борг</option>
                </select>
            </GlassPanel>

            <GlassPanel
                variant="surface"
                rounded="xl"
                padding="sm"
                class="min-w-[220px] flex-1"
            >
                <input
                    v-model="search"
                    type="text"
                    placeholder="Клієнт, телефон, номер, авто…"
                    class="w-full bg-transparent text-sm"
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

        <div
            v-else-if="items.length === 0"
            class="py-16 text-center text-sm text-slate-400"
        >
            Нічого не знайдено
        </div>

        <GlassPanel v-else padding="none" rounded="xl" class="overflow-hidden">
            <button
                v-for="item in items"
                :key="item.id"
                type="button"
                class="row-item flex w-full items-center gap-3 border-b border-slate-200/60 px-4 py-3 text-left last:border-b-0"
                @click="openEdit(item)"
            >
                <div class="w-24 shrink-0">
                    <p class="font-mono text-xs font-bold text-slate-800">
                        {{ formatDate(item.startsAt) }}
                    </p>
                    <p class="font-mono text-[11px] text-slate-500">
                        {{ formatTime(item.startsAt) }}–{{
                            formatTime(item.endsAt)
                        }}
                    </p>
                </div>

                <div class="w-28 shrink-0 truncate text-xs text-slate-500">
                    {{ item.bayTitle }}
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-slate-900">
                        {{ item.customerName || 'Без імені' }}
                    </p>
                    <p class="truncate text-xs text-slate-500">
                        {{ item.serviceName || '—' }}
                        <span v-if="item.carModel"> · {{ item.carModel }}</span>
                        <span
                            v-if="item.plateNumber"
                            class="font-mono uppercase"
                        >
                            · {{ item.plateNumber }}</span
                        >
                    </p>
                </div>

                <span
                    class="hidden shrink-0 rounded-full px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-wider sm:inline-block"
                    :style="{
                        color: kindColor(item.kind),
                        backgroundColor: kindSoftColor(item.kind),
                    }"
                >
                    {{ kindLabel(item.kind) }}
                </span>

                <span
                    class="hidden shrink-0 rounded-full px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-wider md:inline-block"
                    :style="{
                        color: paymentColor(item.paymentStatus),
                        backgroundColor: paymentSoftColor(item.paymentStatus),
                    }"
                >
                    {{ paymentLabel(item.paymentStatus) }}
                </span>

                <span
                    v-if="item.priceUah !== null"
                    class="w-16 shrink-0 text-right font-mono text-sm font-semibold text-slate-700"
                >
                    {{ item.priceUah }} ₴
                </span>
            </button>
        </GlassPanel>

        <div
            v-if="lastPage > 1"
            class="mt-4 flex items-center justify-center gap-3"
        >
            <button
                type="button"
                class="glass-surface rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 disabled:opacity-40"
                :disabled="currentPage <= 1"
                @click="goToPage(currentPage - 1)"
            >
                ‹ Назад
            </button>
            <span class="font-mono text-xs font-bold text-slate-500">
                {{ currentPage }} / {{ lastPage }} ({{ total }})
            </span>
            <button
                type="button"
                class="glass-surface rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-600 disabled:opacity-40"
                :disabled="currentPage >= lastPage"
                @click="goToPage(currentPage + 1)"
            >
                Далі ›
            </button>
        </div>

        <AppointmentModal
            v-if="showModal && editingAppointment"
            :bays="bays"
            :clients="clients"
            :service-catalog="serviceCatalog"
            :appointment="editingAppointment"
            @close="closeModal"
            @save="handleSave"
            @cancel-appointment="handleCancelAppointment"
        />
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref, watch } from 'vue';
import AppointmentModal from '../../../components/MasterCrm/AppointmentModal.vue';
import GlassPanel from '../../../components/MasterCrm/GlassPanel.vue';
import { useMasterCrm } from '../../../composables/useMasterCrm';
import { toDateInput } from '../../../lib/date';
import type {
    AppointmentKind,
    AppointmentsPage,
    CrmAppointment,
    CrmBay,
    CrmChange,
    CrmClient,
    CrmServiceCatalogItem,
    PaymentStatus,
} from '../../../types/master-crm';

const crm = useMasterCrm();

const from = ref(toDateInput(addDays(new Date(), -30)));
const to = ref(toDateInput(new Date()));
const bayId = ref('');
const kind = ref<'' | AppointmentKind>('');
const paymentStatus = ref<'' | PaymentStatus>('');
const search = ref('');

const items = ref<CrmAppointment[]>([]);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const isLoading = ref(false);
const error = ref<string | null>(null);

const bays = ref<CrmBay[]>([]);
const clients = ref<CrmClient[]>([]);
const serviceCatalog = ref<CrmServiceCatalogItem[]>([]);

function addDays(date: Date, days: number): Date {
    const d = new Date(date);
    d.setDate(d.getDate() + days);
    return d;
}

let page = 1;
let searchTimer: ReturnType<typeof setTimeout> | undefined;

async function load() {
    isLoading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get<AppointmentsPage>(
            '/master-api/crm/appointments',
            {
                params: {
                    from: from.value || undefined,
                    to: to.value || undefined,
                    bayId: bayId.value || undefined,
                    kind: kind.value || undefined,
                    paymentStatus: paymentStatus.value || undefined,
                    search: search.value || undefined,
                    page,
                },
            },
        );
        items.value = data.data;
        currentPage.value = data.currentPage;
        lastPage.value = data.lastPage;
        total.value = data.total;
    } catch {
        error.value = 'Не вдалося завантажити записи';
    } finally {
        isLoading.value = false;
    }
}

function applyFilters() {
    page = 1;
    load();
}

function goToPage(target: number) {
    if (target < 1 || target > lastPage.value) return;
    page = target;
    load();
}

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 300);
});

const kindColors: Record<AppointmentKind, string> = {
    work: 'busy',
    request: 'request',
    next: 'next',
};

function kindColor(k: AppointmentKind): string {
    return `var(--status-${kindColors[k]})`;
}

function kindSoftColor(k: AppointmentKind): string {
    return `var(--status-${kindColors[k]}-soft)`;
}

const kindLabels: Record<AppointmentKind, string> = {
    work: 'В роботі',
    request: 'Заявка',
    next: 'Далі',
};

function kindLabel(k: AppointmentKind): string {
    return kindLabels[k];
}

const paymentColors: Record<PaymentStatus, string> = {
    paid: 'free',
    partial: 'next',
    debt: 'busy',
    pending: 'request',
};

function paymentColor(status: PaymentStatus): string {
    return `var(--status-${paymentColors[status]})`;
}

function paymentSoftColor(status: PaymentStatus): string {
    return `var(--status-${paymentColors[status]}-soft)`;
}

const paymentLabels: Record<PaymentStatus, string> = {
    pending: 'Не оплачено',
    partial: 'Частково',
    paid: 'Оплачено',
    debt: 'Борг',
};

function paymentLabel(status: PaymentStatus): string {
    return paymentLabels[status];
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('uk-UA', {
        day: '2-digit',
        month: 'short',
    });
}

function formatTime(iso: string): string {
    const d = new Date(iso);
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

const showModal = ref(false);
const editingAppointment = ref<CrmAppointment | null>(null);

function openEdit(appointment: CrmAppointment) {
    editingAppointment.value = appointment;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingAppointment.value = null;
}

async function handleSave(payload: { id: string; changes: CrmChange[] }) {
    const businessDay = editingAppointment.value
        ? editingAppointment.value.startsAt.slice(0, 10)
        : toDateInput(new Date());
    const ok = await crm.sync(businessDay, payload.changes);
    if (ok) {
        closeModal();
        load();
    }
}

async function handleCancelAppointment() {
    if (!editingAppointment.value) return;
    const businessDay = editingAppointment.value.startsAt.slice(0, 10);
    const changes: CrmChange[] = [
        {
            type: 'cancel_appointment',
            payload: { id: editingAppointment.value.id },
        },
    ];
    const ok = await crm.sync(businessDay, changes);
    if (ok) {
        closeModal();
        load();
    }
}

onMounted(async () => {
    await crm.loadSnapshot(toDateInput(new Date()));
    if (crm.snapshot.value) {
        bays.value = crm.snapshot.value.bays;
        clients.value = crm.snapshot.value.clients;
        serviceCatalog.value = crm.snapshot.value.serviceCatalog;
    }
    await load();
});
</script>
