<script setup lang="ts">
import GuestMapPage from '../../Carbeat/Public/GuestMap.vue';

defineProps<{
    apiBase: string;
    mapPath?: string;
    initialSelectedMaster?: any;
    seo?: any;
}>();
</script>

<template>
    <GuestMapPage
        :api-base="apiBase"
        :map-path="mapPath"
        :initial-selected-master="initialSelectedMaster"
        :seo="seo"
        flavor="floxcity"
    />
</template>
