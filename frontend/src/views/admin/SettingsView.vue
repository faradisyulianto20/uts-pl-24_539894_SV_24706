<script setup>
import { onMounted, ref } from 'vue'
import { useTheme } from '../../composables/useTheme'
import AdminLayout from '../../components/AdminLayout.vue'
import ToggleSwitch from '../../components/ui/ToggleSwitch.vue'
import { Sun, Moon, Monitor } from '@lucide/vue'

const SETTINGS_KEY = 'zaskia-writing-room-settings'

const { theme, setTheme } = useTheme()

const siteName = ref('Zaskia.')
const tagline = ref('A little more attention. A little less hurry.')
const autoSave = ref(true)
const emailDigest = ref(false)
const showReadingTime = ref(true)
const savedFlash = ref(false)

onMounted(() => {
  try {
    const raw = localStorage.getItem(SETTINGS_KEY)
    if (raw) {
      const s = JSON.parse(raw)
      siteName.value = s.siteName ?? siteName.value
      tagline.value = s.tagline ?? tagline.value
      autoSave.value = s.autoSave ?? autoSave.value
      emailDigest.value = s.emailDigest ?? emailDigest.value
      showReadingTime.value = s.showReadingTime ?? showReadingTime.value
    }
  } catch {
    /* biarkan default */
  }
})

function saveSettings() {
  localStorage.setItem(
    SETTINGS_KEY,
    JSON.stringify({
      siteName: siteName.value,
      tagline: tagline.value,
      autoSave: autoSave.value,
      emailDigest: emailDigest.value,
      showReadingTime: showReadingTime.value,
    }),
  )
  savedFlash.value = true
  window.setTimeout(() => (savedFlash.value = false), 2000)
}
</script>

<template>
  <AdminLayout>
    <div class="page-head">
      <div>
        <h1>Settings</h1>
        <p>Tune the writing room until it feels like yours.</p>
      </div>
    </div>

    <div class="settings-grid">
      <div class="settings-col">
        <div class="card">
          <h2 class="card-title">General</h2>
          <p class="card-sub">How the journal introduces itself.</p>
          <hr class="divider" />

          <div class="form-2col">
            <div class="field">
              <label for="site_name">Site name</label>
              <input id="site_name" v-model="siteName" class="input" type="text" />
            </div>
            <div class="field">
              <label for="tagline">Tagline</label>
              <input id="tagline" v-model="tagline" class="input" type="text" />
            </div>
          </div>
        </div>

        <div class="card">
          <h2 class="card-title">Writing</h2>
          <p class="card-sub">Small comforts while you write.</p>
          <hr class="divider" />

          <div class="setting-toggle-row">
            <div>
              <div class="setting-toggle-title">Autosave drafts</div>
              <div class="setting-toggle-desc">Keep local copies while you write.</div>
            </div>
            <ToggleSwitch v-model="autoSave" label="Autosave drafts" />
          </div>

          <div class="setting-toggle-row">
            <div>
              <div class="setting-toggle-title">Show reading time</div>
              <div class="setting-toggle-desc">Display estimated reading time on essays.</div>
            </div>
            <ToggleSwitch v-model="showReadingTime" label="Show reading time" />
          </div>

          <div class="setting-toggle-row">
            <div>
              <div class="setting-toggle-title">Monthly email digest</div>
              <div class="setting-toggle-desc">A gentle recap — coming with the backend update.</div>
            </div>
            <ToggleSwitch v-model="emailDigest" label="Monthly email digest" disabled />
          </div>
        </div>

        <div class="footer-bar">
          <span class="footer-bar-status">
            {{ savedFlash ? 'Settings saved locally.' : 'Changes are stored in this browser.' }}
          </span>
          <div class="footer-bar-actions">
            <button type="button" class="btn btn-primary" @click="saveSettings">
              Save settings
            </button>
          </div>
        </div>
      </div>

      <div class="settings-col">
        <div class="card">
          <h2 class="card-title">Appearance</h2>
          <p class="card-sub">How the room is lit.</p>

          <div class="theme-cards">
            <button
              type="button"
              class="theme-card"
              :class="{ selected: theme === 'light' }"
              @click="setTheme('light')"
            >
              <Sun :size="18" />
              Light
            </button>
            <button
              type="button"
              class="theme-card"
              :class="{ selected: theme === 'dark' }"
              @click="setTheme('dark')"
            >
              <Moon :size="18" />
              Dark
            </button>
            <button
              type="button"
              class="theme-card"
              :class="{ selected: theme === 'system' }"
              @click="setTheme('system')"
            >
              <Monitor :size="18" />
              System
            </button>
          </div>
        </div>

        <div class="card settings-quote">
          <p>“The room should feel like a room — not a dashboard.”</p>
          <small>— the design notes</small>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
