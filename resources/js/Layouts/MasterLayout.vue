<template>
    <div :class="['master-shell min-h-screen', portalThemeClass]">
        <div
            class="glass-surface flex items-center gap-1 overflow-x-auto p-3 lg:hidden"
        >
            <Link
                v-for="item in navItems"
                :key="item.href"
                :href="item.href"
                class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold"
                :class="
                    route().current(item.pattern)
                        ? 'bg-white/60 text-slate-900'
                        : 'text-slate-600'
                "
            >
                {{ item.label }}
            </Link>
        </div>

        <div class="mx-auto flex min-h-screen max-w-[1400px]">
            <aside class="hidden w-64 shrink-0 flex-col gap-6 p-5 lg:flex">
                <GlassPanel class="flex flex-1 flex-col gap-6">
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            {{ brandName }}
                        </p>
                        <h1 class="text-lg font-extrabold text-slate-900">
                            Кабінет майстра
                        </h1>
                    </div>

                    <nav class="flex flex-1 flex-col gap-1">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            class="rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                            :class="
                                route().current(item.pattern)
                                    ? 'glass-surface text-slate-900'
                                    : 'text-slate-600 hover:bg-white/40'
                            "
                        >
                            {{ item.label }}
                        </Link>
                    </nav>

                    <Link
                        :href="route('master-logout')"
                        method="post"
                        as="button"
                        class="rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-500 transition hover:bg-white/40"
                    >
                        Вийти
                    </Link>
                </GlassPanel>
            </aside>

            <main class="flex-1 p-4 lg:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import GlassPanel from '../components/MasterCrm/GlassPanel.vue';
import { useBrand } from '../composables/useBrand';

const { brandName, portalThemeClass } = useBrand();

const navItems = computed(() => [
    {
        label: 'Розклад',
        href: route('master.schedule.index'),
        pattern: 'master.schedule.*',
    },
    {
        label: 'Каталог послуг',
        href: route('master.catalog.index'),
        pattern: 'master.catalog.*',
    },
    {
        label: 'Клієнти',
        href: route('master.clients.index'),
        pattern: 'master.clients.*',
    },
    {
        label: 'Фінанси',
        href: route('master.finance.index'),
        pattern: 'master.finance.*',
    },
    {
        label: 'Налаштування',
        href: route('master.settings.index'),
        pattern: 'master.settings.*',
    },
]);
</script>

<style scoped>
.master-shell {
    background: radial-gradient(
            circle at top left,
            rgba(var(--brand-primary-rgb), 0.12),
            transparent 55%
        ),
        #f4f6fa;
}
</style>
