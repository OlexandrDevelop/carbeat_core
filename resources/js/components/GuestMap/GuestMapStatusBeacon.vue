<script setup lang="ts">
import type { StatusBeacon } from '@/composables/useStatusBeacon';

defineProps<{
    beacon: StatusBeacon;
    isFloxcity: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();
</script>

<template>
    <div class="status-beacon rounded-2xl p-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <div class="status-beacon-dot mt-1 h-2.5 w-2.5 rounded-full" />
                <div>
                    <div
                        class="text-xs font-semibold uppercase tracking-wide"
                        :class="
                            isFloxcity ? 'text-emerald-700' : 'text-sky-700'
                        "
                    >
                        Status update
                    </div>
                    <div class="mt-0.5 text-sm text-slate-800">
                        {{ beacon.masterName }} is available now.
                    </div>
                </div>
            </div>
            <button
                type="button"
                class="rounded-md px-2 py-1 text-xs font-semibold"
                :class="
                    isFloxcity
                        ? 'bg-emerald-50 text-slate-800 hover:bg-emerald-100'
                        : 'bg-sky-50 text-slate-800 hover:bg-sky-100'
                "
                @click="emit('close')"
            >
                ✕
            </button>
        </div>
    </div>
</template>

<style scoped>
.status-beacon {
    background: rgba(5, 18, 12, 0.54);
    border: 1px solid rgba(52, 211, 153, 0.34);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.22);
    backdrop-filter: blur(20px) saturate(150%);
    -webkit-backdrop-filter: blur(20px) saturate(150%);
}

.status-beacon-dot {
    background: #34d399;
    animation: status-beacon-ping 1.5s infinite ease-out;
}

@keyframes status-beacon-ping {
    0% {
        box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.6);
    }
    72% {
        box-shadow: 0 0 0 10px rgba(52, 211, 153, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(52, 211, 153, 0);
    }
}
</style>
