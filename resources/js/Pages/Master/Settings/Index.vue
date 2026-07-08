<template>
    <div>
        <h1 class="mb-4 text-lg font-extrabold text-slate-900">
            Налаштування гаража
        </h1>

        <div v-if="isLoading" class="py-16 text-center text-slate-500">
            Завантаження…
        </div>

        <GlassPanel v-else-if="settings" class="max-w-lg space-y-3">
            <p
                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
                Редагування недоступне у веб-кабінеті — змінити ці дані можна
                через мобільний застосунок або адміністратора.
            </p>
            <dl class="divide-y divide-white/40">
                <div class="flex justify-between py-2">
                    <dt class="text-sm text-slate-500">Назва гаража</dt>
                    <dd class="text-sm font-semibold text-slate-900">
                        {{ settings.garageName || '—' }}
                    </dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-sm text-slate-500">Телефон</dt>
                    <dd class="font-mono text-sm font-semibold text-slate-900">
                        {{ settings.garagePhone || '—' }}
                    </dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-sm text-slate-500">Адреса</dt>
                    <dd class="text-sm font-semibold text-slate-900">
                        {{ settings.address || '—' }}
                    </dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-sm text-slate-500">Кількість боксів</dt>
                    <dd class="font-mono text-sm font-semibold text-slate-900">
                        {{ settings.teamSize }}
                    </dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-sm text-slate-500">Графік роботи</dt>
                    <dd class="font-mono text-sm font-semibold text-slate-900">
                        {{ settings.workingHours || '—' }}
                    </dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-sm text-slate-500">План</dt>
                    <dd class="text-sm font-semibold text-slate-900">
                        {{ settings.subscriptionPlan }}
                    </dd>
                </div>
            </dl>
        </GlassPanel>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import GlassPanel from '../../../components/MasterCrm/GlassPanel.vue';
import { useMasterCrm } from '../../../composables/useMasterCrm';

const crm = useMasterCrm();
const settings = computed(() => crm.snapshot.value?.garageSettings ?? null);
const isLoading = crm.isLoading;

onMounted(() => crm.loadSnapshot(new Date().toISOString().slice(0, 10)));
</script>
