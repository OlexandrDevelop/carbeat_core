<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100">
        <div class="mx-auto flex min-h-screen max-w-6xl items-center justify-center px-6">
            <div class="grid w-full grid-cols-1 items-center gap-10 lg:grid-cols-2">
                <div class="hidden lg:block">
                    <div class="relative rounded-3xl bg-black p-1 shadow-2xl shadow-gray-300/40">
                        <div class="rounded-3xl bg-gradient-to-br from-gray-900 to-gray-800 p-8">
                            <h2 class="mb-4 bg-gradient-to-r from-white to-gray-300 bg-clip-text text-4xl font-bold text-transparent">
                                Welcome to Admin
                            </h2>
                            <p class="max-w-md text-gray-400">
                                Manage masters with a clean, fast, and secure interface.
                            </p>
                            <div class="mt-8 grid grid-cols-3 gap-3 text-center text-gray-400">
                                <div class="rounded-xl bg-white/5 p-4">
                                    <div class="text-2xl font-semibold text-white">OTP</div>
                                    <div class="text-xs">Secure login</div>
                                </div>
                                <div class="rounded-xl bg-white/5 p-4">
                                    <div class="text-2xl font-semibold text-white">Fast</div>
                                    <div class="text-xs">Lightning quick</div>
                                </div>
                                <div class="rounded-xl bg-white/5 p-4">
                                    <div class="text-2xl font-semibold text-white">Clean</div>
                                    <div class="text-xs">Apple-like UI</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="mx-auto w-full max-w-md rounded-3xl border border-gray-200 bg-white/80 p-8 shadow-xl backdrop-blur">
                        <div class="mb-6 text-center">
                            <div class="mx-auto mb-3 h-12 w-12 rounded-2xl bg-black text-white grid place-items-center">
                                <span class="text-xl">A</span>
                            </div>
                            <h1 class="text-2xl font-semibold text-gray-900">Admin Login</h1>
                            <p class="text-sm text-gray-500">Sign in with your phone number and OTP code</p>
                        </div>

                        <div v-if="step === 'phone'" class="space-y-4">
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input v-model="phone" type="tel" placeholder="+380XXXXXXXXX"
                                   class="w-full rounded-2xl bg-gray-100 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            <button @click="requestOtp" :disabled="loading || !phone"
                                    class="mt-2 w-full rounded-2xl bg-black px-4 py-3 text-sm text-white transition hover:opacity-90 disabled:opacity-50">
                                {{ loading ? 'Sending...' : 'Send code' }}
                            </button>
                        </div>

                        <div v-else class="space-y-4">
                            <label class="block text-sm font-medium text-gray-700">Enter OTP</label>
                            <input v-model="otp" type="text" inputmode="numeric" maxlength="6" placeholder="000000"
                                   class="tracking-widest w-full rounded-2xl bg-gray-100 px-4 py-3 text-center text-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            <button @click="verifyOtp" :disabled="loading || otp.length !== 4"
                                    class="mt-2 w-full rounded-2xl bg-black px-4 py-3 text-sm text-white transition hover:opacity-90 disabled:opacity-50">
                                {{ loading ? 'Verifying...' : 'Verify' }}
                            </button>
                            <button @click="resend" class="w-full text-sm text-gray-600 hover:text-gray-900">Resend code</button>
                        </div>

                        <p v-if="error" class="mt-4 text-center text-sm text-red-600">{{ error }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const phone = ref('');
const otp = ref('');
const step = ref<'phone' | 'otp'>('phone');
const loading = ref(false);
const error = ref('');

async function requestOtp() {
    error.value = '';
    loading.value = true;
    try {
        await axios.post('/admin-auth/request-otp', { phone: phone.value });
        step.value = 'otp';
    } catch (e: any) {
        error.value = e?.response?.data?.message || 'Failed to send code';
    } finally {
        loading.value = false;
    }
}

async function verifyOtp() {
    error.value = '';
    loading.value = true;
    try {
        await axios.post('/admin-auth/verify-otp', { phone: phone.value, sms_code: otp.value });
        router.visit('/admin/masters');
    } catch (e: any) {
        error.value = e?.response?.data?.message || 'Invalid code';
    } finally {
        loading.value = false;
    }
}

function resend() {
    requestOtp();
}
</script>

<style scoped>
</style>
