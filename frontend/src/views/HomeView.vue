<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import AppShell from '../components/AppShell.vue'

const router = useRouter()
const user = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await api.get('/me')
    user.value = data.user
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat profil.'
  }
})

async function handleLogout() {
  try {
    await api.post('/logout')
  } catch {
    // token mungkin sudah tidak berlaku
  } finally {
    localStorage.removeItem('token')
    router.push({ name: 'login' })
  }
}
</script>

<template>
  <AppShell>
    <div class="login-card">
      <p class="profile-eyebrow">Your Account</p>
      <h1 class="profile-title">Welcome back</h1>

      <div v-if="error" class="alert">{{ error }}</div>

      <template v-else-if="user">
        <div class="profile-list">
          <div class="profile-row">
            <span class="key">Name</span>
            <span class="value">{{ user.name }}</span>
          </div>
          <div class="profile-row">
            <span class="key">Email</span>
            <span class="value">{{ user.email }}</span>
          </div>
          <div class="profile-row">
            <span class="key">Role</span>
            <span class="value">{{ user.role }}</span>
          </div>
        </div>

        <button type="button" class="btn btn-logout" @click="handleLogout">Logout</button>
      </template>

      <p v-else style="text-align: center; color: var(--text-secondary)">Memuat...</p>
    </div>
  </AppShell>
</template>
