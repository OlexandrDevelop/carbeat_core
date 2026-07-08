<template>
    <div
        :class="[
            'master-login-page flex min-h-screen items-center justify-center p-6',
            portalThemeClass,
        ]"
    >
        <GlassPanel class="w-full max-w-sm" padding="lg">
            <div class="mb-6 text-center">
                <p
                    class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                >
                    {{ brandName }}
                </p>
                <h1 class="text-2xl font-extrabold text-slate-900">
                    Кабінет майстра
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Вхід за номером телефону та SMS-кодом
                </p>
            </div>

            <div v-if="step === 'phone'" class="space-y-4">
                <label class="block text-sm font-medium text-slate-700"
                    >Номер телефону</label
                >
                <input
                    v-model="phone"
                    type="tel"
                    placeholder="+380XXXXXXXXX"
                    class="glass-surface w-full rounded-2xl px-4 py-3 text-sm outline-none"
                    @keyup.enter="requestOtp"
                />
                <button
                    type="button"
                    :disabled="loading || !phone"
                    class="w-full rounded-2xl px-4 py-3 text-sm font-semibold text-white transition disabled:opacity-50"
                    :style="{ backgroundColor: 'var(--brand-primary)' }"
                    @click="requestOtp"
                >
                    {{ loading ? 'Надсилаємо…' : 'Надіслати код' }}
                </button>
            </div>

            <div v-else class="space-y-4">
                <label class="block text-sm font-medium text-slate-700"
                    >Код з SMS</label
                >
                <input
                    v-model="otp"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="0000"
                    class="glass-surface w-full rounded-2xl px-4 py-3 text-center text-lg tracking-widest outline-none"
                    @keyup.enter="verifyOtp"
                />
                <button
                    type="button"
                    :disabled="loading || otp.length < 4"
                    class="w-full rounded-2xl px-4 py-3 text-sm font-semibold text-white transition disabled:opacity-50"
                    :style="{ backgroundColor: 'var(--brand-primary)' }"
                    @click="verifyOtp"
                >
                    {{ loading ? 'Перевіряємо…' : 'Увійти' }}
                </button>
                <button
                    type="button"
                    class="w-full text-sm text-slate-500 hover:text-slate-800"
                    @click="resend"
                >
                    Надіслати код повторно
                </button>
            </div>

            <p
                v-if="error"
                class="mt-4 text-center text-sm font-semibold"
                style="color: var(--status-busy)"
            >
                {{ error }}
            </p>
        </GlassPanel>
    </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import GlassPanel from '../../../components/MasterCrm/GlassPanel.vue';
import { useBrand } from '../../../composables/useBrand';
import AuthLayout from '../AuthLayout.vue';

defineOptions({ layout: AuthLayout });

const { brandName, portalThemeClass } = useBrand();

const phone = ref('');
const otp = ref('');
const step = ref<'phone' | 'otp'>('phone');
const loading = ref(false);
const error = ref('');

async function requestOtp() {
    error.value = '';
    loading.value = true;
    try {
        await axios.post('/master-auth/request-otp', { phone: phone.value });
        step.value = 'otp';
    } catch (e: any) {
        error.value = e?.response?.data?.message ?? 'Не вдалося надіслати код';
    } finally {
        loading.value = false;
    }
}

async function verifyOtp() {
    error.value = '';
    loading.value = true;
    try {
        await axios.post('/master-auth/verify-otp', {
            phone: phone.value,
            sms_code: otp.value,
        });
        router.visit('/master');
    } catch (e: any) {
        error.value =
            e?.response?.data?.error ??
            e?.response?.data?.message ??
            'Невірний код';
    } finally {
        loading.value = false;
    }
}

function resend() {
    requestOtp();
}
</script>

<style scoped>
.master-login-page {
    background: radial-gradient(
            circle at top left,
            rgba(var(--brand-primary-rgb), 0.16),
            transparent 55%
        ),
        #f4f6fa;
}
</style>
