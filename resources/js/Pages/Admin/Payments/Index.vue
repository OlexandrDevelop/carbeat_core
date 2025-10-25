<template>
  <div class="p-6">
    <h1 class="text-2xl font-semibold mb-4">Payment Settings</h1>

    <div class="grid md:grid-cols-2 gap-6">
      <section class="border rounded p-4">
        <h2 class="font-semibold mb-2">Apple</h2>
        <div class="space-y-2">
          <input v-model="form.apple.issuer_id" placeholder="Issuer ID" class="border p-2 rounded w-full" />
          <input v-model="form.apple.key_id" placeholder="Key ID" class="border p-2 rounded w-full" />
          <textarea v-model="form.apple.private_key" placeholder="Private Key (PKCS8)" rows="4" class="border p-2 rounded w-full"></textarea>
          <input v-model="form.apple.bundle_id" placeholder="Bundle ID" class="border p-2 rounded w-full" />
          <label class="inline-flex items-center gap-2">
            <input type="checkbox" v-model="form.apple.use_sandbox" />
            <span>Use Sandbox</span>
          </label>
        </div>
      </section>

      <section class="border rounded p-4">
        <h2 class="font-semibold mb-2">Google</h2>
        <div class="space-y-2">
          <textarea v-model="form.google.service_account_json" placeholder="Service Account JSON" rows="6" class="border p-2 rounded w-full"></textarea>
          <input v-model="form.google.package_name" placeholder="Package Name" class="border p-2 rounded w-full" />
        </div>
      </section>
    </div>

    <div class="mt-4">
      <button @click="save()" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const form = ref({ apple: { issuer_id: '', key_id: '', private_key: '', bundle_id: '', use_sandbox: true }, google: { service_account_json: '', package_name: '' } })

const load = async () => {
  const { data } = await axios.get('/admin-api/payment-settings')
  form.value = Object.assign(form.value, data)
}

const save = async () => {
  await axios.put('/admin-api/payment-settings', form.value)
}

onMounted(load)
</script>

<style scoped>
</style>
