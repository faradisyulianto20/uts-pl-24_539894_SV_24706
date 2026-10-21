import { createRouter, createWebHistory } from 'vue-router'
import api from '../services/api'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import AuthCallbackView from '../views/AuthCallbackView.vue'
import BlogView from '../views/BlogView.vue'
import PostDetailView from '../views/PostDetailView.vue'
import DashboardView from '../views/admin/DashboardView.vue'
import ArticlesView from '../views/admin/ArticlesView.vue'
import PostFormView from '../views/admin/PostFormView.vue'
import CommentsView from '../views/admin/CommentsView.vue'
import SettingsView from '../views/admin/SettingsView.vue'
import ProfileView from '../views/admin/ProfileView.vue'

const routes = [
  {
    path: '/',
    name: 'blog',
    component: BlogView,
    meta: { requiresAuth: true },
  },
  {
    path: '/post/:id',
    name: 'post-detail',
    component: PostDetailView,
    meta: { requiresAuth: true },
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { guest: true },
  },
  {
    path: '/auth/callback',
    name: 'auth-callback',
    component: AuthCallbackView,
    meta: { guest: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    redirect: { name: 'admin-overview' },
  },
  {
    path: '/dashboard/overview',
    name: 'admin-overview',
    component: DashboardView,
    meta: { requiresAuth: true, admin: true },
  },
  {
    path: '/dashboard/articles',
    name: 'admin-articles',
    component: ArticlesView,
    meta: { requiresAuth: true, admin: true },
  },
  {
    path: '/dashboard/articles/new',
    name: 'admin-post-new',
    component: PostFormView,
    meta: { requiresAuth: true, admin: true },
  },
  {
    path: '/dashboard/articles/:id/edit',
    name: 'admin-post-edit',
    component: PostFormView,
    meta: { requiresAuth: true, admin: true },
  },
  {
    path: '/dashboard/comments',
    name: 'admin-comments',
    component: CommentsView,
    meta: { requiresAuth: true, admin: true },
  },
  {
    path: '/dashboard/settings',
    name: 'admin-settings',
    component: SettingsView,
    meta: { requiresAuth: true, admin: true },
  },
  {
    path: '/dashboard/profile',
    name: 'admin-profile',
    component: ProfileView,
    meta: { requiresAuth: true, admin: true },
  },
  { path: '/:pathMatch(.*)*', redirect: { name: 'blog' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

let checkedAdmin = false

router.beforeEach(async (to) => {
  const token = localStorage.getItem('token')

  if (to.meta.requiresAuth && !token) {
    return { name: 'login' }
  }

  if (to.meta.guest && token) {
    return { name: 'blog' }
  }

  if (to.meta.admin && token) {
    let user = null
    try {
      const cached = sessionStorage.getItem('zaskia-user')
      user = cached ? JSON.parse(cached) : null
    } catch {
      user = null
    }

    if (!user || !checkedAdmin) {
      try {
        const { data } = await api.get('/me')
        user = data.user
        sessionStorage.setItem('zaskia-user', JSON.stringify(user))
        checkedAdmin = true
      } catch {
        localStorage.removeItem('token')
        sessionStorage.removeItem('zaskia-user')
        return { name: 'login' }
      }
    }

    if (user?.role !== 'admin') {
      return { name: 'blog' }
    }
  }

  return true
})

export default router
