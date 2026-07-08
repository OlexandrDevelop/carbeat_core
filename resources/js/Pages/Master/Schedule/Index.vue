<template>
    <div>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <GlassPanel
                variant="surface"
                rounded="xl"
                padding="sm"
                class="flex items-center gap-2"
            >
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-bold"
                    @click="shiftDay(-1)"
                >
                    ‹
                </button>
                <span
                    class="min-w-[140px] text-center font-mono text-sm font-bold text-slate-800"
                >
                    {{ formattedDate }}
                </span>
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-bold"
                    @click="shiftDay(1)"
                >
                    ›
                </button>
                <button
                    type="button"
                    class="ml-2 text-xs font-semibold text-slate-500 hover:text-slate-800"
                    @click="goToday"
                >
                    Сьогодні
                </button>
            </GlassPanel>

            <button
                type="button"
                class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                :style="{ backgroundColor: 'var(--brand-primary)' }"
                @click="openCreateModal()"
            >
                + Новий запис
            </button>
        </div>

        <p
            v-if="crm.error.value"
            class="mb-3 text-sm font-semibold"
            style="color: var(--status-busy)"
        >
            {{ crm.error.value }}
        </p>

        <div
            v-if="crm.isLoading.value && !snapshot"
            class="py-16 text-center text-slate-500"
        >
            Завантаження…
        </div>

        <div
            v-else-if="snapshot"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <GlassPanel
                v-for="bay in visibleBays"
                :key="bay.id"
                class="flex flex-col gap-3"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">
                            {{ bay.title }}
                        </h3>
                        <p class="text-xs text-slate-500">
                            {{ bay.technicianName || 'Без техніка' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="glass-surface rounded-lg px-2 py-1 text-xs font-semibold text-slate-600"
                        @click="openCreateModal(bay.id)"
                    >
                        + запис
                    </button>
                </div>

                <div
                    v-if="bay.appointments.length === 0"
                    class="rounded-xl border border-dashed border-slate-300 p-4 text-center text-xs text-slate-400"
                >
                    Немає записів
                </div>
                <AppointmentCard
                    v-for="appointment in bay.appointments"
                    :key="appointment.id"
                    :appointment="appointment"
                    @click="openEditModal(appointment)"
                />
            </GlassPanel>
        </div>

        <AppointmentModal
            v-if="showModal"
            :bays="snapshot?.bays ?? []"
            :clients="snapshot?.clients ?? []"
            :service-catalog="snapshot?.serviceCatalog ?? []"
            :appointment="editingAppointment"
            :default-bay-id="defaultBayId"
            @close="closeModal"
            @save="handleSave"
            @cancel-appointment="handleCancelAppointment"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import AppointmentCard from '../../../components/MasterCrm/AppointmentCard.vue';
import AppointmentModal from '../../../components/MasterCrm/AppointmentModal.vue';
import GlassPanel from '../../../components/MasterCrm/GlassPanel.vue';
import { useMasterCrm } from '../../../composables/useMasterCrm';
import type { CrmAppointment, CrmChange } from '../../../types/master-crm';

const crm = useMasterCrm();
const snapshot = computed(() => crm.snapshot.value);

function toDateInput(date: Date): string {
    return date.toISOString().slice(0, 10);
}

const currentDate = ref(toDateInput(new Date()));

const formattedDate = computed(() => {
    const date = new Date(`${currentDate.value}T00:00:00`);
    return date.toLocaleDateString('uk-UA', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
    });
});

const visibleBays = computed(() =>
    (snapshot.value?.bays ?? []).filter((bay) => !bay.isArchived),
);

function shiftDay(delta: number) {
    const date = new Date(`${currentDate.value}T00:00:00`);
    date.setDate(date.getDate() + delta);
    currentDate.value = toDateInput(date);
}

function goToday() {
    currentDate.value = toDateInput(new Date());
}

watch(currentDate, (date) => crm.loadSnapshot(date), { immediate: false });

onMounted(() => crm.loadSnapshot(currentDate.value));

const showModal = ref(false);
const editingAppointment = ref<CrmAppointment | null>(null);
const defaultBayId = ref<string | undefined>(undefined);

function openCreateModal(bayId?: string) {
    editingAppointment.value = null;
    defaultBayId.value = bayId;
    showModal.value = true;
}

function openEditModal(appointment: CrmAppointment) {
    editingAppointment.value = appointment;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingAppointment.value = null;
}

async function handleSave(payload: { id: string; changes: CrmChange[] }) {
    const ok = await crm.sync(currentDate.value, payload.changes);
    if (ok) {
        closeModal();
    }
}

async function handleCancelAppointment() {
    if (!editingAppointment.value) return;
    const changes: CrmChange[] = [
        {
            type: 'cancel_appointment',
            payload: { id: editingAppointment.value.id },
        },
    ];
    const ok = await crm.sync(currentDate.value, changes);
    if (ok) {
        closeModal();
    }
}
</script>
