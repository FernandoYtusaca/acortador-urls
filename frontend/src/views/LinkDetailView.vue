<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/services/api'
import AppHeader from '@/components/AppHeader.vue'
import ClicksChart from '@/components/ClicksChart.vue'
import CopyButton from '@/components/CopyButton.vue'
import LinkEditForm from '@/components/LinkEditForm.vue'
import LinkStatus from '@/components/LinkStatus.vue'
import { formatDateTime } from '@/utils/format'

const route = useRoute()
const router = useRouter()
const id = route.params.id

const link = ref(null)
const stats = ref(null)
const days = ref(30)
const loading = ref(true)
const error = ref('')
const editing = ref(false)
const actionError = ref('')

const shortLabel = computed(() => link.value?.short_url.replace(/^https?:\/\//, ''))

async function loadLink() {
  try {
    link.value = await api.get(`/links/${id}`)
  } catch (e) {
    error.value = e.status === 404 ? 'No se encontró el enlace.' : 'No se pudo cargar el enlace.'
  }
}

async function loadStats() {
  try {
    stats.value = await api.get(`/links/${id}/stats`, { days: days.value })
  } catch {
    error.value = 'No se pudieron cargar las estadísticas.'
  }
}

function onSaved(updated) {
  link.value = updated
  editing.value = false
}

async function remove() {
  if (!confirm('¿Eliminar este enlace? También se perderán sus estadísticas de clics.')) return

  actionError.value = ''
  try {
    await api.delete(`/links/${id}`)
    router.push({ name: 'links' })
  } catch {
    actionError.value = 'No se pudo eliminar el enlace.'
  }
}

onMounted(async () => {
  await loadLink()
  if (link.value) await loadStats()
  loading.value = false
})

watch(days, loadStats)
</script>

<template>
  <main class="page">
    <AppHeader />

    <RouterLink to="/" class="back">← Volver a mis enlaces</RouterLink>

    <p v-if="error" class="error">{{ error }}</p>
    <p v-else-if="loading" class="muted">Cargando...</p>

    <template v-else-if="link">
      <section class="panel">
        <div class="panel-head">
          <h2>{{ shortLabel }}</h2>
          <div class="actions-row">
            <button class="secondary" @click="editing = true">Editar</button>
            <button class="danger" @click="remove">Eliminar</button>
          </div>
        </div>

        <p v-if="actionError" class="error">{{ actionError }}</p>

        <dl class="details">
          <dt>Enlace corto</dt>
          <dd>
            <a :href="link.short_url" target="_blank" rel="noopener">{{ link.short_url }}</a>
            <CopyButton :text="link.short_url" />
          </dd>

          <dt>Destino</dt>
          <dd class="break">
            <a :href="link.original_url" target="_blank" rel="noopener">{{ link.original_url }}</a>
          </dd>

          <dt>Creado</dt>
          <dd>{{ formatDateTime(link.created_at) }}</dd>

          <dt>Estado</dt>
          <dd><LinkStatus :link="link" /></dd>

          <dt>Clics totales</dt>
          <dd>{{ link.total_clicks }}</dd>
        </dl>
      </section>

      <LinkEditForm v-if="editing" :link="link" @saved="onSaved" @cancel="editing = false" />

      <section v-if="stats" class="panel">
        <div class="panel-head">
          <h2>Clics por día</h2>
          <select v-model.number="days">
            <option :value="7">Últimos 7 días</option>
            <option :value="30">Últimos 30 días</option>
            <option :value="90">Últimos 90 días</option>
          </select>
        </div>

        <p class="muted">
          {{ stats.clicks_in_period }} clics en los últimos {{ stats.period_days }} días
        </p>
        <ClicksChart :data="stats.clicks_by_day" />
      </section>

      <section v-if="stats" class="panel">
        <h2>Principales orígenes</h2>

        <p v-if="!stats.top_referers.length" class="muted">Aún no hay clics con origen conocido.</p>

        <ul v-else class="stat-list">
          <li v-for="r in stats.top_referers" :key="r.referer">
            <span class="break">{{ r.referer }}</span>
            <strong>{{ r.total }}</strong>
          </li>
        </ul>
      </section>
    </template>
  </main>
</template>
