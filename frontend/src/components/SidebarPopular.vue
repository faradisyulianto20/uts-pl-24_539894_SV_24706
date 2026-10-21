<script setup>
import { formatDate, paddedCount } from '../utils/format'

defineProps({
  posts: { type: Array, default: () => [] },
})
</script>

<template>
  <aside class="blog-sidebar">
    <div class="sidebar-block">
      <span class="eyebrow">Popular This Week</span>

      <div v-if="posts.length === 0" class="empty-state" style="padding: 24px 0; text-align: left">
        Nothing yet — the readers are still quiet.
      </div>

      <div v-for="(post, i) in posts" :key="post.id" class="popular-item">
        <span class="popular-num">{{ paddedCount(i + 1) }}</span>
        <div class="popular-body">
          <h3>
            <router-link :to="{ name: 'post-detail', params: { id: post.id } }">
              {{ post.title }}
            </router-link>
          </h3>
          <p class="popular-meta">{{ formatDate(post.published_at || post.created_at, { short: true }) }}</p>
        </div>
      </div>
    </div>

    <div class="sidebar-block sidebar-note">
      <span class="eyebrow">A Note from the Author</span>
      <p>
        This journal exists because some thoughts only become clear once they're written
        down slowly. Thank you for reading along.
      </p>
      <span class="sidebar-sign">Zaskia</span>
    </div>
  </aside>
</template>
