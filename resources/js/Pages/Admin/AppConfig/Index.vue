<template>
  <div class="p-6 space-y-8">
    <h1 class="text-2xl font-semibold">App Config</h1>

    <section class="border rounded p-4">
      <h2 class="font-semibold mb-3">App Versions (Force/Soft Update)</h2>
      <div class="grid md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <h3 class="font-medium">Android</h3>
          <label class="text-sm text-gray-700">Min supported build (обов’язкове оновлення)</label>
          <input v-model.number="versions.android.min_supported_build"
                 class="border p-2 rounded w-full" type="number" min="1" />
          <label class="text-sm text-gray-700">Recommended build (рекомендоване оновлення)</label>
          <input v-model.number="versions.android.recommended_build"
                 class="border p-2 rounded w-full" type="number" min="1" />
          <label class="text-sm text-gray-700">Store URL</label>
          <input v-model="versions.android.store_url" class="border p-2 rounded w-full" />
          <label class="text-sm text-gray-700">Повідомлення про оновлення</label>
          <textarea v-model="versions.android.message" rows="3" class="border p-2 rounded w-full"></textarea>
        </div>
        <div class="space-y-2">
          <h3 class="font-medium">iOS</h3>
          <label class="text-sm text-gray-700">Min supported build (обов’язкове оновлення)</label>
          <input v-model.number="versions.ios.min_supported_build"
                 class="border p-2 rounded w-full" type="number" min="1" />
          <label class="text-sm text-gray-700">Recommended build (рекомендоване оновлення)</label>
          <input v-model.number="versions.ios.recommended_build"
                 class="border p-2 rounded w-full" type="number" min="1" />
          <label class="text-sm text-gray-700">Store URL</label>
          <input v-model="versions.ios.store_url" class="border p-2 rounded w-full" />
          <label class="text-sm text-gray-700">Повідомлення про оновлення</label>
          <textarea v-model="versions.ios.message" rows="3" class="border p-2 rounded w-full"></textarea>
        </div>
      </div>
      <div class="mt-3 space-y-2">
        <button @click="saveVersions" :disabled="savingVersions" class="bg-blue-600 disabled:opacity-60 text-white px-4 py-2 rounded">
          {{ savingVersions ? 'Saving...' : 'Save Versions' }}
        </button>
        <div v-if="messageVersions.text" :class="['text-sm', messageVersions.type === 'success' ? 'text-green-700' : 'text-red-700']">
          {{ messageVersions.text }}
        </div>
      </div>
    </section>

    <section class="border rounded p-4">
      <h2 class="font-semibold mb-3">Subscription (Trial)</h2>
      <div class="flex items-center gap-3 mb-2">
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" v-model="subscription.trial_enabled" />
          <span>Trial enabled</span>
        </label>
        <div class="flex items-center gap-2">
          <label class="text-sm text-gray-700">Кількість днів (1–365)</label>
          <input v-model.number="subscription.trial_days" type="number" min="1" max="365"
                 class="border p-2 rounded w-32" />
        </div>
      </div>
      <div class="space-y-2">
        <button @click="saveSubscription" :disabled="savingSubscription" class="bg-blue-600 disabled:opacity-60 text-white px-4 py-2 rounded">
          {{ savingSubscription ? 'Saving...' : 'Save Trial' }}
        </button>
        <div v-if="messageSubscription.text" :class="['text-sm', messageSubscription.type === 'success' ? 'text-green-700' : 'text-red-700']">
          {{ messageSubscription.text }}
        </div>
      </div>
    </section>
  </div>
  </template>

  <script setup>
  import { ref, onMounted } from 'vue'
  import axios from 'axios'

  const versions = ref({
    android: { min_supported_build: 1, recommended_build: 1, message: '', store_url: '' },
    ios: { min_supported_build: 1, recommended_build: 1, message: '', store_url: '' },
  })
  const subscription = ref({ trial_enabled: true, trial_days: 30 })

  const savingVersions = ref(false)
  const savingSubscription = ref(false)
  const messageVersions = ref({ type: '', text: '' })
  const messageSubscription = ref({ type: '', text: '' })

  const showMessage = (target, type, text) => {
    target.value = { type, text }
    setTimeout(() => { target.value = { type: '', text: '' } }, 3000)
  }

  const load = async () => {
    const { data: v } = await axios.get('/admin-api/app-config/versions')
    const { data: s } = await axios.get('/admin-api/app-config/subscription')
    versions.value = Object.assign(versions.value, v)
    subscription.value = Object.assign(subscription.value, s)
  }

  const saveVersions = async () => {
    if (savingVersions.value) return
    savingVersions.value = true
    try {
      await axios.post('/admin-api/app-config/versions', versions.value)
      showMessage(messageVersions, 'success', 'Налаштування версій збережено')
    } catch (e) {
      const msg = e?.response?.data?.message || 'Помилка збереження налаштувань версій'
      showMessage(messageVersions, 'error', msg)
    } finally {
      savingVersions.value = false
    }
  }
  const saveSubscription = async () => {
    if (savingSubscription.value) return
    savingSubscription.value = true
    try {
      await axios.post('/admin-api/app-config/subscription', subscription.value)
      showMessage(messageSubscription, 'success', 'Налаштування trial збережено')
    } catch (e) {
      const msg = e?.response?.data?.message || 'Помилка збереження налаштувань trial'
      showMessage(messageSubscription, 'error', msg)
    } finally {
      savingSubscription.value = false
    }
  }

  onMounted(load)
  </script>

  <style scoped>
  </style>

