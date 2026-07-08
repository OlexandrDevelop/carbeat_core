<template>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[320px_1fr]">
        <GlassPanel
            padding="none"
            class="flex max-h-[80vh] flex-col overflow-hidden"
        >
            <div class="p-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Пошук клієнта…"
                    class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                />
                <button
                    type="button"
                    class="mt-2 w-full rounded-xl px-3 py-2 text-sm font-semibold text-white"
                    :style="{ backgroundColor: 'var(--brand-primary)' }"
                    @click="startCreateClient"
                >
                    + Новий клієнт
                </button>
            </div>
            <div class="flex-1 overflow-y-auto">
                <button
                    v-for="client in filteredClients"
                    :key="client.id"
                    type="button"
                    class="block w-full border-t border-white/40 px-4 py-3 text-left text-sm transition"
                    :class="
                        selectedClientId === client.id
                            ? 'bg-white/50'
                            : 'hover:bg-white/30'
                    "
                    @click="selectClient(client.id)"
                >
                    <p class="font-semibold text-slate-800">
                        {{ client.name }}
                    </p>
                    <p class="font-mono text-xs text-slate-500">
                        {{ client.phone || '—' }}
                    </p>
                </button>
                <p
                    v-if="filteredClients.length === 0"
                    class="p-4 text-center text-xs text-slate-400"
                >
                    Немає клієнтів
                </p>
            </div>
        </GlassPanel>

        <GlassPanel v-if="editingClient" class="space-y-3">
            <h2 class="text-sm font-extrabold text-slate-900">
                {{ selectedClientId ? 'Редагувати клієнта' : 'Новий клієнт' }}
            </h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-500"
                        >Ім'я</label
                    >
                    <input
                        v-model="clientForm.name"
                        type="text"
                        class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-500"
                        >Телефон</label
                    >
                    <input
                        v-model="clientForm.phone"
                        type="tel"
                        class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                    />
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    class="glass-surface rounded-xl px-4 py-2 text-sm font-semibold text-slate-600"
                    @click="editingClient = false"
                >
                    Скасувати
                </button>
                <button
                    type="button"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-white"
                    :style="{ backgroundColor: 'var(--brand-primary)' }"
                    @click="saveClient"
                >
                    Зберегти
                </button>
            </div>
        </GlassPanel>

        <GlassPanel v-else-if="selectedClient" class="space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">
                        {{ selectedClient.name }}
                    </h2>
                    <p class="font-mono text-sm text-slate-500">
                        {{ selectedClient.phone || '—' }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="text-xs font-semibold text-slate-500 hover:text-slate-900"
                        @click="startEditClient"
                    >
                        Редагувати
                    </button>
                    <button
                        type="button"
                        class="text-xs font-semibold"
                        style="color: var(--status-busy)"
                        @click="removeClient"
                    >
                        Видалити
                    </button>
                </div>
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-800">Автомобілі</h3>
                    <button
                        type="button"
                        class="text-xs font-semibold text-slate-500 hover:text-slate-900"
                        @click="startCreateVehicle"
                    >
                        + Авто
                    </button>
                </div>

                <GlassPanel
                    v-if="editingVehicle"
                    variant="surface"
                    rounded="xl"
                    class="mb-3"
                >
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-500"
                                >Модель</label
                            >
                            <input
                                v-model="vehicleForm.modelName"
                                type="text"
                                class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-500"
                                >Номерний знак</label
                            >
                            <input
                                v-model="vehicleForm.plateNumber"
                                type="text"
                                class="glass-surface w-full rounded-xl px-3 py-2 font-mono text-sm uppercase"
                            />
                        </div>
                    </div>
                    <div class="mt-3 flex justify-end gap-2">
                        <button
                            type="button"
                            class="text-xs font-semibold text-slate-500"
                            @click="editingVehicle = false"
                        >
                            Скасувати
                        </button>
                        <button
                            type="button"
                            class="text-xs font-semibold"
                            :style="{ color: 'var(--brand-primary)' }"
                            @click="saveVehicle"
                        >
                            Зберегти
                        </button>
                    </div>
                </GlassPanel>

                <div class="space-y-2">
                    <div
                        v-for="vehicle in clientVehicles"
                        :key="vehicle.id"
                        class="glass-surface flex items-center justify-between rounded-xl px-3 py-2"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                {{ vehicle.modelName }}
                            </p>
                            <p
                                class="font-mono text-xs uppercase text-slate-500"
                            >
                                {{ vehicle.plateNumber }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="text-xs font-semibold"
                            style="color: var(--status-busy)"
                            @click="removeVehicle(vehicle.id)"
                        >
                            Видалити
                        </button>
                    </div>
                    <p
                        v-if="clientVehicles.length === 0"
                        class="text-xs text-slate-400"
                    >
                        Немає автомобілів
                    </p>
                </div>
            </div>
        </GlassPanel>

        <GlassPanel
            v-else
            class="flex items-center justify-center text-sm text-slate-400"
        >
            Оберіть клієнта зі списку
        </GlassPanel>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import GlassPanel from '../../../components/MasterCrm/GlassPanel.vue';
import { useMasterCrm } from '../../../composables/useMasterCrm';

const crm = useMasterCrm();
const snapshot = computed(() => crm.snapshot.value);
const today = new Date().toISOString().slice(0, 10);

onMounted(() => crm.loadSnapshot(today));

const search = ref('');
const selectedClientId = ref<string | null>(null);
const editingClient = ref(false);
const clientForm = reactive({ id: '', name: '', phone: '' });

const filteredClients = computed(() => {
    const clients = snapshot.value?.clients ?? [];
    const query = search.value.trim().toLowerCase();
    if (!query) return clients;
    return clients.filter(
        (c) =>
            c.name.toLowerCase().includes(query) ||
            (c.phone ?? '').toLowerCase().includes(query),
    );
});

const selectedClient = computed(
    () =>
        snapshot.value?.clients.find((c) => c.id === selectedClientId.value) ??
        null,
);

const clientVehicles = computed(
    () =>
        snapshot.value?.vehicles.filter(
            (v) => v.clientId === selectedClientId.value,
        ) ?? [],
);

function selectClient(id: string) {
    selectedClientId.value = id;
    editingClient.value = false;
    editingVehicle.value = false;
}

function startCreateClient() {
    clientForm.id = crypto.randomUUID();
    clientForm.name = '';
    clientForm.phone = '';
    selectedClientId.value = null;
    editingClient.value = true;
}

function startEditClient() {
    if (!selectedClient.value) return;
    clientForm.id = selectedClient.value.id;
    clientForm.name = selectedClient.value.name;
    clientForm.phone = selectedClient.value.phone;
    editingClient.value = true;
}

async function saveClient() {
    const ok = await crm.sync(today, [
        {
            type: 'save_client',
            payload: {
                id: clientForm.id,
                name: clientForm.name,
                phone: clientForm.phone,
            },
        },
    ]);
    if (ok) {
        selectedClientId.value = clientForm.id;
        editingClient.value = false;
    }
}

async function removeClient() {
    if (
        !selectedClientId.value ||
        !confirm('Видалити клієнта разом з його автомобілями?')
    )
        return;
    const ok = await crm.sync(today, [
        {
            type: 'delete_client',
            payload: { clientId: selectedClientId.value },
        },
    ]);
    if (ok) {
        selectedClientId.value = null;
    }
}

const editingVehicle = ref(false);
const vehicleForm = reactive({ id: '', modelName: '', plateNumber: '' });

function startCreateVehicle() {
    vehicleForm.id = crypto.randomUUID();
    vehicleForm.modelName = '';
    vehicleForm.plateNumber = '';
    editingVehicle.value = true;
}

async function saveVehicle() {
    if (!selectedClientId.value) return;
    const ok = await crm.sync(today, [
        {
            type: 'save_vehicle',
            payload: {
                id: vehicleForm.id,
                clientId: selectedClientId.value,
                modelName: vehicleForm.modelName,
                plateNumber: vehicleForm.plateNumber,
            },
        },
    ]);
    if (ok) {
        editingVehicle.value = false;
    }
}

async function removeVehicle(id: string) {
    if (!confirm('Видалити це авто?')) return;
    await crm.sync(today, [
        { type: 'delete_vehicle', payload: { vehicleId: id } },
    ]);
}
</script>
