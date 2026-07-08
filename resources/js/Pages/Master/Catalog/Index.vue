<template>
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-lg font-extrabold text-slate-900">
                Каталог послуг
            </h1>
            <button
                type="button"
                class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white"
                :style="{ backgroundColor: 'var(--brand-primary)' }"
                @click="startCreate"
            >
                + Послуга
            </button>
        </div>

        <p
            v-if="crm.error.value"
            class="mb-3 text-sm font-semibold"
            style="color: var(--status-busy)"
        >
            {{ crm.error.value }}
        </p>

        <GlassPanel v-if="editing" class="mb-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-500"
                        >Назва (укр)</label
                    >
                    <input
                        v-model="form.nameUk"
                        type="text"
                        class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-500"
                        >Назва (eng)</label
                    >
                    <input
                        v-model="form.nameEn"
                        type="text"
                        class="glass-surface w-full rounded-xl px-3 py-2 text-sm"
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-500"
                        >Тривалість, хв</label
                    >
                    <input
                        v-model.number="form.durationMinutes"
                        type="number"
                        min="0"
                        class="glass-surface w-full rounded-xl px-3 py-2 font-mono text-sm"
                    />
                </div>
                <div>
                    <label
                        class="mb-1 block text-xs font-semibold text-slate-500"
                        >Ціна, ₴</label
                    >
                    <input
                        v-model.number="form.priceUah"
                        type="number"
                        min="0"
                        class="glass-surface w-full rounded-xl px-3 py-2 font-mono text-sm"
                    />
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button
                    type="button"
                    class="glass-surface rounded-xl px-4 py-2 text-sm font-semibold text-slate-600"
                    @click="cancelEdit"
                >
                    Скасувати
                </button>
                <button
                    type="button"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-white"
                    :style="{ backgroundColor: 'var(--brand-primary)' }"
                    @click="save"
                >
                    Зберегти
                </button>
            </div>
        </GlassPanel>

        <GlassPanel v-if="snapshot" padding="none" class="overflow-hidden">
            <table class="w-full text-sm">
                <thead class="glass-surface text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Назва</th>
                        <th class="px-4 py-2 text-left">Тривалість</th>
                        <th class="px-4 py-2 text-left">Ціна</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in snapshot.serviceCatalog"
                        :key="item.id"
                        class="border-t border-white/40"
                    >
                        <td class="px-4 py-2 font-semibold text-slate-800">
                            {{ item.nameUk }}
                        </td>
                        <td class="px-4 py-2 font-mono text-slate-600">
                            {{ item.durationMinutes }} хв
                        </td>
                        <td class="px-4 py-2 font-mono text-slate-600">
                            {{ item.priceUah }} ₴
                        </td>
                        <td class="space-x-2 px-4 py-2 text-right">
                            <button
                                type="button"
                                class="text-xs font-semibold text-slate-500 hover:text-slate-900"
                                @click="startEdit(item)"
                            >
                                Редагувати
                            </button>
                            <button
                                type="button"
                                class="text-xs font-semibold"
                                style="color: var(--status-busy)"
                                @click="remove(item.id)"
                            >
                                Видалити
                            </button>
                        </td>
                    </tr>
                    <tr v-if="snapshot.serviceCatalog.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-sm text-slate-400"
                        >
                            Немає послуг у каталозі
                        </td>
                    </tr>
                </tbody>
            </table>
        </GlassPanel>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import GlassPanel from '../../../components/MasterCrm/GlassPanel.vue';
import { useMasterCrm } from '../../../composables/useMasterCrm';
import type { CrmServiceCatalogItem } from '../../../types/master-crm';

const crm = useMasterCrm();
const snapshot = computed(() => crm.snapshot.value);
const today = new Date().toISOString().slice(0, 10);

onMounted(() => crm.loadSnapshot(today));

const editing = ref(false);
const editingId = ref<string | null>(null);
const form = reactive({
    nameUk: '',
    nameEn: '',
    durationMinutes: 60,
    priceUah: 0,
});

function startCreate() {
    editingId.value = crypto.randomUUID();
    form.nameUk = '';
    form.nameEn = '';
    form.durationMinutes = 60;
    form.priceUah = 0;
    editing.value = true;
}

function startEdit(item: CrmServiceCatalogItem) {
    editingId.value = item.id;
    form.nameUk = item.nameUk;
    form.nameEn = item.nameEn;
    form.durationMinutes = item.durationMinutes;
    form.priceUah = item.priceUah;
    editing.value = true;
}

function cancelEdit() {
    editing.value = false;
    editingId.value = null;
}

async function save() {
    if (!editingId.value) return;
    const displayOrder = snapshot.value?.serviceCatalog.length ?? 0;
    const ok = await crm.sync(today, [
        {
            type: 'save_service',
            payload: {
                id: editingId.value,
                nameUk: form.nameUk,
                nameEn: form.nameEn,
                durationMinutes: form.durationMinutes,
                priceUah: form.priceUah,
                displayOrder,
            },
        },
    ]);
    if (ok) {
        cancelEdit();
    }
}

async function remove(id: string) {
    if (!confirm('Видалити цю послугу?')) return;
    await crm.sync(today, [
        { type: 'delete_service', payload: { serviceId: id } },
    ]);
}
</script>
