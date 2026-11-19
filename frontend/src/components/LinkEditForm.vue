<script setup>
import { reactive, ref } from 'vue'
import { api } from '@/services/api'
import { toDatetimeLocal } from '@/utils/format'

const props = defineProps({
  link: { type: Object, required: true },
})
const emit = defineEmits(['saved', 'cancel'])

const initial = {
  original_url: props.link.original_url,
  expires_at: props.link.expires_at ? toDatetimeLocal(props.link.expires_at) : '',
}

const form = reactive({ ...initial })
const errors = ref({})
const message = ref('')
const loading = ref(false)

async function submit() {
  errors.value = {}
  message.value = ''

  // Solo se envía lo que cambió. Si el enlace ya expiró y no se toca la fecha,
  // reenviarla haría fallar la regla "la fecha debe ser futura".
  const body = {}
  if (form.original_url !== initial.original_url) body.original_url = form.original_url
  if (form.expires_at !== initial.expires_at) {
    body.expires_at = form.expires_at ? new Date(form.expires_at).toISOString() : null
  }

  if (!Object.keys(body).length) {
    emit('cancel')
    return
  }

  loading.value = true
  try {
    emit('saved', await api.put(`/links/${props.link.id}`, body))
  } catch (e) {
    if (e.status === 422) errors.value = e.errors
    else message.value = 'No se pudo guardar el enlace.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="panel link-form" @submit.prevent="submit">
    <h2>Editar enlace</h2>

    <label>
      URL de destino
      <input v-model="form.original_url" type="url" required />
      <small v-if="errors.original_url" class="error">{{ errors.original_url[0] }}</small>
    </label>

    <label>
      Expira
      <input v-model="form.expires_at" type="datetime-local" />
      <small class="muted">
        Déjalo vacío para que no expire. Si el enlace ya expiró, elige una fecha futura para
        reactivarlo.
      </small>
      <small v-if="errors.expires_at" class="error">{{ errors.expires_at[0] }}</small>
    </label>

    <div>
      <button v-if="form.expires_at" type="button" class="link" @click="form.expires_at = ''">
        Quitar expiración
      </button>
    </div>

    <p v-if="message" class="error">{{ message }}</p>

    <div class="form-actions">
      <button :disabled="loading">{{ loading ? 'Guardando...' : 'Guardar' }}</button>
      <button type="button" class="secondary" @click="emit('cancel')">Cancelar</button>
    </div>
  </form>
</template>
