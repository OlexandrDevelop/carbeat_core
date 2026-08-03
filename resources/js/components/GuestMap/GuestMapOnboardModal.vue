<script setup lang="ts">
import type { Lang, UiTextKey } from '@/composables/useGuestLang';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type Service = { id: number; name: string };
type Preview = { name: string; photo: string; city: string | null } | null;
type Mode = 'register' | 'claim' | 'login';

const props = defineProps<{
    services: Service[];
    currentLang: Lang;
    t: (key: UiTextKey) => string;
    /** Best-effort fallback location (current map center) if geolocation is denied/unavailable. */
    fallbackLocation: () => { lat: number; lng: number } | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const step = ref<'phone' | 'verify'>('phone');
const mode = ref<Mode | null>(null);
const preview = ref<Preview>(null);

const phone = ref('');
const code = ref('');
const name = ref('');
const serviceId = ref<number | null>(null);

const loading = ref(false);
const error = ref('');
const resendCooldown = ref(0);

const geoCoords = ref<{ lat: number; lng: number } | null>(null);

const isRegister = computed(() => mode.value === 'register');

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') emit('close');
}

onMounted(() => {
    window.addEventListener('keydown', onKeydown);

    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                geoCoords.value = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
            },
            () => {
                // Denied or unavailable — silently fall back at submit time.
            },
            { timeout: 5000, maximumAge: 5 * 60_000 },
        );
    }
});

onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

function resolveLocation(): { lat: number; lng: number } | null {
    return geoCoords.value ?? props.fallbackLocation();
}

async function sendCode(): Promise<void> {
    if (!phone.value || loading.value) return;

    error.value = '';
    loading.value = true;
    try {
        const { data } = await axios.post('/master-onboard/send-code', {
            phone: phone.value,
        });
        mode.value = data.mode as Mode;
        preview.value = data.preview ?? null;
        step.value = 'verify';
        startResendCooldown();
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ?? props.t('onboardGenericError');
    } finally {
        loading.value = false;
    }
}

function startResendCooldown(): void {
    resendCooldown.value = 30;
    const timer = window.setInterval(() => {
        resendCooldown.value -= 1;
        if (resendCooldown.value <= 0) window.clearInterval(timer);
    }, 1000);
}

function resend(): void {
    if (resendCooldown.value > 0) return;
    sendCode();
}

async function verify(): Promise<void> {
    if (loading.value || code.value.length !== 4) return;

    error.value = '';
    loading.value = true;
    try {
        const payload: Record<string, unknown> = {
            phone: phone.value,
            code: code.value,
        };

        if (isRegister.value) {
            const location = resolveLocation();
            payload.name = name.value;
            payload.service_id = serviceId.value;
            payload.latitude = location?.lat ?? null;
            payload.longitude = location?.lng ?? null;
        }

        await axios.post('/master-onboard/verify', payload);
        router.visit('/master');
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ?? props.t('onboardGenericError');
    } finally {
        loading.value = false;
    }
}

const canVerify = computed(() => {
    if (code.value.length !== 4) return false;
    if (isRegister.value) return !!name.value && !!serviceId.value;
    return true;
});
</script>

