<script setup lang="ts">
import GuestMapPage from '../../Carbeat/Public/GuestMap.vue';

defineProps<{ apiBase: string }>();
</script>

<template>
    <GuestMapPage :api-base="apiBase" flavor="floxcity" />
</template>
