import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import HomeView from '../views/HomeView.vue'
import AuthCallbackView from '../views/AuthCallbackView.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guest: true },
  },
  {
    path: '/auth/callback',
    name: 'auth-callback',
    component: AuthCallbackView,
    meta: { guest: true },
  },
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: { requiresAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const token = localStorage.getItem('token')

  if (to.meta.requiresAuth && !token) {
    return { name: 'login' }
  }

  if (to.meta.guest && token) {
    return { name: 'home' }
  }

  return true
})

export default router
