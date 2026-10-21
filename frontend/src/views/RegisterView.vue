<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Eye, EyeOff } from '@lucide/vue'
import api from '../services/api'
import AppShell from '../components/AppShell.vue'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { setUser } = useAuth()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirm = ref('')
const showPassword = ref(false)
const error = ref('')
const loading = ref(false)

async function handleRegister() {
  error.value = ''
  loading.value = true

  try {
    const { data } = await api.post('/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirm.value,
    })

    if (data.token) {
      localStorage.setItem('token', data.token)
    }
    if (data.user) setUser(data.user)
    router.push({ name: 'blog' })
  } catch (e) {
    if (e.response?.status === 422) {
      const errors = e.response.data.errors
      error.value = Object.values(errors).flat().join(' ')
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
        <p class="eyebrow auth-eyebrow">Begin Your Journal</p>
        <h1 class="auth-title">Create Account</h1>
        <p class="auth-subtitle">
          A quiet place for your words — write, reflect, and keep the pages that matter.
        </p>

        <form class="auth-form" @submit.prevent="handleRegister">
          <div v-if="error" class="alert alert-error">{{ error }}</div>

          <div class="field">
            <label for="name">Name</label>
            <input
              id="name"
              v-model="name"
              class="input"
              type="text"
              required
              placeholder="Your name"
              autocomplete="name"
            />
          </div>

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
            <label for="password">Password</label>
            <div class="input-wrap has-icon">
              <input
                id="password"
                v-model="password"
                class="input"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="At least 8 characters"
                autocomplete="new-password"
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

          <div class="field">
            <label for="password_confirm">Confirm Password</label>
            <input
              id="password_confirm"
              v-model="passwordConfirm"
              class="input"
              :type="showPassword ? 'text' : 'password'"
              required
              placeholder="Repeat your password"
              autocomplete="new-password"
            />
          </div>

          <button type="submit" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Creating…' : 'Create account' }}
          </button>
        </form>

        <p class="auth-alt">
          Already have a page here?
          <router-link to="/login">Log in</router-link>
        </p>
      </div>
    </div>
  </AppShell>
</template>
