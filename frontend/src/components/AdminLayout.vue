<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  LayoutDashboard,
  FileText,
  MessageSquare,
  Settings,
  LogOut,
  ExternalLink,
  Menu,
  X,
} from '@lucide/vue'
import ThemeToggle from './ThemeToggle.vue'
import Avatar from './ui/Avatar.vue'
import api from '../services/api'
import { useAuth } from '../composables/useAuth'
import { avatarUrl } from '../utils/format'

const route = useRoute()
const router = useRouter()
const { user, fetchMe, clearUser } = useAuth()

const drawerOpen = ref(false)
const commentCount = ref(null)

const navItems = computed(() => [
  { to: { name: 'admin-overview' }, label: 'Overview', icon: LayoutDashboard },
  { to: { name: 'admin-articles' }, label: 'Articles', icon: FileText },
  { to: { name: 'admin-comments' }, label: 'Comments', icon: MessageSquare, badge: commentCount.value },
  { to: { name: 'admin-settings' }, label: 'Settings', icon: Settings },
])

const crumbTitle = computed(() => {
  const map = {
    'admin-overview': 'Overview',
    'admin-articles': 'Articles',
    'admin-post-new': 'New essay',
    'admin-post-edit': 'Edit essay',
    'admin-comments': 'Comments',
    'admin-settings': 'Settings',
    'admin-profile': 'Profile',
  }
  return map[route.name] || ''
})

const showCrumbArrow = computed(() =>
  ['admin-post-new', 'admin-post-edit'].includes(route.name),
)

watch(drawerOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(async () => {
  try {
    if (!user.value) await fetchMe()
  } catch {
    clearUser()
    localStorage.removeItem('token')
    router.replace({ name: 'login' })
    return
  }
  if (user.value?.role !== 'admin') {
    router.replace({ name: 'blog' })
    return
  }
  try {
    const { data } = await api.get('/admin/comments')
    commentCount.value = (data.comments || []).length
  } catch {
    commentCount.value = null
  }
})

async function handleLogout() {
  try {
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
  <div class="admin-shell">
    <div
      v-if="drawerOpen"
      style="position: fixed; inset: 0; z-index: 39"
      @click="drawerOpen = false"
    />

    <aside class="admin-sidebar" :class="{ open: drawerOpen }">
      <router-link :to="{ name: 'dashboard' }" class="admin-logo">Zaskia.</router-link>
      <p class="admin-logo-sub">The Writing Room</p>

      <nav class="admin-nav">
        <router-link
          v-for="item in navItems"
          :key="item.label"
          :to="item.to"
          :class="{ active: route.name === item.to.name || (item.to.name === 'admin-articles' && ['admin-post-new', 'admin-post-edit'].includes(route.name)) }"
          @click="drawerOpen = false"
        >
          <component :is="item.icon" :size="18" />
          {{ item.label }}
          <span v-if="item.badge" class="nav-badge">{{ item.badge }}</span>
        </router-link>
      </nav>

      <div class="admin-sidebar-foot">
        <router-link :to="{ name: 'blog' }" class="admin-view-link" @click="drawerOpen = false">
          <ExternalLink :size="16" />
          View blog
        </router-link>

        <div class="admin-user">
          <Avatar
            :name="user?.name || 'A'"
            :src="avatarUrl(user?.avatar)"
            @click="router.push({ name: 'admin-profile' })"
            style="cursor: pointer"
          />
          <div class="admin-user-info">
            <div class="admin-user-name">{{ user?.name || '…' }}</div>
            <div class="admin-user-role">{{ user?.role || '' }}</div>
          </div>
          <button type="button" class="admin-logout" aria-label="Log out" @click="handleLogout">
            <LogOut :size="18" />
          </button>
        </div>
      </div>
    </aside>

    <div class="admin-main">
      <header class="admin-topbar">
        <div style="display: flex; align-items: center; gap: 14px">
          <button
            type="button"
            class="admin-hamburger"
            aria-label="Open menu"
            @click="drawerOpen = true"
          >
            <X v-if="drawerOpen" :size="20" />
            <Menu v-else :size="20" />
          </button>

          <nav class="breadcrumb" aria-label="Breadcrumb">
            <span class="faint">Zaskia.</span>
            <template v-if="showCrumbArrow">
              <router-link :to="{ name: 'admin-articles' }" class="muted">Articles</router-link>
              <span class="faint">/</span>
            </template>
            <span class="crumb-current">{{ crumbTitle }}</span>
          </nav>
        </div>

        <div class="topbar-right">
          <ThemeToggle />
          <span class="topbar-divider" aria-hidden="true" />
          <router-link :to="{ name: 'admin-profile' }" class="topbar-profile">
            {{ user?.name || 'Profile' }}
          </router-link>
        </div>
      </header>

      <main class="admin-content">
        <slot />
      </main>
    </div>
  </div>
</template>
