<script setup>
import { Sun, Moon } from '@lucide/vue'
import { useTheme } from '../composables/useTheme'

const { theme, setTheme } = useTheme()

function isLight() {
  return theme.value === 'light' || (theme.value === 'system' && !window.matchMedia('(prefers-color-scheme: dark)').matches)
}
</script>

<template>
  <div class="theme-toggle" role="group" aria-label="Color theme">
    <button
      type="button"
      aria-label="Switch to light theme"
      :class="{ active: isLight() }"
      @click="setTheme('light')"
    >
      <Sun />
    </button>
    <button
      type="button"
      aria-label="Switch to dark theme"
      :class="{ active: !isLight() }"
      @click="setTheme('dark')"
    >
      <Moon />
    </button>
  </div>
</template>
