<template>
  <div class="p-6">
    <h1 class="text-2xl font-semibold mb-4">Tariffs</h1>
    <div class="flex gap-2 mb-4">
      <input v-model="filters.q" placeholder="Search by name" class="border p-2 rounded" />
      <select v-model="filters.currency" class="border p-2 rounded">
        <option value="">All currencies</option>
        <option value="USD">USD</option>
        <option value="EUR">EUR</option>
        <option value="UAH">UAH</option>
      </select>
      <button @click="load()" class="bg-blue-600 text-white px-4 py-2 rounded">Search</button>
      <button @click="create()" class="bg-green-600 text-white px-4 py-2 rounded">Create</button>
    </div>

    <table class="w-full border text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="p-2 text-left">ID</th>
          <th class="p-2 text-left">Name</th>
          <th class="p-2 text-left">Price</th>
          <th class="p-2 text-left">Currency</th>
          <th class="p-2 text-left">Apple Product</th>
          <th class="p-2 text-left">Google Product</th>
          <th class="p-2 text-left">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="t in items" :key="t.id" class="border-t">
          <td class="p-2">{{ t.id }}</td>
          <td class="p-2">{{ t.name }}</td>
          <td class="p-2">{{ t.price }}</td>
          <td class="p-2">{{ t.currency }}</td>
          <td class="p-2">{{ t.apple_product_id }}</td>
          <td class="p-2">{{ t.google_product_id }}</td>
          <td class="p-2 flex gap-2">
            <button @click="goEdit(t.id)" class="px-2 py-1 bg-gray-200 rounded">Edit</button>
            <button @click="remove(t.id)" class="px-2 py-1 bg-red-600 text-white rounded">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="mt-4 flex gap-2">
      <button @click="prev()" class="px-3 py-1 border rounded">Prev</button>
      <div>Page {{ meta.current_page }} / {{ meta.last_page }}</div>
      <button @click="next()" class="px-3 py-1 border rounded">Next</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

const filters = ref({ q: '', currency: '' })
const items = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
let page = 1

const load = async () => {
  const { data } = await axios.get(`/admin-api/tariffs`, { params: { ...filters.value, page } })
  items.value = data.data
  meta.value = data.meta
}

const next = () => { if (page < meta.value.last_page) { page++; load() } }
const prev = () => { if (page > 1) { page--; load() } }
const goEdit = (id) => router.visit(`/admin/tariffs/${id}/edit`)
const remove = async (id) => { await axios.delete(`/admin-api/tariffs/${id}`); load() }
const create = async () => {
  const { data } = await axios.post(`/admin-api/tariffs`, { name: 'New Tariff', currency: 'USD', price: 0 })
  router.visit(`/admin/tariffs/${data.id}/edit`)
}

onMounted(load)
</script>

<style scoped>
</style>
