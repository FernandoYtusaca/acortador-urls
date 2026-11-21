<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { api } from '@/services/api'

const props = defineProps({
  refreshKey: { type: Number, default: 0 },
})

const summary = ref(null)

const ICONS = {
  link: '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
  check: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
  chart:
    '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
  trend: '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
}

const cards = computed(() => [
  { label: 'Enlaces creados', value: summary.value?.total_links, icon: 'link', tone: 'teal' },
  { label: 'Enlaces activos', value: summary.value?.active_links, icon: 'check', tone: 'green' },
  { label: 'Clics totales', value: summary.value?.total_clicks, icon: 'chart', tone: 'violet' },
  {
    label: 'Clics en 7 días',
    value: summary.value?.clicks_last_7_days,
    icon: 'trend',
    tone: 'amber',
  },
])

function format(value) {
  return value === undefined ? '—' : value.toLocaleString('es')
}

async function load() {
  try {
    summary.value = await api.get('/links/summary')
  } catch {
    summary.value = null
  }
}

onMounted(load)
watch(() => props.refreshKey, load)
</script>

<template>
  <section class="stats">
    <article v-for="c in cards" :key="c.label" class="stat-card">
      <span class="stat-icon" :class="c.tone">
        <svg
          viewBox="0 0 24 24"
          width="22"
          height="22"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
          v-html="ICONS[c.icon]"
        />
      </span>
      <div>
        <div class="stat-value">{{ format(c.value) }}</div>
        <div class="stat-label">{{ c.label }}</div>
      </div>
    </article>
  </section>
</template>
