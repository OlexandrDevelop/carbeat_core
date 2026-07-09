<template>
    <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <template v-if="name === 'schedule'">
            <rect x="3" y="4.5" width="18" height="16" rx="2" />
            <path d="M3 9.5h18M8 2.5v4M16 2.5v4" />
        </template>

        <template v-else-if="name === 'appointments'">
            <rect x="5" y="4" width="14" height="17" rx="2" />
            <rect x="9" y="2" width="6" height="3" rx="1" />
            <path d="M8 10h8M8 14h8M8 18h5" />
        </template>

        <template v-else-if="name === 'catalog'">
            <path d="M4 6h16M4 12h16M4 18h10" />
        </template>

        <template v-else-if="name === 'clients'">
            <circle cx="12" cy="8" r="3.5" />
            <path d="M4.5 20a7.5 7.5 0 0 1 15 0" />
        </template>

        <template v-else-if="name === 'finance'">
            <path d="M4 20V10M12 20V4M20 20v-7" />
        </template>

        <template v-else>
            <path d="M4 7h11M17 7h3M4 12h5M11 12h9M4 17h13M19 17h1" />
            <circle cx="14" cy="7" r="2" />
            <circle cx="8" cy="12" r="2" />
            <circle cx="16" cy="17" r="2" />
        </template>
    </svg>
</template>

<script setup lang="ts">
defineProps<{
    name:
        | 'schedule'
        | 'appointments'
        | 'catalog'
        | 'clients'
        | 'finance'
        | 'settings';
}>();
</script>
