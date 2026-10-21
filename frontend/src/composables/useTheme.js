import { ref } from 'vue'

const THEME_KEY = 'zaskia-theme'

function getInitialTheme() {
  const saved = localStorage.getItem(THEME_KEY)
  if (saved === 'light' || saved === 'dark') return saved
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

const theme = ref(getInitialTheme())

function apply() {
  document.documentElement.classList.toggle('dark', theme.value === 'dark')
}

export function useTheme() {
  function setTheme(value) {
    theme.value = value
    localStorage.setItem(THEME_KEY, value)
    apply()
  }

  function toggle() {
    setTheme(theme.value === 'dark' ? 'light' : 'dark')
  }

  return { theme, setTheme, toggle }
}

apply()
