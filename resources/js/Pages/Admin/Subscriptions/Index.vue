<template>
  <div class="p-6">
    <h1 class="text-2xl font-semibold mb-4">Subscriptions</h1>
    <div class="flex gap-2 mb-4">
      <input v-model="filters.phone" placeholder="Phone" class="border p-2 rounded" />
      <select v-model="filters.platform" class="border p-2 rounded">
        <option value="">All platforms</option>
        <option value="apple">Apple</option>
        <option value="google">Google</option>
      </select>
      <select v-model="filters.status" class="border p-2 rounded">
        <option value="">All statuses</option>
        <option value="active">Active</option>
        <option value="expired">Expired</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <button @click="load()" class="bg-blue-600 text-white px-4 py-2 rounded">Search</button>
      <button @click="exportCsv()" class="bg-gray-600 text-white px-4 py-2 rounded">Export CSV</button>
    </div>

    <table class="w-full border text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="p-2 text-left">ID</th>
          <th class="p-2 text-left">User</th>
          <th class="p-2 text-left">Phone</th>
          <th class="p-2 text-left">Platform</th>
          <th class="p-2 text-left">Product</th>
          <th class="p-2 text-left">External ID</th>
          <th class="p-2 text-left">Status</th>
          <th class="p-2 text-left">Expires</th>
          <th class="p-2 text-left">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="s in items" :key="s.id" class="border-t">
          <td class="p-2">{{ s.id }}</td>
          <td class="p-2">{{ s.user_id }}</td>
          <td class="p-2">{{ s.user_phone }}</td>
          <td class="p-2">{{ s.platform }}</td>
          <td class="p-2">{{ s.product_id }}</td>
          <td class="p-2 truncate max-w-[240px]">{{ s.external_id }}</td>
          <td class="p-2">{{ s.status }}</td>
          <td class="p-2">{{ s.expires_at }}</td>
          <td class="p-2 flex gap-2">
            <button @click="goEdit(s.id)" class="px-2 py-1 bg-gray-200 rounded">Edit</button>
            <button @click="remove(s.id)" class="px-2 py-1 bg-red-600 text-white rounded">Delete</button>
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

const filters = ref({ phone: '', platform: '', status: '' })
const items = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
let page = 1

const load = async () => {
  const { data } = await axios.get(`/admin-api/subscriptions`, { params: { ...filters.value, page } })
  items.value = data.data
  meta.value = data.meta
}

const next = () => { if (page < meta.value.last_page) { page++; load() } }
const prev = () => { if (page > 1) { page--; load() } }
const goEdit = (id) => router.visit(`/admin/subscriptions/${id}/edit`)
const remove = async (id) => { await axios.delete(`/admin-api/subscriptions/${id}`); load() }
const exportCsv = () => { window.location.href = `/admin-api/subscriptions/export?` + new URLSearchParams(filters.value).toString() }

onMounted(load)
</script>

<style scoped>
</style>
