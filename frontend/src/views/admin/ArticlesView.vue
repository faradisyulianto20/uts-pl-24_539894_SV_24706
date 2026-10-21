<script setup>
import { computed, onMounted, ref } from 'vue'
import { PenLine, Search } from '@lucide/vue'
import AdminLayout from '../../components/AdminLayout.vue'
import api from '../../services/api'
import { formatDate, formatNumber, readingTime } from '../../utils/format'

const posts = ref([])
const loading = ref(true)
const search = ref('')
const confirmDelete = ref(null)

onMounted(async () => {
  try {
    const { data } = await api.get('/posts')
    posts.value = data.posts || []
  } finally {
    loading.value = false
  }
})

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return posts.value
  return posts.value.filter(
    (p) => p.title.toLowerCase().includes(q) || (p.content || '').toLowerCase().includes(q),
  )
})

async function removePost(post) {
  if (!window.confirm(`Delete “${post.title}”? This cannot be undone.`)) return
  try {
    await api.delete(`/posts/${post.id}`)
    posts.value = posts.value.filter((p) => p.id !== post.id)
  } catch (e) {
    window.alert(e.response?.data?.message || 'Unable to delete this essay.')
  } finally {
    confirmDelete.value = null
  }
}
</script>

<template>
  <AdminLayout>
    <div class="page-head">
      <div>
        <h1>Articles</h1>
        <p>Every essay in your journal — edit, review, or let one go.</p>
      </div>
      <router-link :to="{ name: 'admin-post-new' }" class="btn btn-primary">
        <PenLine :size="16" />
        Write new
      </router-link>
    </div>

    <div class="card table-card">
      <div class="table-card-head">
        <div>
          <h2 class="card-title">Your Essays</h2>
          <p class="card-sub">{{ posts.length }} total essays</p>
        </div>
        <div class="mod-search">
          <Search />
          <input
            v-model="search"
            class="input"
            type="search"
            placeholder="Search by title or content…"
          />
        </div>
      </div>

      <div v-if="loading" class="empty-state">
        <span class="serif">Opening the drawer…</span>
      </div>

      <table v-else-if="filtered.length" class="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Published</th>
            <th>Reading</th>
            <th>Views</th>
            <th style="text-align: right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="post in filtered" :key="post.id">
            <td>
              <div class="table-essay-title">{{ post.title }}</div>
              <div class="table-essay-meta">by {{ post.user?.name || 'Author' }}</div>
            </td>
            <td>{{ post.published_at ? formatDate(post.published_at, { short: true }) : '—' }}</td>
            <td>{{ readingTime(post.content) }}</td>
            <td>{{ formatNumber(post.views) }}</td>
            <td>
              <div class="actions-cell">
                <router-link
                  :to="{ name: 'admin-post-edit', params: { id: post.id } }"
                  class="table-action"
                >
                  Edit
                </router-link>
                <router-link
                  :to="{ name: 'post-detail', params: { id: post.id } }"
                  class="table-action"
                >
                  View
                </router-link>
                <button type="button" class="table-action danger" @click="removePost(post)">
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <span class="serif">{{ search ? 'Nothing matches that.' : 'No essays yet.' }}</span>
        {{ search ? 'Try a different word.' : 'Your first page is waiting.' }}
      </div>
    </div>
  </AdminLayout>
</template>
