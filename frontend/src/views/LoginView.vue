<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Eye, EyeOff } from '@lucide/vue'
import api from '../services/api'
import AppShell from '../components/AppShell.vue'
import { useAuth } from '../composables/useAuth'

const route = useRoute()
const router = useRouter()
const { setUser } = useAuth()

const apiBase = (import.meta.env.VITE_API_BASE || '/api').replace(/\/$/, '')
const googleAuthUrl = `${apiBase}/auth/google`

const GOOGLE_ERRORS = {
  google_failed: 'Google login failed or was cancelled. Please try again.',
  google_not_configured:
    'Google login is not configured on the server yet. Please use email and password instead.',
}

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const error = ref('')
const loading = ref(false)

const initialError = route.query.error
if (initialError && typeof initialError === 'string') {
  error.value = GOOGLE_ERRORS[initialError] || 'Something went wrong with Google login.'
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
    if (data.user) setUser(data.user)
    router.push({ name: 'blog' })
  } catch (e) {
    if (e.response?.status === 422) {
      const errors = e.response.data.errors
      error.value = Object.values(errors).flat().join(' ')
    } else if (e.response?.status === 401) {
      error.value = 'Those credentials don’t match any reader. Try again.'
    } else {
      error.value = e.response?.data?.message || 'Something went wrong. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AppShell>
    <div class="auth-wrap">
      <div class="auth-card">
        <p class="auth-logo">Zaskia.</p>
        <p class="eyebrow auth-eyebrow">Your Quiet Corner</p>
        <h1 class="auth-title">Welcome Back</h1>
        <p class="auth-subtitle">
          A space for your words, and the stories still waiting to be told.
        </p>

        <form class="auth-form" @submit.prevent="handleLogin">
          <div v-if="error" class="alert alert-error">{{ error }}</div>

          <div class="field">
            <label for="email">Email</label>
            <input
              id="email"
              v-model="email"
              class="input"
              type="email"
              required
              placeholder="you@example.com"
              autocomplete="email"
            />
          </div>

          <div class="field">
            <div class="field-row">
              <label for="password">Password</label>
            </div>
            <div class="input-wrap has-icon">
              <input
                id="password"
                v-model="password"
                class="input"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="Enter your password"
                autocomplete="current-password"
              />
              <button
                type="button"
                class="input-icon-btn"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" :size="18" />
                <Eye v-else :size="18" />
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Signing in…' : 'Login' }}
          </button>

          <div class="auth-divider"><span>or</span></div>

          <a class="btn btn-outline" :href="googleAuthUrl">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" width="18" height="18">
              <path
                d="M21.35 11.1h-9.17v2.73h6.51c-.33 3.81-3.5 5.44-6.5 5.44C8.36 19.27 5 16.25 5 12c0-4.1 3.2-7.27 7.2-7.27 3.09 0 4.9 1.97 4.9 1.97L19 4.72S16.56 2 12.1 2C6.42 2 2.03 6.8 2.03 12c0 5.05 4.13 10 10.22 10 5.35 0 9.25-3.67 9.25-9.09 0-1.15-.15-1.81-.15-1.81Z"
              />
            </svg>
            Continue with Google
          </a>
        </form>

        <p class="auth-alt">
          New to the journal?
          <router-link to="/register">Create an account</router-link>
        </p>
      </div>
    </div>
  </AppShell>
</template>
