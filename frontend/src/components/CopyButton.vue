<script setup>
import { ref } from 'vue'

const props = defineProps({
  text: { type: String, required: true },
})

const copied = ref(false)
let timer = null

async function copy() {
  try {
    await navigator.clipboard.writeText(props.text)
    copied.value = true
    clearTimeout(timer)
    timer = setTimeout(() => (copied.value = false), 1500)
  } catch {
    window.prompt('Copia el enlace:', props.text)
  }
}
</script>

<template>
  <button type="button" class="link" @click="copy">
    {{ copied ? '¡Copiado!' : 'Copiar' }}
  </button>
</template>
