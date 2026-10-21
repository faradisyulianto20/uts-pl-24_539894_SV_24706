<script setup>
import { computed, onMounted, ref } from 'vue'
import { MessageSquare, Search, Info, Reply } from '@lucide/vue'
import AdminLayout from '../../components/AdminLayout.vue'
import Avatar from '../../components/ui/Avatar.vue'
import api from '../../services/api'
import { formatDate, avatarUrl } from '../../utils/format'

const comments = ref([])
const loading = ref(true)
const search = ref('')
const activeTab = ref('all')

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/comments')
    comments.value = data.comments || []
  } finally {
    loading.value = false
  }
})

const filtered = computed(() => {
  let list = comments.value
  const q = search.value.trim().toLowerCase()
  if (q) {
    list = list.filter(
      (c) =>
        (c.body || '').toLowerCase().includes(q) ||
        (c.user?.name || '').toLowerCase().includes(q) ||
        (c.post?.title || '').toLowerCase().includes(q),
    )
  }
  return list
})

async function removeComment(comment) {
  if (!window.confirm('Delete this comment permanently?')) return
  try {
    await api.delete(`/admin/comments/${comment.id}`)
    comments.value = comments.value.filter((c) => c.id !== comment.id)
  } catch (e) {
    window.alert(e.response?.data?.message || 'Unable to delete this comment.')
  }
}
</script>

<template>
  <AdminLayout>
    <div class="page-head">
      <div>
        <h1>Comments</h1>
        <p>Every margin note across your journal, in one place.</p>
      </div>
    </div>

    <div class="moderation-banner">
      <div class="moderation-banner-left">
        <MessageSquare :size="20" />
        <div>
          <div class="moderation-banner-title">
            {{ comments.length }} {{ comments.length === 1 ? 'comment' : 'comments' }} to review
          </div>
          <div class="moderation-banner-sub">Keep the margins thoughtful and kind.</div>
        </div>
      </div>
    </div>

    <div class="card moderation-card">
      <div class="moderation-toolbar">
        <div class="mod-tabs">
          <button
            type="button"
            class="mod-tab"
            :class="{ active: activeTab === 'all' }"
            @click="activeTab = 'all'"
          >
            All comments
          </button>
          <button
            type="button"
            class="mod-tab"
            :class="{ active: activeTab === 'pending' }"
            disabled
            style="opacity: 0.45; cursor: not-allowed"
            title="Approval workflow is coming soon"
          >
            Pending (soon)
          </button>
        </div>
        <div class="mod-search">
          <Search />
          <input
            v-model="search"
            class="input"
            type="search"
            placeholder="Search comments…"
          />
        </div>
      </div>

      <div class="moderation-subbar">
        <span>Showing {{ filtered.length }} of {{ comments.length }} comments</span>
        <span>Newest first</span>
      </div>

      <div v-if="loading" class="empty-state">
        <span class="serif">Gathering the margins…</span>
      </div>

      <template v-else>
        <div v-if="filtered.length === 0" class="empty-state">
          <span class="serif">{{ search ? 'Nothing matches that.' : 'The margins are quiet.' }}</span>
          {{ search ? 'Try a different word.' : 'No comments have been left yet.' }}
        </div>

        <div v-for="comment in filtered" :key="comment.id" class="mod-item">
          <div class="mod-item-head">
            <Avatar :name="comment.user?.name || '?'" :src="avatarUrl(comment.user?.avatar)" />
            <div class="mod-item-who">
              <div class="mod-item-name">{{ comment.user?.name || 'Reader' }}</div>
              <div class="mod-item-date">{{ formatDate(comment.created_at) }}</div>
              <router-link
                v-if="comment.post"
                :to="{ name: 'post-detail', params: { id: comment.post_id } }"
                class="mod-item-on"
              >
                <MessageSquare :size="14" />
                on “{{ comment.post.title }}”
              </router-link>
            </div>
            <span class="badge" style="opacity: 0.7">
              <span class="badge-dot" />
              Approved
            </span>
          </div>

          <p class="mod-item-text">{{ comment.body }}</p>

          <div class="mod-item-actions">
            <span class="mod-reply-link" style="opacity: 0.5" title="Reply is coming soon">
              <Reply :size="15" />
              Reply
            </span>
            <div class="mod-item-actions-right">
              <button
                type="button"
                class="mod-action"
                disabled
                title="Unapprove is coming soon"
              >
                Unapprove
              </button>
              <button
                type="button"
                class="mod-action"
                disabled
                title="Spam marking is coming soon"
              >
                Mark as spam
              </button>
              <button type="button" class="mod-action danger" @click="removeComment(comment)">
                Delete
              </button>
            </div>
          </div>
        </div>
      </template>

      <div class="moderation-foot">
        <span>{{ comments.length }} comments in total</span>
        <span>Sorted by newest</span>
      </div>
    </div>

    <p class="mod-info">
      <Info :size="16" />
      <span>
        Unapprove, spam marking, and replies will light up once the backend grows those
        endpoints — the interface is ready and waiting for them.
      </span>
    </p>
  </AdminLayout>
</template>
