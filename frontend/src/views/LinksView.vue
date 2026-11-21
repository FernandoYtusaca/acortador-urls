<script setup>
import { ref } from 'vue'
import AppHeader from '@/components/AppHeader.vue'
import CopyButton from '@/components/CopyButton.vue'
import LinkForm from '@/components/LinkForm.vue'
import LinkList from '@/components/LinkList.vue'
import StatCards from '@/components/StatCards.vue'

const refreshKey = ref(0)
const created = ref(null)

function onCreated(link) {
  created.value = link
  refreshKey.value++
}
</script>

<template>
  <main class="page">
    <AppHeader />

    <StatCards :refresh-key="refreshKey" />

    <LinkForm @created="onCreated" />

    <p v-if="created" class="success">
      ¡Enlace creado!
      <a :href="created.short_url" target="_blank" rel="noopener">{{ created.short_url }}</a>
      <CopyButton :text="created.short_url" />
    </p>

    <LinkList :refresh-key="refreshKey" />
  </main>
</template>
