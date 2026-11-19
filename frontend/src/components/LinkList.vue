<script setup>
import { onMounted, ref, watch } from 'vue'
import { api } from '@/services/api'
import { formatDate } from '@/utils/format'

const props = defineProps({
  refreshKey: { type: Number, default: 0 },
})

const page = ref(1)
const result = ref(null)
const loading = ref(false)
const error = ref('')

async function loadLinks() {
  loading.value = true
  error.value = ''
  try {
    result.value = await api.get('/links', { page: page.value })
  } catch {
    error.value = 'No se pudieron cargar los enlaces.'
  } finally {
    loading.value = false
  }
}

function shortLabel(url) {
  return url.replace(/^https?:\/\//, '')
}

onMounted(loadLinks)

watch(page, loadLinks)

// Cuando se pide recargar (por ejemplo, Luego de crear un enlace) se vuelve a la página 1
watch(
  () => props.refreshKey,
  () => {
    if (page.value !== 1) page.value = 1
    else loadLinks()
  },
)
</script>

<template>
  <section class="panel">
    <h2>Mis enlaces</h2>

    <p v-if="error" class="error">{{ error }}</p>
    <p v-else-if="loading && !result" class="muted">Cargando...</p>
    <p v-else-if="result && !result.data.length" class="muted">Aún no tienes enlaces.</p>

    <div v-else-if="result" class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Enlace corto</th>
            <th>Destino</th>
            <th class="num">Clics</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="l in result.data" :key="l.id">
            <td>
              <a :href="l.short_url" target="_blank" rel="noopener">{{
                shortLabel(l.short_url)
              }}</a>
            </td>
            <td class="destination" :title="l.original_url">{{ l.original_url }}</td>
            <td class="num">{{ l.total_clicks }}</td>
            <td>
              <span v-if="l.is_expired" class="badge badge-danger">Expirado</span>
              <span v-else-if="l.expires_at" class="badge badge-warn">
                Expira {{ formatDate(l.expires_at) }}
              </span>
              <span v-else class="badge">Sin expiración</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <nav v-if="result && result.last_page > 1" class="pagination">
      <button class="secondary" :disabled="page <= 1" @click="page--">Anterior</button>
      <span>Página {{ result.current_page }} de {{ result.last_page }}</span>
      <button class="secondary" :disabled="page >= result.last_page" @click="page++">
        Siguiente
      </button>
    </nav>
  </section>
</template>
