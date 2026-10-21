<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'
import AppShell from '../components/AppShell.vue'
import SidebarPopular from '../components/SidebarPopular.vue'
import { formatDate, readingTime, plainExcerpt } from '../utils/format'

const posts = ref([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await api.get('/posts')
    posts.value = data.data || data.posts || data
  } catch (e) {
    error.value = e.response?.data?.message || 'Unable to load essays.'
  } finally {
    loading.value = false
  }
})

const popular = computed(() =>
  posts.value
    .filter((p) => p.is_popular)
    .sort((a, b) => (b.views || 0) - (a.views || 0))
    .slice(0, 3),
)
</script>

<template>
  <AppShell>
    <div class="blog-page">
      <section class="blog-hero">
        <p class="eyebrow">The Journal</p>
        <h1>Quiet thoughts,<br />written down.</h1>
        <p>
          Essays on craft, attention, and the slow work of making sense — collected here,
          one page at a time.
        </p>
      </section>

      <div class="blog-grid">
        <div>
          <div class="latest-bar">
            <span class="eyebrow">Latest Essays</span>
            <span class="latest-count">{{ posts.length }} entries</span>
          </div>

          <div v-if="loading" class="empty-state">
            <span class="serif">Gathering the pages…</span>
          </div>

          <div v-else-if="error" class="empty-state">
            <span class="serif">Something went quiet.</span>
            {{ error }}
          </div>

          <div v-else-if="posts.length === 0" class="empty-state">
            <span class="serif">No essays yet.</span>
            The first page is still blank.
          </div>

          <article v-for="post in posts" :key="post.id" class="essay-card">
            <router-link
              v-if="post.cover_image_url"
              :to="{ name: 'post-detail', params: { id: post.id } }"
              class="essay-cover"
            >
              <img :src="post.cover_image_url" :alt="post.title" />
            </router-link>
            <router-link
              v-else
              :to="{ name: 'post-detail', params: { id: post.id } }"
              class="essay-cover"
            >
              <span class="cover-placeholder-pill">Cover Image</span>
              <span class="cover-placeholder-text">
                <strong>Cover Image</strong>
                <span>Add a quiet image to give this essay a visual atmosphere.</span>
              </span>
            </router-link>

            <p class="essay-meta">
              {{ formatDate(post.published_at || post.created_at) }}
            </p>
            <h2>
              <router-link :to="{ name: 'post-detail', params: { id: post.id } }">
                {{ post.title }}
              </router-link>
            </h2>
            <p class="essay-excerpt">{{ plainExcerpt(post.content) }}</p>
            <div class="essay-foot">
              <router-link
                :to="{ name: 'post-detail', params: { id: post.id } }"
                class="read-more"
              >
                Continue reading
                <span aria-hidden="true">→</span>
              </router-link>
              <span class="read-time">{{ readingTime(post.content) }}</span>
            </div>
          </article>

          <p v-if="!loading && !error && posts.length > 0" class="caught-up">
            — you're all caught up —
          </p>
        </div>

        <SidebarPopular :posts="popular" />
      </div>
    </div>
  </AppShell>
</template>
