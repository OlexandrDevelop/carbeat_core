<script setup lang="ts">
import GuidePage from '@/components/Guide/GuidePage.vue';
import type { SeoContentPayload, SeoPayload } from '@/types/guest-map';

defineProps<{
    mapPath?: string;
    seo: SeoPayload;
    content: SeoContentPayload;
}>();
</script>

<template>
    <GuidePage v-bind="$props" flavor="floxcity" />
</template>
