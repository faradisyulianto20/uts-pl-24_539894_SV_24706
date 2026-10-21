<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft, MessageCircle } from '@lucide/vue'
import api from '../services/api'
import AppShell from '../components/AppShell.vue'
import CommentSection from '../components/CommentSection.vue'
import Avatar from '../components/ui/Avatar.vue'
import Chip from '../components/ui/Chip.vue'
import { formatDate, readingTime, formatNumber, contentToHtml, avatarUrl } from '../utils/format'

const route = useRoute()
const post = ref(null)
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await api.get(`/posts/${route.params.id}`)
    post.value = data.post || data.data || data
  } catch (e) {
    error.value = e.response?.data?.message || 'Unable to load this essay.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <AppShell>
    <div class="article-page">
      <div v-if="loading" class="empty-state">
        <span class="serif">Opening the page…</span>
      </div>

      <div v-else-if="error || !post" class="empty-state">
        <span class="serif">This page slipped away.</span>
        {{ error }}
        <div style="margin-top: 16px">
          <router-link to="/" class="read-more">← Back to the journal</router-link>
        </div>
      </div>

      <template v-else>
        <router-link to="/" class="article-back">
          <ArrowLeft :size="16" />
          Back to the journal
        </router-link>

        <p class="eyebrow">{{ formatDate(post.published_at || post.created_at) }}</p>
        <h1>{{ post.title }}</h1>
        <p v-if="post.excerpt" class="article-excerpt">{{ post.excerpt }}</p>

        <div class="article-author">
          <Avatar :name="post.user?.name || 'Z'" size="lg" :src="avatarUrl(post.user?.avatar)" />
          <div>
            <div class="article-author-name">{{ post.user?.name || 'Zaskia' }}</div>
            <div class="article-author-meta">
              {{ readingTime(post.content) }} · {{ formatNumber(post.views) }} views
            </div>
          </div>
        </div>

        <div class="article-cover">
          <img v-if="post.cover_image_url" :src="post.cover_image_url" :alt="post.title" />
          <div v-else class="article-cover-center">
            <span class="cover-icon"><MessageCircle :size="18" /></span>
            <h3>{{ post.title }}</h3>
            <p>
              Add a quiet image to give this essay a visual atmosphere — a calm frame for
              the words within.
            </p>
          </div>
        </div>

        <div class="essay-body" v-html="contentToHtml(post.content)" />

        <p class="essay-dots" aria-hidden="true">· · ·</p>

        <div v-if="post.tags && post.tags.length" class="article-tags">
          <Chip v-for="tag in post.tags" :key="tag" :label="tag" />
        </div>

        <CommentSection :post-id="post.id" :comments="post.comments || []" />
      </template>
    </div>
  </AppShell>
</template>
