<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Camera, KeyRound, ShieldCheck } from '@lucide/vue'
import AdminLayout from '../../components/AdminLayout.vue'
import Avatar from '../../components/ui/Avatar.vue'
import api from '../../services/api'
import { useAuth } from '../../composables/useAuth'
import { avatarUrl, formatDate } from '../../utils/format'

const router = useRouter()
const { user, fetchMe, setUser } = useAuth()

const name = ref('')
const email = ref('')
const avatarFile = ref(null)
const avatarPreview = ref('')
const saving = ref(false)
const error = ref('')
const success = ref('')
const joinedAt = ref('')

onMounted(async () => {
  try {
    if (!user.value) await fetchMe()
  } catch {
    /* biarkan form kosong */
  }
  if (user.value) {
    name.value = user.value.name || ''
    email.value = user.value.email || ''
    joinedAt.value = user.value.created_at || ''
  }
})

const currentAvatar = computed(() => avatarPreview.value || avatarUrl(user.value?.avatar))

function onPickAvatar(e) {
  const file = e.target.files?.[0]
  if (!file) return
  if (file.size > 1024 * 1024) {
    error.value = 'Avatar must be 1MB or smaller.'
    e.target.value = ''
    return
  }
  error.value = ''
  if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value)
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

function removeAvatar() {
  avatarFile.value = null
  if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value)
  avatarPreview.value = ''
}

async function saveProfile() {
  error.value = ''
  success.value = ''
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('name', name.value.trim())
    formData.append('email', email.value.trim())
    if (avatarFile.value) formData.append('avatar', avatarFile.value)

    const { data } = await api.post('/me', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const updated = data.user
    if (updated) {
      setUser(updated)
      name.value = updated.name || name.value
      email.value = updated.email || email.value
    }
    removeAvatar()
    success.value = 'Profile updated.'
  } catch (e) {
    if (e.response?.status === 422) {
      error.value = Object.values(e.response.data.errors || {}).flat().join(' ')
    } else {
      error.value = e.response?.data?.message || 'Unable to update your profile.'
    }
  } finally {
    saving.value = false
  }
}

function futureDisabled() {
  window.alert('This opens with the next backend update — stay tuned.')
}

function handleLogout() {
  localStorage.removeItem('token')
  router.push({ name: 'login' })
}
</script>

<template>
  <AdminLayout>
    <div class="page-head">
      <div>
        <h1>Profile</h1>
        <p>How you appear in the writing room and the margins.</p>
      </div>
    </div>

    <div class="profile-grid">
      <div class="profile-col">
        <div class="card">
          <h2 class="card-title">Account</h2>
          <p class="card-sub">Your name and email across the journal.</p>
          <hr class="divider" />

          <div v-if="error" class="alert alert-error">{{ error }}</div>
          <div v-if="success" class="alert">{{ success }}</div>

          <div class="avatar-row">
            <Avatar :name="name || 'U'" :src="currentAvatar" size="xl" />
            <div class="avatar-actions">
              <label class="btn btn-outline btn-sm" style="cursor: pointer">
                <Camera :size="16" />
                Change photo
                <input type="file" accept="image/*" hidden @change="onPickAvatar" />
              </label>
              <button
                v-if="avatarFile || avatarPreview"
                type="button"
                class="btn-ghost"
                @click="removeAvatar"
              >
                Remove
              </button>
              <span class="field-hint">JPG, PNG · max 1MB</span>
            </div>
          </div>

          <div class="form-2col">
            <div class="field">
              <label for="name">Name</label>
              <input id="name" v-model="name" class="input" type="text" required />
            </div>
            <div class="field">
              <label for="email">Email</label>
              <input id="email" v-model="email" class="input" type="email" required />
            </div>
          </div>

          <div class="footer-bar">
            <span class="footer-bar-status">
              <ShieldCheck :size="16" />
              Only you can edit this page.
            </span>
            <div class="footer-bar-actions">
              <button type="button" class="btn btn-outline" @click="futureDisabled">
                Discard
              </button>
              <button type="button" class="btn btn-primary" :disabled="saving" @click="saveProfile">
                {{ saving ? 'Saving…' : 'Save changes' }}
              </button>
            </div>
          </div>
        </div>

        <div class="card">
          <h2 class="card-title">Security</h2>
          <p class="card-sub">Keep the door firmly yours.</p>

          <div class="security-row">
            <div>
              <div class="security-row-title">Password</div>
              <div class="security-row-desc">Change it once in a while.</div>
            </div>
            <button type="button" class="btn btn-outline btn-sm" @click="futureDisabled">
              <KeyRound :size="16" />
              Manage
            </button>
          </div>

          <div class="security-row">
            <div>
              <div class="security-row-title">Sessions</div>
              <div class="security-row-desc">Sign out everywhere else.</div>
            </div>
            <button type="button" class="btn btn-outline btn-sm" @click="futureDisabled">
              Review
            </button>
          </div>

          <div class="security-row">
            <div>
              <div class="security-row-title">Log out</div>
              <div class="security-row-desc">Close this writing session.</div>
            </div>
            <button type="button" class="btn btn-outline btn-sm" @click="handleLogout">
              Log out
            </button>
          </div>
        </div>
      </div>

      <div class="profile-col">
        <div class="card">
          <div class="avatar-row">
            <Avatar :name="name || 'U'" :src="currentAvatar" size="lg" />
            <div>
              <h2 class="profile-summary-name">{{ name || '…' }}</h2>
              <span class="badge">
                <span class="badge-dot" />
                {{ user?.role || 'author' }}
              </span>
            </div>
          </div>
          <p class="profile-summary-text">
            Writing, reflecting, and keeping the pages that matter — one quiet essay at a time.
          </p>
          <p v-if="joinedAt" class="profile-summary-stats">
            Member since {{ formatDate(joinedAt, { short: true }) }}
          </p>
        </div>

        <div class="card settings-quote">
          <p class="profile-quote">“Care is attention, repeated.”</p>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
