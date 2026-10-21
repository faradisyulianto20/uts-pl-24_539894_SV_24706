<script setup>
import { computed, onMounted, ref } from 'vue'
import { PenLine, MessageSquare, ArrowRight } from '@lucide/vue'
import AdminLayout from '../../components/AdminLayout.vue'
import Avatar from '../../components/ui/Avatar.vue'
import api from '../../services/api'
import { useAuth } from '../../composables/useAuth'
import { formatDate, formatNumber, avatarUrl } from '../../utils/format'

const { user } = useAuth()

const posts = ref([])
const comments = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const [postsRes, commentsRes] = await Promise.all([
      api.get('/posts'),
      api.get('/admin/comments'),
    ])
    posts.value = postsRes.data.posts || []
    comments.value = commentsRes.data.comments || []
  } finally {
    loading.value = false
  }
})

const totalViews = computed(() => posts.value.reduce((sum, p) => sum + (p.views || 0), 0))
const maxViews = computed(() => Math.max(1, ...posts.value.map((p) => p.views || 0)))
const recentComments = computed(() => comments.value.slice(0, 3))
const recentActivity = computed(() =>
  posts.value
    .slice()
    .sort((a, b) => new Date(b.updated_at || b.created_at) - new Date(a.updated_at || a.created_at))
    .slice(0, 4),
)

const stats = computed(() => [
  { label: 'Total Essays', value: posts.value.length, desc: 'Essays in your journal' },
  { label: 'Published', value: posts.value.filter((p) => p.published_at).length, desc: 'Visible to readers' },
  { label: 'Total Views', value: formatNumber(totalViews.value), desc: 'Across all essays' },
  { label: 'Comments', value: comments.value.length, desc: 'Margin notes from readers' },
])
</script>

<template>
  <AdminLayout>
    <div class="page-head">
      <div>
        <h1>Good evening, {{ (user?.name || 'Author').split(' ')[0] }}.</h1>
        <p>Here's what your journal has been up to.</p>
      </div>
      <router-link :to="{ name: 'admin-post-new' }" class="btn btn-primary">
        <PenLine :size="16" />
        Write new
      </router-link>
    </div>

    <div v-if="loading" class="empty-state">
      <span class="serif">Gathering your pages…</span>
    </div>

    <template v-else>
      <div class="stat-grid">
        <div v-for="stat in stats" :key="stat.label" class="card stat-card">
          <span class="eyebrow">{{ stat.label }}</span>
          <span class="stat-value">{{ stat.value }}</span>
          <span class="stat-desc">{{ stat.desc }}</span>
        </div>
      </div>

      <div class="card table-card">
        <div class="table-card-head">
          <div>
            <h2 class="card-title">Recent Essays</h2>
            <p class="card-sub">Your latest pages, and how far they've travelled.</p>
          </div>
          <router-link :to="{ name: 'admin-articles' }" class="card-link" style="margin-top: 0">
            Manage articles <ArrowRight :size="14" />
          </router-link>
        </div>

        <table v-if="posts.length" class="data-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Published</th>
              <th>Views</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="post in posts.slice(0, 5)" :key="post.id">
              <td>
                <div class="table-essay-title">{{ post.title }}</div>
                <div class="table-essay-meta">by {{ post.user?.name || 'Author' }}</div>
              </td>
              <td>{{ post.published_at ? formatDate(post.published_at, { short: true }) : '—' }}</td>
              <td class="views-cell">
                <div class="views-number">{{ formatNumber(post.views) }}</div>
                <div class="views-bar">
                  <span :style="{ width: Math.round(((post.views || 0) / maxViews) * 100) + '%' }" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="empty-state">
          <span class="serif">No essays yet.</span>
          Your first page is waiting.
        </div>
      </div>

      <div class="overview-bottom">
        <div class="card">
          <h2 class="card-title">Recent Comments</h2>
          <p class="card-sub">Latest notes left in the margins.</p>

          <div v-if="recentComments.length === 0" class="empty-state" style="padding: 24px 0; text-align: left">
            No comments yet — the margins are quiet.
          </div>

          <div v-for="comment in recentComments" :key="comment.id" class="mini-comment">
            <Avatar :name="comment.user?.name || '?'" :src="avatarUrl(comment.user?.avatar)" />
            <div>
              <span class="mini-comment-name">{{ comment.user?.name || 'Reader' }}</span>
              <span class="mini-comment-date"> · {{ formatDate(comment.created_at, { short: true }) }}</span>
              <p class="mini-comment-text">{{ comment.body }}</p>
              <span class="mini-comment-on">on “{{ comment.post?.title || '—' }}”</span>
            </div>
          </div>

          <router-link :to="{ name: 'admin-comments' }" class="card-link">
            <MessageSquare :size="14" />
            Moderate comments
          </router-link>
        </div>

        <div class="card">
          <h2 class="card-title">Latest Activity</h2>
          <p class="card-sub">What changed recently.</p>

          <div v-if="recentActivity.length === 0" class="empty-state" style="padding: 24px 0; text-align: left">
            Nothing yet.
          </div>

          <div v-for="post in recentActivity" :key="post.id" class="activity-item">
            <span class="activity-date">{{ formatDate(post.updated_at || post.created_at, { short: true }) }}</span>
            <div class="activity-title">{{ post.title }}</div>
            <div class="activity-sub">Last edited by {{ post.user?.name || 'Author' }}</div>
          </div>

          <router-link :to="{ name: 'admin-post-new' }" class="card-link">
            <PenLine :size="14" />
            Write new essay
          </router-link>
        </div>
      </div>
    </template>
  </AdminLayout>
</template>
