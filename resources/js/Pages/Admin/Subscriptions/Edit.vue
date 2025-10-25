<template>
  <div class="p-6">
    <h1 class="text-2xl font-semibold mb-4">Edit Subscription #{{ id }}</h1>

    <div class="grid grid-cols-2 gap-4 max-w-2xl">
      <label class="block">
        <span class="text-sm text-gray-600">Status</span>
        <select v-model="form.status" class="border p-2 rounded w-full">
          <option value="active">Active</option>
          <option value="expired">Expired</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </label>
      <label class="block">
        <span class="text-sm text-gray-600">Product ID</span>
        <input v-model="form.product_id" class="border p-2 rounded w-full" />
      </label>
      <label class="block col-span-2">
        <span class="text-sm text-gray-600">Expires At</span>
        <input v-model="form.expires_at" type="datetime-local" class="border p-2 rounded w-full" />
      </label>
    </div>

    <div class="mt-4 flex gap-2">
      <button @click="save()" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
      <button @click="back()" class="bg-gray-200 px-4 py-2 rounded">Back</button>
    </div>

    <div class="mt-8 border-t pt-4 max-w-2xl">
      <h2 class="text-lg font-semibold mb-2">Verify Subscription</h2>
      <div class="grid grid-cols-2 gap-4">
        <label class="block">
          <span class="text-sm text-gray-600">User ID</span>
          <input v-model.number="verifyForm.user_id" type="number" class="border p-2 rounded w-full" />
        </label>
        <label class="block">
          <span class="text-sm text-gray-600">Platform</span>
          <select v-model="verifyForm.platform" class="border p-2 rounded w-full">
            <option value="apple">Apple</option>
            <option value="google">Google</option>
          </select>
        </label>
        <label class="block col-span-2">
          <span class="text-sm text-gray-600">Receipt/Token</span>
          <textarea v-model="verifyForm.receipt_token" class="border p-2 rounded w-full" rows="3"></textarea>
        </label>
        <label class="block col-span-2">
          <span class="text-sm text-gray-600">Product ID (optional)</span>
          <input v-model="verifyForm.product_id" class="border p-2 rounded w-full" />
        </label>
      </div>
      <div class="mt-2">
        <button @click="verify()" class="bg-green-600 text-white px-4 py-2 rounded">Verify & Store</button>
        <span class="ml-2 text-sm text-gray-600" v-if="lastVerify">Last verify: {{ lastVerify }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

const props = defineProps({ subscriptionId: Number })
const id = props.subscriptionId
const form = ref({ status: 'active', product_id: '', expires_at: '' })
const lastVerify = ref('')
const verifyForm = ref({ user_id: 0, platform: 'apple', receipt_token: '', product_id: '' })

const load = async () => {
  const { data } = await axios.get(`/admin-api/subscriptions/${id}`)
  const s = data
  form.value.status = s.status
  form.value.product_id = s.product_id || ''
  form.value.expires_at = s.expires_at ? s.expires_at.substring(0, 16) : ''
}

const save = async () => {
  await axios.put(`/admin-api/subscriptions/${id}`, form.value)
  back()
}

const back = () => router.visit('/admin/subscriptions')

const verify = async () => {
  const { data } = await axios.post(`/admin-api/subscriptions/verify`, verifyForm.value)
  lastVerify.value = JSON.stringify(data)
}

onMounted(load)
</script>

<style scoped>
</style>
