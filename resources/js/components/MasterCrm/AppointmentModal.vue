<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
        @click.self="$emit('close')"
    >
        <GlassPanel
            class="max-h-[90vh] w-full max-w-lg overflow-y-auto"
            padding="lg"
        >
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-extrabold text-slate-900">
                    {{ isEditing ? 'Редагувати запис' : 'Новий запис' }}
                </h2>
                <button
                    type="button"
                    class="text-slate-400 hover:text-slate-700"
                    @click="$emit('close')"
                >
                    ✕
                </button>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-500"
                            >Бокс</label
                        >
                        <select
                            v-model="form.bayId"
                            class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                        >
                            <option
                                v-for="bay in bays"
                                :key="bay.id"
                                :value="bay.id"
                            >
                                {{ bay.title }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-500"
                            >Тип</label
                        >
                        <select
                            v-model="form.kind"
                            class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                        >
                            <option value="work">В роботі</option>
                            <option value="next">Далі</option>
                            <option value="request">Заявка</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-500"
                            >Початок</label
                        >
                        <input
                            v-model="startsAtLocal"
                            type="datetime-local"
                            class="glass-surface w-full rounded-xl px-3 py-2 font-mono text-sm"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-500"
                            >Кінець</label
                        >
                        <input
                            v-model="endsAtLocal"
                            type="datetime-local"
                            class="glass-surface w-full rounded-xl px-3 py-2 font-mono text-sm"
                        />
                    </div>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-500"
                        >Клієнт</label
                    >
                    <input
                        v-model="form.customerName"
                        type="text"
                        placeholder="Ім'я клієнта"
                        class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                        list="crm-client-names"
                        @change="onClientNameChange"
                    />
                    <datalist id="crm-client-names">
                        <option
                            v-for="client in clients"
                            :key="client.id"
                            :value="client.name"
                        />
                    </datalist>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-500"
                            >Телефон</label
                        >
                        <input
                            v-model="form.customerPhone"
                            type="tel"
                            class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-500"
                            >Номерний знак</label
                        >
                        <input
                            v-model="form.plateNumber"
                            type="text"
                            class="glass-surface w-full rounded-xl px-3 py-2 font-mono text-sm uppercase"
                        />
                    </div>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-500"
                        >Авто</label
                    >
                    <input
                        v-model="form.carModel"
                        type="text"
                        placeholder="Модель авто"
                        class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                    />
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-500"
                        >Послуга</label
                    >
                    <select
                        v-model="form.serviceCatalogId"
                        class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                        @change="onServiceChange"
                    >
                        <option :value="null">— оберіть —</option>
                        <option
                            v-for="service in serviceCatalog"
                            :key="service.id"
                            :value="service.id"
                        >
                            {{ service.nameUk }} · {{ service.priceUah }} ₴
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-500"
                        >Назва послуги</label
                    >
                    <input
                        v-model="form.serviceName"
                        type="text"
                        class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                    />
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-500"
                            >Сума, ₴</label
                        >
                        <input
                            v-model.number="form.priceUah"
                            type="number"
                            min="0"
                            class="glass-surface w-full rounded-xl px-3 py-2 font-mono text-sm"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-500"
                            >Оплачено, ₴</label
                        >
                        <input
                            v-model.number="form.paidAmountUah"
                            type="number"
                            min="0"
                            class="glass-surface w-full rounded-xl px-3 py-2 font-mono text-sm"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-semibold text-slate-500"
                            >Спосіб оплати</label
                        >
                        <select
                            v-model="form.paymentMethod"
                            class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                        >
                            <option value="none">—</option>
                            <option value="cash">Готівка</option>
                            <option value="card">Картка</option>
                            <option value="qr">QR</option>
                            <option value="transfer">Переказ</option>
                            <option value="mixed">Змішана</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-500"
                        >Нотатки</label
                    >
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                    ></textarea>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-between gap-2">
                <button
                    v-if="isEditing"
                    type="button"
                    class="rounded-xl px-4 py-2.5 text-sm font-semibold"
                    style="color: var(--status-busy)"
                    @click="$emit('cancel-appointment')"
                >
                    Скасувати запис
                </button>
                <div class="ml-auto flex gap-2">
                    <button
                        type="button"
                        class="glass-surface rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600"
                        @click="$emit('close')"
                    >
                        Закрити
                    </button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                        :style="{ backgroundColor: 'var(--brand-primary)' }"
                        @click="submit"
                    >
                        Зберегти
                    </button>
                </div>
            </div>
        </GlassPanel>
    </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import type {
    CrmAppointment,
    CrmBay,
    CrmChange,
    CrmClient,
    CrmServiceCatalogItem,
} from '../../types/master-crm';
import GlassPanel from './GlassPanel.vue';

const props = defineProps<{
    bays: CrmBay[];
    clients: CrmClient[];
    serviceCatalog: CrmServiceCatalogItem[];
    appointment?: CrmAppointment | null;
    defaultBayId?: string;
    defaultStartsAt?: string;
}>();

const emit = defineEmits<{
    close: [];
    save: [{ id: string; changes: CrmChange[] }];
    'cancel-appointment': [];
}>();

const isEditing = computed(() => !!props.appointment);

function toLocalInput(iso: string): string {
    const date = new Date(iso);
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function defaultStart(): string {
    if (props.defaultStartsAt) return props.defaultStartsAt;
    const now = new Date();
    now.setMinutes(0, 0, 0);
    return now.toISOString();
}

function defaultEnd(startIso: string): string {
    const date = new Date(startIso);
    date.setHours(date.getHours() + 1);
    return date.toISOString();
}

const appointmentId = props.appointment?.id ?? crypto.randomUUID();

const form = reactive({
    bayId:
        props.appointment?.bayId ??
        props.defaultBayId ??
        props.bays[0]?.id ??
        '',
    kind: props.appointment?.kind ?? 'work',
    clientId: props.appointment?.clientId ?? (null as string | null),
    vehicleId: props.appointment?.vehicleId ?? (null as string | null),
    serviceCatalogId:
        props.appointment?.serviceCatalogId ?? (null as string | null),
    customerName: props.appointment?.customerName ?? '',
    customerPhone: props.appointment?.customerPhone ?? '',
    carModel: props.appointment?.carModel ?? '',
    plateNumber: props.appointment?.plateNumber ?? '',
    serviceName: props.appointment?.serviceName ?? '',
    priceUah: props.appointment?.priceUah ?? 0,
    paidAmountUah: props.appointment?.paidAmountUah ?? 0,
    paymentMethod: props.appointment?.paymentMethod ?? 'none',
    notes: props.appointment?.notes ?? '',
});

const startsAtLocal = ref(
    toLocalInput(props.appointment?.startsAt ?? defaultStart()),
);
const endsAtLocal = ref(
    toLocalInput(
        props.appointment?.endsAt ??
            defaultEnd(props.appointment?.startsAt ?? defaultStart()),
    ),
);

function onServiceChange() {
    const service = props.serviceCatalog.find(
        (s) => s.id === form.serviceCatalogId,
    );
    if (service) {
        form.serviceName = service.nameUk;
        form.priceUah = service.priceUah;
    }
}

function onClientNameChange() {
    const match = props.clients.find((c) => c.name === form.customerName);
    if (match) {
        form.clientId = match.id;
        form.customerPhone = match.phone || form.customerPhone;
    } else {
        form.clientId = null;
    }
}

function submit() {
    const changes: CrmChange[] = [];

    // Inline "create new client" — if the typed name doesn't match an
    // existing client, create one so the appointment can reference it.
    let clientId = form.clientId;
    if (!clientId && form.customerName.trim() !== '') {
        clientId = crypto.randomUUID();
        changes.push({
            type: 'save_client',
            payload: {
                id: clientId,
                name: form.customerName,
                phone: form.customerPhone,
            },
        });
    }

    const schedulePayload = {
        id: appointmentId,
        bayId: form.bayId,
        clientId,
        vehicleId: form.vehicleId,
        serviceCatalogId: form.serviceCatalogId,
        kind: form.kind,
        startsAt: new Date(startsAtLocal.value).toISOString(),
        endsAt: new Date(endsAtLocal.value).toISOString(),
        customerName: form.customerName,
        customerPhone: form.customerPhone,
        carModel: form.carModel,
        plateNumber: form.plateNumber,
        serviceName: form.serviceName,
        priceUah: form.priceUah,
        notes: form.notes,
        hasPhotoRequest: props.appointment?.hasPhotoRequest ?? false,
    };

    if (isEditing.value) {
        // Schedule/customer/service fields and payment fields are two
        // separate change types on the backend (MasterCrmService::applyChanges
        // — `update_appointment` vs `update_appointment_payment`), mirroring
        // how the mobile app keeps "reschedule" and "record payment" distinct.
        // priceUah is sent in both: `update_appointment` persists it onto the
        // booking's total_amount, `update_appointment_payment` needs it to
        // (re)compute the paid/pending/partial status against the new total.
        changes.push({ type: 'update_appointment', payload: schedulePayload });
        changes.push({
            type: 'update_appointment_payment',
            payload: {
                id: appointmentId,
                priceUah: form.priceUah,
                paidAmountUah: form.paidAmountUah,
                paymentMethod: form.paymentMethod,
            },
        });
    } else {
        changes.push({
            type: 'create_appointment',
            payload: {
                ...schedulePayload,
                paidAmountUah: form.paidAmountUah,
                paymentStatus: derivePaymentStatus(),
                paymentMethod: form.paymentMethod,
            },
        });
    }

    emit('save', { id: appointmentId, changes });
}

function derivePaymentStatus(): string {
    const total = Number(form.priceUah) || 0;
    const paid = Math.max(0, Math.min(Number(form.paidAmountUah) || 0, total));
    if (paid <= 0) return 'pending';
    if (paid >= total) return 'paid';
    return 'partial';
}
</script>
