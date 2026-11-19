<script setup>
import { formatDate } from '@/utils/format'

defineProps({
  link: { type: Object, required: true },
})
</script>

<template>
  <span v-if="link.is_expired" class="badge badge-danger">Expirado</span>
  <span v-else-if="link.expires_at" class="badge badge-warn">
    Expira {{ formatDate(link.expires_at) }}
  </span>
  <span v-else class="badge">Sin expiración</span>
</template>
