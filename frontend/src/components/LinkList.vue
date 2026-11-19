<script setup>
import { onMounted, ref, watch } from 'vue'
import { api } from '@/services/api'
import CopyButton from '@/components/CopyButton.vue'
import LinkStatus from '@/components/LinkStatus.vue'

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

// Cuando se pide recargar (por ejemplo, luego de crear un enlace) se vuelve a la página 1
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
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="l in result.data" :key="l.id">
            <td class="short">
              <a :href="l.short_url" target="_blank" rel="noopener">{{
                shortLabel(l.short_url)
              }}</a>
              <CopyButton :text="l.short_url" />
            </td>
            <td class="destination" :title="l.original_url">{{ l.original_url }}</td>
            <td class="num">{{ l.total_clicks }}</td>
            <td><LinkStatus :link="l" /></td>
            <td class="actions">
              <RouterLink :to="{ name: 'link-detail', params: { id: l.id } }">
                Estadísticas
              </RouterLink>
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
