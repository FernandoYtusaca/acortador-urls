<script setup>
import { computed, ref } from 'vue'
import { hostOf } from '@/utils/format'

const props = defineProps({
  url: { type: String, required: true },
})

const COLORS = ['#007f7d', '#6d28d9', '#b45309', '#be185d', '#1d4ed8', '#15803d']

const failed = ref(false)

const host = computed(() => hostOf(props.url))
const letter = computed(() => (host.value[0] || '?').toUpperCase())
const showImage = computed(() => host.value && !failed.value)
const favicon = computed(() => `https://www.google.com/s2/favicons?domain=${host.value}&sz=64`)

// Mismo dominio, mismo color: se deriva un índice del texto
const color = computed(() => {
  let hash = 0
  for (const ch of host.value) hash = (hash * 31 + ch.charCodeAt(0)) >>> 0
  return COLORS[hash % COLORS.length]
})
</script>

<template>
  <span
    class="link-avatar"
    :class="{ 'has-image': showImage }"
    :style="showImage ? {} : { background: color }"
  >
    <img v-if="showImage" :src="favicon" alt="" loading="lazy" @error="failed = true" />
    <template v-else>{{ letter }}</template>
  </span>
</template>
