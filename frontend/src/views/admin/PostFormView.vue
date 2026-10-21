<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import {
  Bold,
  Italic,
  Strikethrough,
  Quote,
  List,
  ListOrdered,
  Link2,
  Undo2,
  Redo2,
  ImageIcon,
  X,
  Check,
} from '@lucide/vue'
import AdminLayout from '../../components/AdminLayout.vue'
import ToggleSwitch from '../../components/ui/ToggleSwitch.vue'
import api from '../../services/api'
import { wordCount } from '../../utils/format'

const route = useRoute()
const router = useRouter()

const isEdit = computed(() => route.name === 'admin-post-edit')
const postId = computed(() => route.params.id)

const title = ref('')
const content = ref('')
const publishedAt = ref(new Date().toISOString().slice(0, 10))
const isPopular = ref(false)
const coverFile = ref(null)
const coverPreview = ref('')
const existingCover = ref('')
const loading = ref(true)
const saving = ref(false)
const error = ref('')

const editor = useEditor({
  extensions: [StarterKit, Link],
  content: '',
  onUpdate({ editor: e }) {
    content.value = e.isEmpty ? '' : e.getHTML()
  },
})

function setLink() {
  if (!editor.value) return
  const previous = editor.value.getAttributes('link').href
  const url = window.prompt('Link URL', previous || 'https://')
  if (url === null) return
  if (url === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
    return
  }
  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

if (isEdit.value) {
  api
    .get(`/posts/${postId.value}`)
    .then(({ data }) => {
      const post = data.post
      title.value = post.title
      content.value = post.content
      publishedAt.value = (post.published_at || post.created_at).slice(0, 10)
      isPopular.value = !!post.is_popular
      existingCover.value = post.cover_image_url || ''
      editor.value?.commands.setContent(post.content || '')
    })
    .catch((e) => {
      error.value = e.response?.data?.message || 'Unable to load this essay.'
    })
    .finally(() => {
      loading.value = false
    })
} else {
  loading.value = false
}

onBeforeUnmount(() => editor.value?.destroy())

watch(coverFile, (file) => {
  if (coverPreview.value) URL.revokeObjectURL(coverPreview.value)
  coverPreview.value = file ? URL.createObjectURL(file) : ''
})

function onPickCover(e) {
  const file = e.target.files?.[0]
  if (!file) return
  if (file.size > 2 * 1024 * 1024) {
    error.value = 'Cover image must be 2MB or smaller.'
    e.target.value = ''
    return
  }
  error.value = ''
  coverFile.value = file
}

function removeCover() {
  coverFile.value = null
  existingCover.value = ''
  if (coverPreview.value) URL.revokeObjectURL(coverPreview.value)
  coverPreview.value = ''
}

const previewSrc = computed(() => coverPreview.value || existingCover.value)

async function save() {
  error.value = ''
  if (!title.value.trim() || !content.value.trim()) {
    error.value = 'Title and content are required.'
    return
  }
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('title', title.value.trim())
    formData.append('content', content.value.trim())
    formData.append('published_at', publishedAt.value)
    formData.append('is_popular', isPopular.value ? 1 : 0)
    if (coverFile.value) formData.append('cover_image', coverFile.value)

    if (isEdit.value) {
      formData.append('_method', 'PUT')
      await api.post(`/posts/${postId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    } else {
      await api.post('/posts', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }
    router.push({ name: 'admin-articles' })
  } catch (e) {
    if (e.response?.status === 422) {
      error.value = Object.values(e.response.data.errors || {}).flat().join(' ')
    } else {
      error.value = e.response?.data?.message || 'Unable to save this essay.'
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AdminLayout>
    <div class="page-head">
      <div>
        <h1>{{ isEdit ? 'Edit Essay' : 'Write New Essay' }}</h1>
        <p>{{ isEdit ? 'Refine the words, then update the page.' : 'A blank page, a quiet room — begin.' }}</p>
      </div>
    </div>

    <div v-if="loading" class="empty-state">
      <span class="serif">Opening the page…</span>
    </div>

    <form v-else class="form-grid" @submit.prevent="save">
      <div class="form-main">
        <div v-if="error" class="alert alert-error">{{ error }}</div>

        <div class="field">
          <label for="title">Title</label>
          <input
            id="title"
            v-model="title"
            class="input"
            type="text"
            placeholder="An honest title finds its reader."
            required
          />
        </div>

        <div class="field">
          <span class="field-label">Content</span>
          <div class="rte">
            <div v-if="editor" class="rte-toolbar">
              <select
                class="rte-select"
                :value="editor.isActive('heading', { level: 2 }) ? 'h2' : editor.isActive('heading', { level: 3 }) ? 'h3' : 'p'"
                @change="
                  (e) => {
                    const v = e.target.value
                    if (v === 'p') editor.chain().focus().setParagraph().run()
                    else editor.chain().focus().toggleHeading({ level: Number(v[1]) }).run()
                  }
                "
              >
                <option value="p">Paragraph</option>
                <option value="h2">Heading 2</option>
                <option value="h3">Heading 3</option>
              </select>
              <span class="rte-sep" />
              <button
                type="button"
                class="rte-btn"
                :class="{ active: editor.isActive('bold') }"
                aria-label="Bold"
                @click="editor.chain().focus().toggleBold().run()"
              >
                <Bold :size="16" />
              </button>
              <button
                type="button"
                class="rte-btn"
                :class="{ active: editor.isActive('italic') }"
                aria-label="Italic"
                @click="editor.chain().focus().toggleItalic().run()"
              >
                <Italic :size="16" />
              </button>
              <button
                type="button"
                class="rte-btn"
                :class="{ active: editor.isActive('strike') }"
                aria-label="Strikethrough"
                @click="editor.chain().focus().toggleStrike().run()"
              >
                <Strikethrough :size="16" />
              </button>
              <span class="rte-sep" />
              <button
                type="button"
                class="rte-btn"
                :class="{ active: editor.isActive('blockquote') }"
                aria-label="Blockquote"
                @click="editor.chain().focus().toggleBlockquote().run()"
              >
                <Quote :size="16" />
              </button>
              <button
                type="button"
                class="rte-btn"
                :class="{ active: editor.isActive('bulletList') }"
                aria-label="Bullet list"
                @click="editor.chain().focus().toggleBulletList().run()"
              >
                <List :size="16" />
              </button>
              <button
                type="button"
                class="rte-btn"
                :class="{ active: editor.isActive('orderedList') }"
                aria-label="Ordered list"
                @click="editor.chain().focus().toggleOrderedList().run()"
              >
                <ListOrdered :size="16" />
              </button>
              <span class="rte-sep" />
              <button
                type="button"
                class="rte-btn"
                :class="{ active: editor.isActive('link') }"
                aria-label="Link"
                @click="setLink"
              >
                <Link2 :size="16" />
              </button>
              <span class="rte-sep" />
              <button
                type="button"
                class="rte-btn"
                aria-label="Undo"
                :disabled="!editor.can().undo()"
                @click="editor.chain().focus().undo().run()"
              >
                <Undo2 :size="16" />
              </button>
              <button
                type="button"
                class="rte-btn"
                aria-label="Redo"
                :disabled="!editor.can().redo()"
                @click="editor.chain().focus().redo().run()"
              >
                <Redo2 :size="16" />
              </button>
            </div>
            <EditorContent :editor="editor" class="rte-content" />
            <div class="rte-foot">
              <span>{{ wordCount(content) }} words</span>
              <span>{{ content.length }} characters</span>
            </div>
          </div>
        </div>

        <div class="field">
          <span class="field-label">Cover Image</span>

          <div v-if="previewSrc" class="cover-preview">
            <img :src="previewSrc" alt="Cover preview" />
            <button type="button" class="cover-remove" aria-label="Remove cover" @click="removeCover">
              <X :size="16" />
            </button>
          </div>

          <label v-else class="dropzone">
            <input type="file" accept="image/*" hidden @change="onPickCover" />
            <span class="dropzone-icon"><ImageIcon :size="22" /></span>
            <span><strong>Click to upload</strong> or drag and drop</span>
            <span>SVG, PNG, JPG (max. 2MB)</span>
          </label>

          <template v-if="previewSrc">
            <div class="cover-meta">
              <span class="cover-meta-left">
                <ImageIcon :size="14" />
                {{ coverFile ? coverFile.name : 'Current cover image' }}
              </span>
              <button type="button" class="btn-ghost" @click="removeCover">
                {{ coverFile ? 'Replace' : 'Remove' }}
              </button>
            </div>
            <div class="cover-hints">
              <span>Recommended 16:9 · max 2MB</span>
              <span>SVG, PNG, JPG</span>
            </div>
            <label v-if="coverFile" class="btn-ghost" style="cursor: pointer; margin-top: 6px">
              or pick another file
              <input type="file" accept="image/*" hidden @change="onPickCover" />
            </label>
          </template>
        </div>

        <div class="footer-bar">
          <span class="footer-bar-status">
            <Check :size="16" />
            {{ saving ? 'Saving…' : 'Changes saved locally' }}
          </span>
          <div class="footer-bar-actions">
            <button type="button" class="btn btn-outline" @click="router.push({ name: 'admin-articles' })">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Saving…' : isEdit ? 'Update essay' : 'Publish essay' }}
            </button>
          </div>
        </div>
      </div>

      <aside class="form-side">
        <div class="card">
          <h2 class="card-title" style="font-size: 20px">Essay Settings</h2>
          <hr class="divider" />

          <div class="field">
            <label for="published_at">Publication Date</label>
            <input id="published_at" v-model="publishedAt" class="input" type="date" required />
          </div>

          <hr class="divider" />

          <div class="setting-toggle-row" style="padding-top: 0">
            <div>
              <div class="setting-toggle-title">Popular this week</div>
              <div class="setting-toggle-desc">Feature in the sidebar highlights.</div>
            </div>
            <ToggleSwitch v-model="isPopular" label="Popular this week" />
          </div>

          <hr class="divider" />

          <div class="field">
            <div class="field-row">
              <span class="field-label">Excerpt</span>
              <span class="field-hint">Soon</span>
            </div>
            <textarea
              class="textarea"
              rows="3"
              disabled
              placeholder="A short summary shown on cards — backend support coming soon."
            />
          </div>

          <div class="field">
            <div class="field-row">
              <span class="field-label">Category</span>
              <span class="field-hint">Soon</span>
            </div>
            <select class="select" disabled>
              <option>Select a category — backend support coming soon.</option>
            </select>
          </div>

          <div class="field">
            <div class="field-row">
              <span class="field-label">Tags</span>
              <span class="field-hint">Soon</span>
            </div>
            <input class="input" type="text" disabled placeholder="Comma-separated tags — backend support coming soon." />
          </div>

          <div class="field">
            <span class="field-label">Status</span>
            <div class="tag-chips" style="margin-top: 6px">
              <span class="badge" style="opacity: 0.55">
                <span class="badge-dot" />
                Draft (soon)
              </span>
              <span class="badge badge-neutral" style="opacity: 0.55">
                <span class="badge-dot" />
                Published
              </span>
            </div>
          </div>
        </div>

        <div class="card side-quote">
          <p>“A page is never finished — only paused.”</p>
        </div>
      </aside>
    </form>
  </AdminLayout>
</template>
