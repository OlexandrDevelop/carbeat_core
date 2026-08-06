<template>
    <div :class="['repair-request-shell min-h-screen', portalThemeClass]">
        <MapBackdrop />

        <div
            class="glass-surface relative z-10 flex items-center justify-between gap-2 p-3"
        >
            <a href="/" class="flex items-center gap-2">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-sm font-bold text-white"
                    :style="{ backgroundColor: 'var(--brand-primary)' }"
                >
                    CB
                </div>
                <span class="text-base font-extrabold text-slate-900">{{
                    brandName
                }}</span>
            </a>
            <div class="inline-flex items-center gap-1 rounded-lg bg-sky-50 p-0.5">
                <button
                    v-for="lang in ['en', 'uk', 'de'] as const"
                    :key="lang"
                    type="button"
                    class="rounded-md px-2 py-1 text-xs font-semibold"
                    :class="
                        currentLang === lang
                            ? 'bg-white text-slate-900'
                            : 'bg-white/70 text-slate-600'
                    "
                    @click="setLanguage(lang)"
                >
                    {{ lang.toUpperCase() }}
                </button>
            </div>
        </div>

        <main class="relative z-10 mx-auto max-w-2xl px-4 py-8 sm:py-12">
            <div class="mb-6 text-center">
                <span
                    class="glass-surface inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold text-slate-700"
                >
                    🔧 {{ t('rrBadge') }}
                </span>
                <h1
                    class="mt-4 text-3xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-4xl"
                >
                    {{ t('rrHeadingPrefix') }}
                    <span :style="{ color: 'var(--brand-primary)' }">{{
                        t('rrHeadingHighlight')
                    }}</span>
                </h1>
                <p class="mt-3 text-base text-slate-600 sm:text-lg">
                    {{ t('rrSubtitle') }}
                </p>
            </div>

            <GlassPanel class="space-y-5">
                <!-- SUCCESS STATE -->
                <div v-if="step === 'success'" class="py-8 text-center">
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-2xl"
                    >
                        ✅
                    </div>
                    <h2 class="text-xl font-bold text-slate-900">
                        {{ t('rrSuccessTitle') }}
                    </h2>
                    <p class="mt-2 text-slate-600">
                        {{ successMessage }}
                    </p>
                    <a
                        href="/"
                        class="mt-6 inline-flex rounded-full px-5 py-2.5 text-sm font-semibold text-white transition"
                        :style="{ backgroundColor: 'var(--brand-primary)' }"
                        >{{ t('rrBackHome') }}</a
                    >
                </div>

                <!-- FORM STEP -->
                <form
                    v-else-if="step === 'form'"
                    class="space-y-5"
                    @submit.prevent="submitForm"
                >
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-slate-700"
                                >{{ t('rrCarMakeLabel') }}</label
                            >
                            <select
                                v-model="form.car_make"
                                required
                                class="glass-surface w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            >
                                <option value="" disabled>
                                    {{ t('rrCarMakePlaceholder') }}
                                </option>
                                <option
                                    v-for="make in props.carMakes"
                                    :key="make"
                                    :value="make"
                                >
                                    {{ make === 'Інше' ? t('rrOther') : make }}
                                </option>
                            </select>
                            <p
                                v-if="errors.car_make"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ errors.car_make }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-slate-700"
                                >{{ t('rrCarModelLabel') }}
                                <span class="font-normal text-slate-400">{{
                                    t('rrOptional')
                                }}</span></label
                            >
                            <input
                                v-model="form.car_model"
                                type="text"
                                :placeholder="t('rrCarModelPlaceholder')"
                                class="glass-surface w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            />
                            <p
                                v-if="errors.car_model"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ errors.car_model }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-slate-700"
                                >{{ t('rrCarYearLabel') }}
                                <span class="font-normal text-slate-400">{{
                                    t('rrOptional')
                                }}</span></label
                            >
                            <input
                                v-model="form.car_year"
                                type="number"
                                inputmode="numeric"
                                min="1970"
                                :max="maxYear"
                                placeholder="2018"
                                class="glass-surface w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            />
                            <p
                                v-if="errors.car_year"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ errors.car_year }}
                            </p>
                        </div>
                    </div>

                    <div class="relative">
                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                            >{{ t('rrCityLabel') }}</label
                        >
                        <input
                            v-model="cityQuery"
                            type="text"
                            required
                            autocomplete="off"
                            :placeholder="t('rrCityPlaceholder')"
                            class="glass-surface w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            @input="onCityInput"
                            @focus="showCitySuggestions = true"
                            @blur="onCityBlur"
                        />
                        <ul
                            v-if="showCitySuggestions && citySuggestions.length > 0"
                            class="glass-panel absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-xl py-1"
                        >
                            <li
                                v-for="city in citySuggestions"
                                :key="`${city.name}-${city.lat}-${city.lng}`"
                            >
                                <button
                                    type="button"
                                    class="block w-full px-3 py-2 text-left text-sm hover:bg-white/50"
                                    @mousedown.prevent="selectCity(city)"
                                >
                                    {{ city.name }}
                                </button>
                            </li>
                        </ul>
                        <p v-if="errors.city" class="mt-1 text-xs text-red-600">
                            {{ errors.city }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                            >{{ t('rrServiceLabel') }}</label
                        >
                        <select
                            v-model="form.service_id"
                            required
                            class="glass-surface w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                        >
                            <option value="" disabled>
                                {{ t('rrServicePlaceholder') }}
                            </option>
                            <option
                                v-for="service in props.services"
                                :key="service.id"
                                :value="service.id"
                            >
                                {{ service.labels[currentLang] }}
                            </option>
                            <option value="other">{{ t('rrOther') }}</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                            >{{ t('rrDescriptionLabel') }}</label
                        >
                        <textarea
                            v-model="form.description"
                            required
                            rows="4"
                            :placeholder="t('rrDescriptionPlaceholder')"
                            class="glass-surface w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                        ></textarea>
                        <p
                            v-if="errors.description"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ errors.description }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-slate-700"
                                >{{ t('rrNameLabel') }}</label
                            >
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                :placeholder="t('rrNamePlaceholder')"
                                class="glass-surface w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            />
                            <p v-if="errors.name" class="mt-1 text-xs text-red-600">
                                {{ errors.name }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-slate-700"
                                >{{ t('rrPhoneLabel') }}</label
                            >
                            <input
                                v-model="form.phone"
                                type="tel"
                                required
                                placeholder="+380XXXXXXXXX"
                                class="glass-surface w-full rounded-xl px-3 py-2.5 text-sm outline-none"
                            />
                            <p
                                v-if="errors.phone"
                                class="mt-1 text-xs text-red-600"
                            >
                                {{ errors.phone }}
                            </p>
                        </div>
                    </div>

                    <p v-if="generalError" class="text-sm font-medium text-red-600">
                        {{ generalError }}
                    </p>

                    <button
                        type="submit"
                        :disabled="loading || !isFormValid"
                        class="w-full rounded-full px-4 py-3 text-sm font-semibold text-white transition disabled:opacity-50"
                        :style="{ backgroundColor: 'var(--brand-primary)' }"
                    >
                        {{ loading ? t('rrSubmitSending') : t('rrSubmitCta') }}
                    </button>
                </form>

                <!-- OTP STEP -->
                <div v-else class="space-y-4 text-center">
                    <h2 class="text-lg font-bold text-slate-900">
                        {{ t('rrOtpTitle') }}
                    </h2>
                    <p class="text-sm text-slate-600">
                        {{ otpSubtitle }}
                    </p>
                    <input
                        v-model="otp"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        placeholder="0000"
                        class="glass-surface mx-auto w-full max-w-[200px] rounded-2xl px-4 py-3 text-center text-lg tracking-widest outline-none"
                        @keyup.enter="verifyAndSubmit"
                    />
                    <p v-if="generalError" class="text-sm font-medium text-red-600">
                        {{ generalError }}
                    </p>
                    <button
                        type="button"
                        :disabled="loading || otp.length < 4"
                        class="w-full rounded-full px-4 py-3 text-sm font-semibold text-white transition disabled:opacity-50"
                        :style="{ backgroundColor: 'var(--brand-primary)' }"
                        @click="verifyAndSubmit"
                    >
                        {{ loading ? t('rrOtpVerifying') : t('rrOtpVerifyCta') }}
                    </button>
                    <div class="flex justify-center gap-6 text-sm">
                        <button
                            type="button"
                            class="text-slate-500 hover:text-slate-800"
                            @click="step = 'form'"
                        >
                            {{ t('rrOtpChangeData') }}
                        </button>
                        <button
                            type="button"
                            class="font-semibold"
                            :style="{ color: 'var(--brand-primary)' }"
                            @click="requestOtp"
                        >
                            {{ t('rrOtpResend') }}
                        </button>
                    </div>
                </div>
            </GlassPanel>
        </main>
    </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';
import MapBackdrop from '@/components/MapBackdrop.vue';
import GlassPanel from '@/components/MasterCrm/GlassPanel.vue';
import { useBrand } from '@/composables/useBrand';
import { useGuestLang } from '@/composables/useGuestLang';
import { getUiTextWithParams } from '@/shared/guest-map-display-labels';
import type { PageProps } from '@/types';

interface ServiceOption {
    id: number;
    labels: Record<'en' | 'uk' | 'de', string>;
}

const props = defineProps<{
    carMakes: string[];
    services: ServiceOption[];
}>();

const { brandName, portalThemeClass } = useBrand();
const { currentLang, t, setLanguage, initLanguage } = useGuestLang();
initLanguage();

const maxYear = new Date().getFullYear() + 1;

const step = ref<'form' | 'otp' | 'success'>('form');
const loading = ref(false);
const otp = ref('');
const generalError = ref('');
const errors = reactive<Record<string, string>>({});

// Already-logged-in drivers (e.g. returning from a previous request) don't
// need to retype a phone number we already verified for them.
const authUser = usePage<PageProps>().props.auth?.user as
    | { phone?: string }
    | null
    | undefined;

const form = reactive({
    car_make: '',
    car_model: '',
    car_year: '',
    service_id: '' as number | 'other' | '',
    description: '',
    name: '',
    phone: authUser?.phone ?? '',
    city: '',
    latitude: null as number | null,
    longitude: null as number | null,
});

const isFormValid = computed(
    () =>
        !!form.car_make &&
        !!form.service_id &&
        !!form.description &&
        !!form.name &&
        !!form.phone &&
        !!form.city &&
        form.latitude !== null &&
        form.longitude !== null,
);

const carDisplay = computed(() =>
    [form.car_make, form.car_model].filter(Boolean).join(' '),
);

const successMessage = computed(() =>
    getUiTextWithParams(currentLang.value, 'rrSuccessMessage', [
        form.name,
        carDisplay.value,
    ]),
);

const otpSubtitle = computed(() =>
    getUiTextWithParams(currentLang.value, 'rrOtpSubtitle', [form.phone]),
);

interface CitySuggestion {
    name: string;
    lat: number;
    lng: number;
}

const cityQuery = ref('');
const citySuggestions = ref<CitySuggestion[]>([]);
const showCitySuggestions = ref(false);
let cityDebounceTimer: ReturnType<typeof setTimeout> | undefined;

function onCityInput() {
    // Typing again invalidates whatever city was previously picked — force
    // re-selection from the list rather than silently submitting stale
    // coordinates for a name the driver has since changed.
    form.city = '';
    form.latitude = null;
    form.longitude = null;
    showCitySuggestions.value = true;

    clearTimeout(cityDebounceTimer);
    const query = cityQuery.value.trim();
    if (query.length < 2) {
        citySuggestions.value = [];
        return;
    }

    cityDebounceTimer = setTimeout(async () => {
        try {
            const response = await axios.get('/repair-request/cities', {
                params: { q: query },
            });
            citySuggestions.value = response.data.data;
        } catch {
            citySuggestions.value = [];
        }
    }, 300);
}

function selectCity(city: CitySuggestion) {
    cityQuery.value = city.name;
    form.city = city.name;
    form.latitude = city.lat;
    form.longitude = city.lng;
    citySuggestions.value = [];
    showCitySuggestions.value = false;
}

function onCityBlur() {
    // Delay so a click on a suggestion (which fires blur first) still
    // registers via @mousedown.prevent before the list disappears.
    setTimeout(() => {
        showCitySuggestions.value = false;
    }, 150);
}

function clearErrors() {
    generalError.value = '';
    Object.keys(errors).forEach((key) => delete errors[key]);
}

async function submitForm() {
    if (!isFormValid.value) return;
    await requestOtp();
}

async function requestOtp() {
    clearErrors();
    loading.value = true;
    try {
        await axios.post('/repair-request/request-otp', {
            phone: form.phone,
        });
        step.value = 'otp';
    } catch (e: any) {
        applyErrorResponse(e);
    } finally {
        loading.value = false;
    }
}

async function verifyAndSubmit() {
    clearErrors();
    loading.value = true;
    try {
        await axios.post('/repair-request/verify-otp', {
            phone: form.phone,
            sms_code: otp.value,
            name: form.name,
            car_make: form.car_make,
            car_model: form.car_model || null,
            car_year: form.car_year || null,
            description: form.description,
            service_id: form.service_id === 'other' ? null : form.service_id,
            city: form.city,
            latitude: form.latitude,
            longitude: form.longitude,
        });
        step.value = 'success';
    } catch (e: any) {
        applyErrorResponse(e);
    } finally {
        loading.value = false;
    }
}

function applyErrorResponse(e: any) {
    const data = e?.response?.data;
    if (data?.errors) {
        for (const key of Object.keys(data.errors)) {
            errors[key] = Array.isArray(data.errors[key])
                ? data.errors[key][0]
                : String(data.errors[key]);
        }
        generalError.value = t('rrValidationError');
        return;
    }
    generalError.value = data?.error ?? data?.message ?? t('rrGenericError');
}
</script>

<style scoped>
.repair-request-shell {
    position: relative;
    overflow: hidden;
    background: #f4f6fa;
}
</style>
