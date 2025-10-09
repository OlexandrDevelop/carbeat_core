<template>
  <div class="p-6">
    <h1 class="text-2xl font-semibold mb-4">Edit Tariff #{{ id }}</h1>

    <div class="grid grid-cols-2 gap-4 max-w-3xl">
      <label class="block">
        <span class="text-sm text-gray-600">Name</span>
        <input v-model="form.name" class="border p-2 rounded w-full" />
      </label>
      <label class="block">
        <span class="text-sm text-gray-600">Currency</span>
        <select v-model="form.currency" class="border p-2 rounded w-full">
          <option value="USD">USD</option>
          <option value="EUR">EUR</option>
          <option value="UAH">UAH</option>
        </select>
      </label>
      <label class="block">
        <span class="text-sm text-gray-600">Price</span>
        <input v-model.number="form.price" type="number" class="border p-2 rounded w-full" />
      </label>
      <label class="block">
        <span class="text-sm text-gray-600">Apple Product ID</span>
        <input v-model="form.apple_product_id" class="border p-2 rounded w-full" />
      </label>
      <label class="block">
        <span class="text-sm text-gray-600">Google Product ID</span>
        <input v-model="form.google_product_id" class="border p-2 rounded w-full" />
      </label>
      <label class="block col-span-2">
        <span class="text-sm text-gray-600">Features (JSON)</span>
        <textarea v-model="featuresText" rows="6" class="border p-2 rounded w-full"></textarea>
      </label>
    </div>

    <div class="mt-4 flex gap-2">
      <button @click="save()" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
      <button @click="back()" class="bg-gray-200 px-4 py-2 rounded">Back</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

const props = defineProps({ tariffId: Number })
const id = props.tariffId
const form = ref({ name: '', price: 0, currency: 'USD', features: [], apple_product_id: '', google_product_id: '' })
const featuresText = ref('[]')

watch(featuresText, (t) => { try { form.value.features = JSON.parse(t || '[]') } catch (e) {} })

const load = async () => {
  const { data } = await axios.get(`/admin-api/tariffs/${id}`)
  const t = data
  form.value = { ...form.value, ...t }
  featuresText.value = JSON.stringify(form.value.features || [], null, 2)
}

const save = async () => {
  await axios.put(`/admin-api/tariffs/${id}`, form.value)
  back()
}

const back = () => router.visit('/admin/tariffs')

onMounted(load)
</script>

<style scoped>
</style>
