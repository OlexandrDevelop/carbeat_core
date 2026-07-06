<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';

const props = defineProps<{
    src: string;
    alt: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') emit('close');
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div
        class="lightbox-backdrop pointer-events-auto absolute inset-0 z-[999] flex items-center justify-center p-3 md:p-8"
        @click.self="emit('close')"
    >
        <button
            type="button"
            class="lightbox-close absolute right-4 top-4 rounded-full px-4 py-2 text-sm font-semibold text-white"
            @click="emit('close')"
        >
            ✕ Close
        </button>
        <img
            :src="src"
            :alt="alt"
            class="lightbox-image max-h-full max-w-full rounded-2xl object-contain"
        />
    </div>
</template>

<style scoped>
.lightbox-backdrop {
    background: rgba(0, 0, 0, 0.82);
}

.lightbox-close {
    background: rgba(11, 15, 25, 0.56);
    border: 1px solid rgba(255, 255, 255, 0.14);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    transition: opacity 0.12s ease;
}

.lightbox-close:hover {
    opacity: 0.78;
}

.lightbox-image {
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>
