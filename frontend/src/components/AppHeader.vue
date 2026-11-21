<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const initials = computed(() =>
  (auth.user?.name || '')
    .split(' ')
    .filter(Boolean)
    .map((word) => word[0])
    .slice(0, 2)
    .join('')
    .toUpperCase(),
)

onMounted(() => {
  if (!auth.user) auth.fetchUser()
})

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <header class="topbar">
    <RouterLink to="/" class="brand">
      <span class="brand-mark">
        <svg
          viewBox="0 0 24 24"
          width="20"
          height="20"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
          <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
        </svg>
      </span>
      <h1 class="brand-name">Acortador de URLs</h1>
    </RouterLink>

    <div class="user">
      <template v-if="auth.user">
        <span class="avatar">{{ initials }}</span>
        <span class="user-name">{{ auth.user.name }}</span>
      </template>
      <button class="secondary" @click="logout">Salir</button>
    </div>
  </header>
</template>
