<script setup>
import { onMounted, ref, watch } from 'vue'
import { api } from '@/services/api'
import CopyButton from '@/components/CopyButton.vue'
import LinkStatus from '@/components/LinkStatus.vue'
import { formatDate, formatDateTime } from '@/utils/format'

const props = defineProps({
  refreshKey: { type: Number, default: 0 },
})

const PILLS = [
  { value: 'all', label: 'Todos' },
  { value: 'active', label: 'Activos' },
  { value: 'expired', label: 'Expirados' },
]

const status = ref('all')
const sort = ref('created_at')
const direction = ref('desc')
const page = ref(1)
const expandedId = ref(null)

const result = ref(null)
const loading = ref(false)
const error = ref('')

async function loadLinks() {
  loading.value = true
  error.value = ''
  try {
    result.value = await api.get('/links', {
      page: page.value,
      status: status.value === 'all' ? '' : status.value,
      sort: sort.value,
      direction: direction.value,
    })
    expandedId.value = null
  } catch {
    error.value = 'No se pudieron cargar los enlaces.'
  } finally {
    loading.value = false
  }
}

function sortBy(field) {
  if (sort.value === field) {
    direction.value = direction.value === 'desc' ? 'asc' : 'desc'
  } else {
    sort.value = field
    direction.value = 'desc'
  }
}

function toggle(id) {
  expandedId.value = expandedId.value === id ? null : id
}

function shortLabel(url) {
  return url.replace(/^https?:\/\//, '')
}

onMounted(loadLinks)

// Al cambiar el filtro o el orden se vuelve a la página 1
watch([status, sort, direction], () => {
  if (page.value !== 1) page.value = 1
  else loadLinks()
})

watch(page, loadLinks)

// Cuando el padre pide recargar (por ejemplo, tras crear un enlace) se vuelve a la página 1
watch(
  () => props.refreshKey,
  () => {
    if (page.value !== 1) page.value = 1
    else loadLinks()
  },
)
</script>

<template>
  <section>
    <div class="list-head">
      <h2 class="list-title">Mis enlaces</h2>

      <div class="pills">
        <button
          v-for="p in PILLS"
          :key="p.value"
          type="button"
          class="pill"
          :class="{ active: status === p.value }"
          @click="status = p.value"
        >
          {{ p.label }}
        </button>
      </div>
    </div>

    <div class="panel">
      <p v-if="error" class="error">{{ error }}</p>
      <p v-else-if="loading && !result" class="muted">Cargando...</p>
      <p v-else-if="result && !result.data.length" class="muted">
        {{ status === 'all' ? 'Aún no tienes enlaces.' : 'No hay enlaces con este filtro.' }}
      </p>

      <div v-else-if="result" class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th></th>
              <th>Enlace corto</th>
              <th>
                <button type="button" class="sort" @click="sortBy('total_clicks')">
                  Clics
                  <span v-if="sort === 'total_clicks'">{{ direction === 'desc' ? '↓' : '↑' }}</span>
                </button>
              </th>
              <th>
                <button type="button" class="sort" @click="sortBy('created_at')">
                  Creado
                  <span v-if="sort === 'created_at'">{{ direction === 'desc' ? '↓' : '↑' }}</span>
                </button>
              </th>
              <th>Estado</th>
            </tr>
          </thead>

          <tbody>
            <template v-for="l in result.data" :key="l.id">
              <tr class="row-main" :class="{ open: expandedId === l.id }" @click="toggle(l.id)">
                <td class="cell-toggle">
                  <button
                    type="button"
                    class="row-toggle"
                    :aria-expanded="expandedId === l.id"
                    aria-label="Ver detalles"
                  >
                    <svg class="chevron" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true">
                      <path
                        d="M5 8l5 5 5-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      />
                    </svg>
                  </button>
                </td>
                <td class="strong">
                  <a :href="l.short_url" target="_blank" rel="noopener" @click.stop>
                    {{ shortLabel(l.short_url) }}
                  </a>
                </td>
                <td>{{ l.total_clicks }}</td>
                <td>{{ formatDate(l.created_at) }}</td>
                <td><LinkStatus :link="l" /></td>
              </tr>

              <tr v-if="expandedId === l.id" class="row-detail">
                <td colspan="5">
                  <dl class="fields">
                    <div class="field">
                      <dt>Destino</dt>
                      <dd class="break">{{ l.original_url }}</dd>
                    </div>
                    <div class="field">
                      <dt>Enlace corto</dt>
                      <dd class="break">
                        {{ l.short_url }}
                        <CopyButton :text="l.short_url" />
                      </dd>
                    </div>
                    <div class="field">
                      <dt>Expira</dt>
                      <dd>{{ l.expires_at ? formatDateTime(l.expires_at) : 'Nunca' }}</dd>
                    </div>
                  </dl>

                  <RouterLink class="btn" :to="{ name: 'link-detail', params: { id: l.id } }">
                    Ver estadísticas
                  </RouterLink>
                </td>
              </tr>
            </template>
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
    </div>
  </section>
</template>
