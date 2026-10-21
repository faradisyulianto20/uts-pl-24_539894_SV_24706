<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowUpRight } from '@lucide/vue'
import ThemeToggle from './ThemeToggle.vue'
import api from '../services/api'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { user, fetchMe, clearUser } = useAuth()

const loggedIn = computed(() => !!localStorage.getItem('token'))

onMounted(async () => {
  if (loggedIn.value) {
    try {
      await fetchMe()
    } catch {
      /* token expired — header tetap tampil */
    }
  }
})

async function handleLogout() {
  try {
    await fetchMe()
    await api.post('/logout')
  } catch {
    /* token mungkin sudah tidak berlaku */
  } finally {
    clearUser()
    localStorage.removeItem('token')
    router.push({ name: 'login' })
  }
}
</script>

<template>
  <div class="app-shell">
    <header class="site-header">
      <div class="header-inner">
        <router-link to="/" class="logo">Zaskia.</router-link>
        <div class="header-right">
          <span class="header-tagline">A Personal Journal</span>
          <ThemeToggle />
          <router-link v-if="!loggedIn" to="/login" class="btn btn-outline btn-sm">Login</router-link>
          <template v-else>
            <router-link
              v-if="user?.role === 'admin'"
              to="/dashboard"
              class="btn btn-outline btn-sm"
            >
              <ArrowUpRight :size="16" />
              The Writing Room
            </router-link>
            <button v-else type="button" class="btn btn-outline btn-sm" @click="handleLogout">
              Logout
            </button>
          </template>
        </div>
      </div>
    </header>

    <main class="site-main">
      <slot />
    </main>

    <footer class="site-footer">
      <div class="footer-inner">
        <span class="copyright">© 2026 Zaskia. All words, thoughtfully kept.</span>
        <span class="footer-quote">A little more attention. A little less hurry.</span>
      </div>
    </footer>
  </div>
</template>
