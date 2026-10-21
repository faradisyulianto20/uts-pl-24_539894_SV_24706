<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'

const route = useRoute()
const router = useRouter()

onMounted(() => {
  const token = route.query.token
  const error = route.query.error

  if (token && typeof token === 'string') {
    localStorage.setItem('token', token)
    router.replace({ name: 'home' })
  } else {
    router.replace({ name: 'login', query: error ? { error: String(error) } : {} })
  }
})
</script>

<template>
  <AppShell>
    <div class="callback-card">
      <div class="spinner" aria-hidden="true"></div>
      <p style="color: var(--text-secondary)">Memproses login Google...</p>
    </div>
  </AppShell>
</template>
