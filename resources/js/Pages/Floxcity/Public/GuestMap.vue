<script setup lang="ts">
import GuestMapPage from '@/components/GuestMap/GuestMapPage.vue';
import type {
    MasterDetails,
    SeoContentPayload,
    SeoPayload,
} from '@/types/guest-map';

defineProps<{
    apiBase: string;
    mapPath?: string;
    profilePathPrefix?: string;
    initialMapView?: { center: [number, number]; zoom: number } | null;
    initialServiceId?: number | null;
    initialSelectedMaster?: MasterDetails | null;
    seo?: SeoPayload | null;
    seoContent?: SeoContentPayload | null;
}>();
</script>

<template>
    <GuestMapPage v-bind="$props" flavor="floxcity" />
</template>
