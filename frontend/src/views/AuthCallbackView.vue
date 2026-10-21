<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppShell from '../components/AppShell.vue'
import api from '../services/api'
import { useAuth } from '../composables/useAuth'

const route = useRoute()
const router = useRouter()
const { setUser } = useAuth()

onMounted(async () => {
  const token = route.query.token
  const error = route.query.error

  if (token && typeof token === 'string') {
    localStorage.setItem('token', token)
    try {
      const { data } = await api.get('/me')
      setUser(data.user)
      const role = data.user?.role
      router.replace({ name: role === 'admin' ? 'dashboard' : 'blog' })
    } catch {
      router.replace({ name: 'blog' })
    }
  } else {
    router.replace({ name: 'login', query: error ? { error: String(error) } : {} })
  }
})
</script>

<template>
  <AppShell>
    <div class="auth-wrap">
      <div class="auth-card" style="text-align: center">
        <p class="auth-logo">Zaskia.</p>
        <p class="auth-subtitle">Finishing your sign-in…</p>
      </div>
    </div>
  </AppShell>
</template>
