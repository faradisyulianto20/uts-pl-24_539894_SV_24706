import { ref } from 'vue'
import api from '../services/api'

const user = ref(null)

function readCache() {
  try {
    const raw = sessionStorage.getItem('zaskia-user')
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

function writeCache(value) {
  if (value) {
    sessionStorage.setItem('zaskia-user', JSON.stringify(value))
  } else {
    sessionStorage.removeItem('zaskia-user')
  }
}

export function useAuth() {
  async function fetchMe(force = false) {
    if (user.value && !force) return user.value
    const cached = readCache()
    if (cached && !force) {
      user.value = cached
      return cached
    }
    const { data } = await api.get('/me')
    user.value = data.user
    writeCache(data.user)
    return data.user
  }

  function setUser(value) {
    user.value = value
    writeCache(value)
  }

  function clearUser() {
    user.value = null
    writeCache(null)
  }

  function isAdmin() {
    return user.value?.role === 'admin'
  }

  return { user, fetchMe, setUser, clearUser, isAdmin }
}
