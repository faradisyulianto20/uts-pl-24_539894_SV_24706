import { ref, watchEffect } from 'vue'

const THEME_KEY = 'zaskia-theme'

function getSaved() {
  const saved = localStorage.getItem(THEME_KEY)
  return saved === 'light' || saved === 'dark' || saved === 'system' ? saved : 'system'
}

const theme = ref(getSaved())
const media = window.matchMedia('(prefers-color-scheme: dark)')

function resolve() {
  if (theme.value === 'system') return media.matches ? 'dark' : 'light'
  return theme.value
}

function apply() {
  const resolved = resolve()
  const root = document.documentElement
  root.classList.toggle('dark', resolved === 'dark')
  root.setAttribute('data-theme', resolved)
}

media.addEventListener('change', () => {
  if (theme.value === 'system') apply()
})

export function useTheme() {
  function setTheme(value) {
    theme.value = value
    localStorage.setItem(THEME_KEY, value)
    apply()
  }

  watchEffect(() => {
    theme.value
    apply()
  })

  return { theme, resolvedTheme: resolve, setTheme }
}

apply()
