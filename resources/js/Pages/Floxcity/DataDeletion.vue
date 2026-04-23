<template>
    <div class="min-h-screen bg-white text-gray-900">
        <main class="mx-auto max-w-3xl px-6 py-10">
            <h1 class="mb-6 text-3xl font-bold">Видалення даних користувача</h1>
            <p class="mb-8 text-sm text-gray-500">
                Останнє оновлення: {{ lastUpdated }}
            </p>

            <!-- Interactive account deletion form -->
            <section
                class="mb-10 rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h2 class="mb-4 text-xl font-semibold">
                    Видалити акаунт за номером телефону
                </h2>
                <p class="mb-6 text-sm text-gray-600">
                    Введіть свій номер телефону, отримайте OTP-код та
                    підтвердіть видалення акаунта. Процес незворотній.
                </p>
                <div class="space-y-4">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Номер телефону</label
                        >
                        <input
                            v-model="phone"
                            type="tel"
                            placeholder="+380XXXXXXXXX"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            :disabled="loading || deleted"
                        />
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            @click="requestOtp"
                            :disabled="loading || deleted || !canSendOtp"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span v-if="!loading">Отримати OTP</span>
                            <span v-else class="inline-flex items-center gap-2">
                                <i class="fa fa-spinner animate-spin"></i>
                                Надсилаємо...
                            </span>
                        </button>
                        <div v-if="cooldown > 0" class="text-xs text-gray-500">
                            Повторний запит через {{ cooldown }} c
                        </div>
                    </div>
                    <div
                        v-if="otpSent"
                        class="rounded-md bg-green-50 p-3 text-sm text-green-700"
                    >
                        Код відправлено. Перевірте SMS. Якщо не отримали —
                        спробуйте ще раз через 60 секунд.
                    </div>
                    <div v-if="otpSent" class="pt-2">
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >OTP-код</label
                        >
                        <input
                            v-model="smsCode"
                            type="text"
                            placeholder="Введіть код"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            :disabled="loading || deleted"
                        />
                    </div>
                    <div class="pt-2">
                        <button
                            @click="confirmDelete"
                            :disabled="
                                loading ||
                                deleted ||
                                !otpSent ||
                                smsCode.trim().length === 0
                            "
                            class="w-full rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span v-if="!loading">Підтвердити видалення</span>
                            <span v-else class="inline-flex items-center gap-2">
                                <i class="fa fa-spinner animate-spin"></i>
                                Обробка...
                            </span>
                        </button>
                    </div>
                    <div
                        v-if="error"
                        class="rounded-md bg-red-50 p-3 text-sm text-red-700"
                    >
                        {{ error }}
                    </div>
                    <div
                        v-if="deleted"
                        class="rounded-md bg-green-50 p-3 text-sm text-green-700"
                    >
                        Акаунт успішно видалено. Дякуємо!
                    </div>
                </div>
                <p class="mt-4 text-xs text-gray-500">
                    Примітка: для підтвердження можна використати універсальний
                    код, якщо його надано службою підтримки.
                </p>
            </section>

            <section class="space-y-4">
                <p>
                    Ви маєте право на видалення своїх персональних даних. Нижче
                    описано способи подання запиту та порядок його опрацювання.
                </p>

                <h2 class="mt-6 text-xl font-semibold">
                    Як подати запит на видалення
                </h2>
                <ul class="list-disc space-y-2 pl-6">
                    <li>
                        Через електронну пошту: надішліть лист з темою
                        «Видалення даних» на адресу
                        <span class="font-medium">admin@flox.city</span>,
                        вказавши номер телефону, з яким пов'язаний акаунт, та
                        суть запиту.
                    </li>
                    <li>
                        Через застосунок/сайт: якщо доступно, скористайтеся
                        кнопкою «Видалити акаунт» у налаштуваннях профілю.
                    </li>
                </ul>

                <h2 class="mt-6 text-xl font-semibold">Підтвердження особи</h2>
                <p>
                    З міркувань безпеки ми можемо попросити підтвердити номер
                    телефону (OTP) або інший доказ належності акаунта, перш ніж
                    виконати запит.
                </p>

                <h2 class="mt-6 text-xl font-semibold">Що буде видалено</h2>
                <ul class="list-disc space-y-2 pl-6">
                    <li>Профіль користувача та контактні дані.</li>
                    <li>
                        Вміст, створений користувачем (наприклад, відгуки), якщо
                        це не суперечить вимогам законодавства.
                    </li>
                    <li>
                        Токени сповіщень/аналітики, пов'язані з вашим пристроєм.
                    </li>
                </ul>
                <p class="text-sm text-gray-600">
                    Частина даних може зберігатися у знеособленому вигляді для
                    аналітики або у журналах у межах строків, визначених
                    законом.
                </p>

                <h2 class="mt-6 text-xl font-semibold">Строки виконання</h2>
                <p>
                    Ми опрацьовуємо запити на видалення без зайвої затримки і,
                    як правило, завершуємо протягом 30 днів.
                </p>

                <h2 class="mt-6 text-xl font-semibold">Контакти</h2>
                <p>
                    З питань приватності та видалення даних звертайтесь:
                    <span class="font-medium">admin@flox.city</span>
                </p>
            </section>
        </main>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { computed, onBeforeUnmount, ref } from 'vue';

const lastUpdated = ref(new Date().toISOString().slice(0, 10));

// Interactive deletion state
const phone = ref('');
const smsCode = ref('');
const loading = ref(false);
const otpSent = ref(false);
const deleted = ref(false);
const error = ref('');
const cooldown = ref(0);
let timer: any = null;

const canSendOtp = computed(() => {
    return (
        cooldown.value === 0 &&
        phone.value.trim().length >= 10 &&
        !deleted.value
    );
});

function startCooldown(seconds = 60) {
    cooldown.value = seconds;
    clearInterval(timer);
    timer = setInterval(() => {
        if (cooldown.value > 0) cooldown.value -= 1;
        if (cooldown.value <= 0) clearInterval(timer);
    }, 1000);
}

async function requestOtp() {
    error.value = '';
    if (!canSendOtp.value) return;
    loading.value = true;
    try {
        await axios.post('/api/v1/auth/request-otp', {
            phone: phone.value.trim(),
        });
        otpSent.value = true;
        startCooldown(60);
    } catch (e: any) {
        error.value =
            e?.response?.data?.message ||
            'Не вдалося надіслати код. Спробуйте пізніше.';
    } finally {
        loading.value = false;
    }
}

async function confirmDelete() {
    error.value = '';
    if (!otpSent.value || smsCode.value.trim().length === 0) return;
    loading.value = true;
    try {
        await axios.post('/api/v1/account/delete', {
            phone: phone.value.trim(),
            sms_code: smsCode.value.trim(),
        });
        deleted.value = true;
        otpSent.value = false;
    } catch (e: any) {
        const msg = e?.response?.data?.error || e?.response?.data?.message;
        error.value =
            msg === 'invalid_code'
                ? 'Невірний код. Перевірте SMS і спробуйте ще раз.'
                : msg || 'Помилка видалення. Спробуйте пізніше.';
    } finally {
        loading.value = false;
    }
}

onBeforeUnmount(() => {
    clearInterval(timer);
});
</script>
