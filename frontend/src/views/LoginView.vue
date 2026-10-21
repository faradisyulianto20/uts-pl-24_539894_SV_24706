<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'
import AppShell from '../components/AppShell.vue'

const route = useRoute()
const router = useRouter()

const apiBase = (import.meta.env.VITE_API_BASE || '/api').replace(/\/$/, '')
const googleAuthUrl = `${apiBase}/auth/google`

const GOOGLE_ERRORS = {
  google_failed: 'Login Google gagal atau dibatalkan. Silakan coba lagi.',
  google_not_configured:
    'Login Google belum dikonfigurasi di server. Gunakan email dan password sebagai gantinya.',
}

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const error = ref('')
const loading = ref(false)

const initialError = route.query.error
if (initialError && typeof initialError === 'string') {
  error.value = GOOGLE_ERRORS[initialError] || 'Terjadi kesalahan saat login Google.'
}

async function handleLogin() {
  error.value = ''
  loading.value = true

  try {
    const { data } = await api.post('/login', {
      email: email.value,
      password: password.value,
    })

    localStorage.setItem('token', data.token)
    router.push({ name: 'home' })
  } catch (e) {
    if (e.response?.status === 422) {
      const errors = e.response.data.errors
      error.value = Object.values(errors).flat().join(' ')
    } else {
      error.value = e.response?.data?.message || 'Terjadi kesalahan. Coba lagi.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AppShell>
    <div>
      <div class="login-card">
        <p class="login-eyebrow">Your Quiet Corner</p>
        <h1 class="login-title">Welcome Back</h1>
        <p class="login-subtitle">
          A space for your words, and the stories still waiting to be told.
        </p>

        <form class="login-form" @submit.prevent="handleLogin">
          <div v-if="error" class="alert">{{ error }}</div>

          <div class="field">
            <label for="email">Email</label>
            <div class="input-wrap">
              <input
                id="email"
                v-model="email"
                type="email"
                required
                placeholder="you@example.com"
                autocomplete="email"
              />
            </div>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap has-icon">
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="Enter your password"
                autocomplete="current-password"
              />
              <button
                type="button"
                class="input-icon-btn"
                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                @click="showPassword = !showPassword"
              >
                <svg
                  v-if="!showPassword"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                  <path
                    d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"
                  />
                  <path
                    d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"
                  />
                  <line x1="2" x2="22" y1="2" y2="22" />
                </svg>
                <svg
                  v-else
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </button>
            </div>
          </div>

          <a href="#" class="forgot-link" @click.prevent>Forgot password?</a>

          <button type="submit" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Memproses...' : 'Login' }}
          </button>

          <div class="divider"><span>or</span></div>

          <a
            class="btn btn-google"
            :href="googleAuthUrl"
          >
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path
                d="M21.35 11.1h-9.17v2.73h6.51c-.33 3.81-3.5 5.44-6.5 5.44C8.36 19.27 5 16.25 5 12c0-4.1 3.2-7.27 7.2-7.27 3.09 0 4.9 1.97 4.9 1.97L19 4.72S16.56 2 12.1 2C6.42 2 2.03 6.8 2.03 12c0 5.05 4.13 10 10.22 10 5.35 0 9.25-3.67 9.25-9.09 0-1.15-.15-1.81-.15-1.81Z"
              />
            </svg>
            Continue with Google
          </a>
        </form>
      </div>

      <div style="text-align: center">
        <router-link to="/" class="back-link">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M19 12H5" />
            <path d="m12 19-7-7 7-7" />
          </svg>
          Back to the journal
        </router-link>
      </div>
    </div>
  </AppShell>
</template>
