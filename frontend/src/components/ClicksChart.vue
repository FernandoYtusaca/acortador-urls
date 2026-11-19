<script setup>
import { computed } from 'vue'
import { formatDay, formatDayShort } from '@/utils/format'

const props = defineProps({
  data: { type: Array, required: true },
})

const WIDTH = 600
const HEIGHT = 200
const PADDING = { top: 10, right: 10, bottom: 24, left: 32 }

const plotWidth = WIDTH - PADDING.left - PADDING.right
const plotHeight = HEIGHT - PADDING.top - PADDING.bottom
const baseline = PADDING.top + plotHeight

// Escala vertical: el valor máximo ocupa toda la altura (mínimo 1, para no dividir entre 0)
const max = computed(() => Math.max(1, ...props.data.map((d) => d.clicks)))

const step = computed(() => plotWidth / props.data.length)
const barWidth = computed(() => Math.max(step.value * 0.7, 1))

const bars = computed(() =>
  props.data.map((d, i) => {
    const height = (d.clicks / max.value) * plotHeight

    return {
      key: d.date,
      x: PADDING.left + i * step.value + (step.value - barWidth.value) / 2,
      y: baseline - height,
      height,
      label: `${formatDay(d.date)}: ${d.clicks} ${d.clicks === 1 ? 'clic' : 'clics'}`,
    }
  }),
)

// Solo se rotulan el primer día, el del medio y el último
const xLabels = computed(() => {
  const last = props.data.length - 1
  const indexes = [...new Set([0, Math.floor(last / 2), last])]

  return indexes.map((i) => ({
    x: PADDING.left + i * step.value + step.value / 2,
    text: formatDayShort(props.data[i].date),
  }))
})
</script>

<template>
  <svg :viewBox="`0 0 ${WIDTH} ${HEIGHT}`" class="chart" role="img" aria-label="Clics por día">
    <line
      :x1="PADDING.left"
      :x2="WIDTH - PADDING.right"
      :y1="baseline"
      :y2="baseline"
      class="chart-axis"
    />

    <text :x="PADDING.left - 6" :y="PADDING.top + 4" class="chart-label" text-anchor="end">
      {{ max }}
    </text>
    <text :x="PADDING.left - 6" :y="baseline" class="chart-label" text-anchor="end">0</text>

    <rect
      v-for="b in bars"
      :key="b.key"
      :x="b.x"
      :y="b.y"
      :width="barWidth"
      :height="b.height"
      rx="2"
      class="chart-bar"
    >
      <title>{{ b.label }}</title>
    </rect>

    <text
      v-for="l in xLabels"
      :key="l.x"
      :x="l.x"
      :y="HEIGHT - 6"
      class="chart-label"
      text-anchor="middle"
    >
      {{ l.text }}
    </text>
  </svg>
</template>