<template>
    <div
        class="onboard-backdrop pointer-events-auto absolute inset-0 z-[600] flex items-center justify-center p-3 md:p-8"
        @click.self="emit('close')"
    >
        <div class="onboard-panel w-full max-w-sm rounded-2xl p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-extrabold">
                    {{
                        step === 'verify' && isRegister
                            ? t('onboardRegisterTitle')
                            : t('onboardTitle')
                    }}
                </h2>
                <button
                    type="button"
                    class="onboard-close rounded-full p-1 text-sm"
                    :aria-label="t('cancel')"
                    @click="emit('close')"
                >
                    ✕
                </button>
            </div>

            <div v-if="step === 'phone'" class="space-y-3">
                <label class="block text-sm font-medium">{{
                    t('onboardPhoneLabel')
                }}</label>
                <input
                    v-model="phone"
                    type="tel"
                    placeholder="+380XXXXXXXXX"
                    class="onboard-input w-full rounded-xl px-4 py-3 text-sm outline-none"
                    @keyup.enter="sendCode"
                />
                <button
                    type="button"
                    :disabled="loading || !phone"
                    class="onboard-primary-btn w-full rounded-xl px-4 py-3 text-sm font-semibold text-white disabled:opacity-50"
                    @click="sendCode"
                >
                    {{ loading ? t('sending') : t('onboardContinue') }}
                </button>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-if="!isRegister && preview"
                    class="flex items-center gap-3 rounded-xl bg-black/5 p-2.5"
                >
                    <img
                        :src="preview.photo"
                        :alt="preview.name"
                        class="h-12 w-12 rounded-lg object-cover"
                    />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">
                            {{ preview.name }}
                        </p>
                        <p v-if="preview.city" class="text-xs opacity-70">
                            {{ preview.city }}
                        </p>
                        <p class="text-xs opacity-70">
                            {{ t('onboardFoundProfile') }}
                        </p>
                    </div>
                </div>

                <template v-if="isRegister">
                    <label class="block text-sm font-medium">{{
                        t('onboardNameLabel')
                    }}</label>
                    <input
                        v-model="name"
                        type="text"
                        class="onboard-input w-full rounded-xl px-4 py-3 text-sm outline-none"
                    />

                    <label class="block text-sm font-medium">{{
                        t('onboardServiceLabel')
                    }}</label>
                    <select
                        v-model.number="serviceId"
                        class="onboard-input w-full rounded-xl px-4 py-3 text-sm outline-none"
                    >
                        <option :value="null" disabled>—</option>
                        <option
                            v-for="service in services"
                            :key="service.id"
                            :value="service.id"
                        >
                            {{ service.name }}
                        </option>
                    </select>
                </template>

                <label class="block text-sm font-medium">{{
                    t('onboardCodeLabel')
                }}</label>
                <input
                    v-model="code"
                    type="text"
                    inputmode="numeric"
                    maxlength="4"
                    placeholder="0000"
                    class="onboard-input w-full rounded-xl px-4 py-3 text-center text-lg tracking-widest outline-none"
                    @keyup.enter="verify"
                />

                <button
                    type="button"
                    :disabled="loading || !canVerify"
                    class="onboard-primary-btn w-full rounded-xl px-4 py-3 text-sm font-semibold text-white disabled:opacity-50"
                    @click="verify"
                >
                    {{ loading ? t('sending') : t('onboardVerify') }}
                </button>
                <button
                    type="button"
                    :disabled="resendCooldown > 0"
                    class="w-full text-sm opacity-70 disabled:opacity-40"
                    @click="resend"
                >
                    {{
                        resendCooldown > 0
                            ? `${t('onboardResend')} (${resendCooldown}s)`
                            : t('onboardResend')
                    }}
                </button>
            </div>

            <p
                v-if="error"
                class="mt-3 text-center text-sm font-semibold"
                style="color: var(--status-busy, #ef4444)"
            >
                {{ error }}
            </p>
        </div>
    </div>
</template>

<style scoped>
.onboard-backdrop {
    background: rgba(0, 0, 0, 0.5);
}

.onboard-panel {
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    color: var(--panel-text);
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.3);
    backdrop-filter: blur(24px) saturate(160%);
    -webkit-backdrop-filter: blur(24px) saturate(160%);
}

.onboard-close {
    background: var(--surface-bg);
    border: 1px solid var(--surface-border);
    color: var(--panel-text);
}

.onboard-input {
    color: var(--panel-text);
    background: var(--surface-bg);
    border: 1px solid var(--surface-border);
}

.onboard-input::placeholder {
    color: var(--panel-muted-text);
    opacity: 0.75;
}

.onboard-primary-btn {
    background: var(--brand-primary);
    cursor: pointer;
    transition: opacity 0.12s ease;
}

.onboard-primary-btn:hover:not(:disabled) {
    opacity: 0.9;
}
</style>
