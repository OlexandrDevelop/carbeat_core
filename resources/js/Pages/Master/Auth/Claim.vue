<template>
    <div
        :class="[
            'master-login-page flex min-h-screen items-center justify-center p-6',
            portalThemeClass,
        ]"
    >
        <GlassPanel class="w-full max-w-sm" padding="lg">
            <div
                v-if="step === 'deeplinking'"
                class="py-8 text-center text-sm text-slate-500"
            >
                Відкриваємо застосунок…
            </div>

            <template v-else-if="step === 'notfound'">
                <div class="mb-6 text-center">
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                        {{ brandName }}
                    </p>
                    <h1 class="text-2xl font-extrabold text-slate-900">
                        {{
                            notFoundReason === 'claimed'
                                ? 'Профіль вже підтверджено'
                                : 'Посилання недійсне'
                        }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{
                            notFoundReason === 'claimed'
                                ? 'Цей профіль вже хтось підтвердив раніше.'
                                : 'Можливо, посилання застаріло або вже було використано.'
                        }}
                    </p>
                </div>
                <a
                    href="/master-login"
                    class="block w-full rounded-2xl px-4 py-3 text-center text-sm font-semibold text-white transition"
                    :style="{ backgroundColor: 'var(--brand-primary)' }"
                >
                    Увійти в кабінет майстра
                </a>
            </template>

            <template v-else>
                <div class="mb-6 text-center">
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                        {{ brandName }}
                    </p>
                    <h1 class="text-2xl font-extrabold text-slate-900">
                        Підтвердження профілю
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Це ваш бізнес? Підтвердьте номер телефону.
                    </p>
                </div>

                <div v-if="step === 'preview'" class="space-y-4">
                    <div
                        class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3"
                    >
                        <img
                            :src="master?.photo"
                            :alt="master?.name"
                            class="h-14 w-14 rounded-xl object-cover"
                        />
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">
                                {{ master?.name }}
                            </p>
                            <p
                                v-if="master?.city"
                                class="text-sm text-slate-500"
                            >
                                {{ master?.city }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="master?.services?.length"
                        class="flex flex-wrap gap-1.5"
                    >
                        <span
                            v-for="service in master.services"
                            :key="service"
                            class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600"
                        >
                            {{ service }}
                        </span>
                    </div>

                    <div
                        v-if="master?.maskedPhone"
                        class="rounded-2xl border border-slate-200 px-4 py-3 text-center text-sm text-slate-600"
                    >
                        Код підтвердження надішлемо на {{ master.maskedPhone }}
                    </div>
                    <p
                        v-else
                        class="text-center text-sm font-semibold"
                        style="color: var(--status-busy)"
                    >
                        Номер телефону не вказано. Зверніться в підтримку.
                    </p>

                    <button
                        type="button"
                        :disabled="loading || !master?.maskedPhone"
                        class="w-full rounded-2xl px-4 py-3 text-sm font-semibold text-white transition disabled:opacity-50"
                        :style="{ backgroundColor: 'var(--brand-primary)' }"
                        @click="sendCode"
                    >
                        {{ loading ? 'Надсилаємо…' : 'Надіслати код' }}
                    </button>

                    <button
                        v-if="deepLink"
                        type="button"
                        class="w-full text-sm text-slate-500 hover:text-slate-800"
                        @click="openApp"
                    >
                        Відкрити в застосунку
                    </button>
                </div>

                <div v-else-if="step === 'sent'" class="space-y-4">
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
                        @keyup.enter="verify"
                    />
                    <button
                        type="button"
                        :disabled="loading || otp.length < 4"
                        class="w-full rounded-2xl px-4 py-3 text-sm font-semibold text-white transition disabled:opacity-50"
                        :style="{ backgroundColor: 'var(--brand-primary)' }"
                        @click="verify"
                    >
                        {{ loading ? 'Перевіряємо…' : 'Увійти' }}
                    </button>
                    <button
                        type="button"
                        :disabled="resendCooldown > 0"
                        class="w-full text-sm text-slate-500 hover:text-slate-800 disabled:opacity-50"
                        @click="resend"
                    >
                        {{
                            resendCooldown > 0
                                ? `Надіслати повторно через ${resendCooldown}с`
                                : 'Надіслати код повторно'
                        }}
                    </button>
                </div>

                <p
                    v-if="error"
                    class="mt-4 text-center text-sm font-semibold"
                    style="color: var(--status-busy)"
                >
                    {{ error }}
                </p>
            </template>
        </GlassPanel>
    </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';
import GlassPanel from '../../../components/MasterCrm/GlassPanel.vue';
import { useBrand } from '../../../composables/useBrand';
import AuthLayout from '../AuthLayout.vue';

defineOptions({ layout: AuthLayout });

const props = defineProps<{
    token: string;
    notFound: boolean;
    master: {
        name: string;
        photo: string;
        city: string | null;
        services: string[];
        maskedPhone: string | null;
    } | null;
    deepLink: string;
    androidStoreUrl: string;
    iosStoreUrl: string;
}>();

const { brandName, portalThemeClass } = useBrand();

const step = ref<'deeplinking' | 'preview' | 'sent' | 'notfound'>('preview');
const notFoundReason = ref<'invalid' | 'claimed'>('invalid');
const otp = ref('');
const loading = ref(false);
const error = ref('');
const resendCooldown = ref(0);

onMounted(() => {
    if (props.notFound) {
        step.value = 'notfound';
        return;
    }

    const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    if (isMobile && props.deepLink) {
        step.value = 'deeplinking';
        window.location.href = props.deepLink;
        setTimeout(() => {
            if (step.value === 'deeplinking') {
                step.value = 'preview';
            }
        }, 1300);
    } else {
        step.value = 'preview';
    }
});

function openApp() {
    window.location.href = props.deepLink;
}

function startResendCooldown() {
    resendCooldown.value = 30;
    const timer = window.setInterval(() => {
        resendCooldown.value -= 1;
        if (resendCooldown.value <= 0) {
            window.clearInterval(timer);
        }
    }, 1000);
}

async function sendCode() {
    error.value = '';
    loading.value = true;
    try {
        await axios.post(`/claim/${props.token}/send-code`);
        step.value = 'sent';
        startResendCooldown();
    } catch (e: any) {
        if (e?.response?.status === 409) {
            notFoundReason.value = 'claimed';
            step.value = 'notfound';
        } else {
            error.value =
                e?.response?.data?.message ?? 'Не вдалося надіслати код';
        }
    } finally {
        loading.value = false;
    }
}

async function verify() {
    error.value = '';
    loading.value = true;
    try {
        await axios.post(`/claim/${props.token}/verify`, { code: otp.value });
        router.visit('/master');
    } catch (e: any) {
        const code = e?.response?.data?.error;
        error.value =
            code === 'code_expired'
                ? 'Код прострочено. Натисніть «Надіслати код повторно».'
                : 'Невірний код.';
    } finally {
        loading.value = false;
    }
}

function resend() {
    if (resendCooldown.value > 0) return;
    sendCode();
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
