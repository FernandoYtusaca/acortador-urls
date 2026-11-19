<script setup>
import { reactive, ref } from 'vue'
import { api } from '@/services/api'

const emit = defineEmits(['created'])

const form = reactive({ original_url: '', alias: '', expires_at: '' })
const errors = ref({})
const message = ref('')
const loading = ref(false)

async function submit() {
  errors.value = {}
  message.value = ''
  loading.value = true

  const body = { original_url: form.original_url }
  if (form.alias) body.alias = form.alias.trim().toLowerCase()
  if (form.expires_at) body.expires_at = new Date(form.expires_at).toISOString()

  try {
    const link = await api.post('/links', body)
    form.original_url = ''
    form.alias = ''
    form.expires_at = ''
    emit('created', link)
  } catch (e) {
    if (e.status === 422) errors.value = e.errors
    else message.value = 'No se pudo crear el enlace.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="panel link-form" @submit.prevent="submit">
    <h2>Acortar un enlace</h2>

    <label>
      URL de destino
      <input v-model="form.original_url" type="url" required placeholder="https://..." />
      <small v-if="errors.original_url" class="error">{{ errors.original_url[0] }}</small>
    </label>

    <div class="link-form-row">
      <label>
        Alias (opcional)
        <input v-model="form.alias" type="text" maxlength="32" placeholder="mi-enlace" />
        <small v-if="errors.alias" class="error">{{ errors.alias[0] }}</small>
      </label>

      <label>
        Expira (opcional)
        <input v-model="form.expires_at" type="datetime-local" />
        <small v-if="errors.expires_at" class="error">{{ errors.expires_at[0] }}</small>
      </label>
    </div>

    <p v-if="message" class="error">{{ message }}</p>

    <button :disabled="loading">{{ loading ? 'Creando...' : 'Acortar' }}</button>
  </form>
</template>
