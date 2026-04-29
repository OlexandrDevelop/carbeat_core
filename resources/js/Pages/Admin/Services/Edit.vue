<template>
    <div class="min-h-screen bg-gray-50">
        <header
            class="sticky top-0 z-10 border-b border-gray-200 bg-white/70 backdrop-blur"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4"
            >
                <h1 class="text-2xl font-semibold text-gray-900">
                    Edit service
                </h1>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.services.index')"
                        class="rounded-lg border px-3 py-1.5 text-sm"
                        >Back</Link
                    >
                    <button
                        @click="saveTranslations"
                        class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-700"
                    >
                        Save
                    </button>
                    <button
                        @click="saveProviders"
                        class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm text-white hover:bg-emerald-700"
                    >
                        Save providers
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl space-y-6 px-6 py-6">
            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div
                    class="mb-4 text-xs font-medium uppercase tracking-wider text-gray-400"
                >
                    Canonical key:
                    <span class="font-mono text-gray-600">{{ canonical }}</span>
                </div>

                <div class="space-y-4">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Назва (uk) <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="translations.uk"
                            type="text"
                            placeholder="Назва послуги українською"
                            class="w-full rounded-xl bg-gray-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Name (en)
                        </label>
                        <input
                            v-model="translations.en"
                            type="text"
                            placeholder="Service name in English"
                            class="w-full rounded-xl bg-gray-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="mb-4 flex items-center gap-3">
                    <label class="text-sm font-medium text-gray-700"
                        >Add provider</label
                    >
                    <select
                        v-model.number="selectedMasterId"
                        class="min-w-[240px] rounded-xl bg-gray-100 px-3 py-2 text-sm"
                    >
                        <option :value="0">Select a master</option>
                        <option
                            v-for="m in allMasters"
                            :key="m.id"
                            :value="m.id"
                        >
                            #{{ m.id }} — {{ m.name }}
                        </option>
                    </select>
                    <button
                        :disabled="!selectedMasterId"
                        @click="addProvider"
                        class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        Add
                    </button>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                ID
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                            >
                                Primary
                            </th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="p in providers"
                            :key="p.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-6 py-3 text-sm text-gray-600">
                                {{ p.id }}
                            </td>
                            <td
                                class="px-6 py-3 text-sm font-medium text-gray-900"
                            >
                                <Link
                                    :href="
                                        route('admin.masters.edit', {
                                            id: p.id,
                                        })
                                    "
                                    class="text-blue-600 hover:text-blue-800"
                                    >{{ p.name }}</Link
                                >
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs"
                                    :class="
                                        p.is_main
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-gray-100 text-gray-600'
                                    "
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="
                                            p.is_main
                                                ? 'bg-emerald-600'
                                                : 'bg-gray-500'
                                        "
                                    />
                                    {{ p.is_main ? 'Primary' : '—' }}
                                </span>
                            </td>
                            <td class="space-x-3 px-6 py-3 text-right">
                                <Link
                                    :href="
                                        route('admin.masters.edit', {
                                            id: p.id,
                                        })
                                    "
                                    class="text-blue-600 hover:text-blue-800"
                                    >Open</Link
                                >
                                <button
                                    @click="removeProvider(p.id)"
                                    :disabled="p.is_main"
                                    class="text-red-600 hover:text-red-800 disabled:opacity-40"
                                >
                                    Remove
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const props = defineProps<{ serviceId: number }>();

const canonical = ref('');
const translations = ref<Record<string, string>>({ uk: '', en: '' });
const providers = ref<Array<{ id: number; name: string; is_main: boolean }>>(
    [],
);
const allMasters = ref<Array<{ id: number; name: string }>>([]);
const selectedMasterId = ref<number>(0);

async function load() {
    const { data } = await axios.get(
        `/admin-api/admin-services/${props.serviceId}`,
    );
    canonical.value = data.canonical ?? '';
    translations.value = { uk: '', en: '', ...(data.translations ?? {}) };
    providers.value = data.providers;
    allMasters.value = data.all_masters;
}

async function saveTranslations() {
    await axios.put(`/admin-api/admin-services/${props.serviceId}`, {
        translations: translations.value,
    });
}

async function saveProviders() {
    const ids = providers.value.map((p) => p.id);
    await axios.put(`/admin-api/admin-services/${props.serviceId}/providers`, {
        master_ids: ids,
    });
    await load();
}

function addProvider() {
    if (!selectedMasterId.value) return;
    if (!providers.value.some((p) => p.id === selectedMasterId.value)) {
        const m = allMasters.value.find((x) => x.id === selectedMasterId.value);
        if (m) providers.value.push({ id: m.id, name: m.name, is_main: false });
    }
    selectedMasterId.value = 0;
}

function removeProvider(id: number) {
    const p = providers.value.find((x) => x.id === id);
    if (p?.is_main) return;
    providers.value = providers.value.filter((x) => x.id !== id);
}

onMounted(load);
</script>
