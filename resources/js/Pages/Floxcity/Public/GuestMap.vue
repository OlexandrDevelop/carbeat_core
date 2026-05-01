<script setup lang="ts">
import GuestMapPage from '../../Carbeat/Public/GuestMap.vue';

defineProps<{
    apiBase: string;
    mapPath?: string;
    profilePathPrefix?: string;
    initialMapView?: { center: [number, number]; zoom: number } | null;
    initialServiceId?: number | null;
    initialSelectedMaster?: any;
    seo?: any;
    seoContent?: any;
}>();
</script>

<template>
    <GuestMapPage
        :api-base="apiBase"
        :map-path="mapPath"
        :profile-path-prefix="profilePathPrefix"
        :initial-map-view="initialMapView"
        :initial-service-id="initialServiceId"
        :initial-selected-master="initialSelectedMaster"
        :seo="seo"
        :seo-content="seoContent"
        flavor="floxcity"
    />
</template>
