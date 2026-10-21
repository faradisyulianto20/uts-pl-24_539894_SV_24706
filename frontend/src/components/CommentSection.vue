<script setup>
import { computed, ref } from 'vue'
import { MessageCircle } from '@lucide/vue'
import api from '../services/api'
import Avatar from './ui/Avatar.vue'
import { formatDate, avatarUrl } from '../utils/format'

const props = defineProps({
  postId: { type: [Number, String], required: true },
  comments: { type: Array, default: () => [] },
})

const body = ref('')
const loading = ref(false)
const error = ref('')
const localComments = ref([])

const allComments = computed(() => [...localComments.value, ...props.comments])

const loggedIn = computed(() => !!localStorage.getItem('token'))

async function submitComment() {
  if (!body.value.trim()) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.post(`/posts/${props.postId}/comments`, {
      body: body.value.trim(),
    })
    const created = data.comment || data.data || data
    localComments.value.unshift(created)
    body.value = ''
  } catch (e) {
    if (e.response?.status === 401) {
      error.value = 'Please log in to leave a comment.'
    } else if (e.response?.status === 422) {
      error.value = Object.values(e.response.data.errors || {}).flat().join(' ')
    } else {
      error.value = e.response?.data?.message || 'Unable to post your comment.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="comments-section">
    <div class="comments-head">
      <h2>Margin Notes</h2>
      <span class="comments-count">
        {{ allComments.length }} {{ allComments.length === 1 ? 'note' : 'notes' }}
      </span>
    </div>
    <p class="comments-sub">Leave a note in the margin — every essay is better with a second voice.</p>

    <form class="comment-form" @submit.prevent="submitComment">
      <div v-if="error" class="alert alert-error">{{ error }}</div>
      <textarea
        v-model="body"
        class="textarea"
        placeholder="Share a thoughtful response…"
        rows="3"
      />
      <div class="comment-form-foot">
        <span class="comment-form-hint">
          <template v-if="loggedIn">Signed in — your note appears right away.</template>
          <template v-else>Log in to leave a note.</template>
        </span>
        <button type="submit" class="btn btn-primary btn-sm" :disabled="loading || !body.trim()">
          <MessageCircle :size="16" />
          {{ loading ? 'Posting…' : 'Post comment' }}
        </button>
      </div>
    </form>

    <div class="comment-list">
      <div v-if="allComments.length === 0" class="empty-state" style="padding: 24px 0">
        <span class="serif">The margins are still empty.</span>
        Be the first to leave a note.
      </div>

      <div v-for="comment in allComments" :key="comment.id" class="comment-item">
        <Avatar :name="comment.user?.name || '?'" :src="avatarUrl(comment.user?.avatar)" />
        <div class="comment-item-body">
          <div class="comment-item-head">
            <span class="comment-item-name">{{ comment.user?.name || 'Reader' }}</span>
            <span class="comment-item-date">{{ formatDate(comment.created_at, { short: true }) }}</span>
          </div>
          <p class="comment-item-text">{{ comment.body }}</p>
          <span class="comment-reply" title="Replies are coming soon">Reply</span>
        </div>
      </div>
    </div>
  </section>
</template>
