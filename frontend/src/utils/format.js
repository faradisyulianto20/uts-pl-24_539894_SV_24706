const MONTHS_LONG = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]

const MONTHS_SHORT = MONTHS_LONG.map((m) => m.slice(0, 3))

export function formatDate(iso, { short = false } = {}) {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return String(iso)
  const months = short ? MONTHS_SHORT : MONTHS_LONG
  const day = String(d.getDate()).padStart(2, '0')
  return `${months[d.getMonth()]} ${day}, ${d.getFullYear()}`
}

export function formatNumber(n) {
  return Number(n || 0).toLocaleString('en-US')
}

export function readingTime(content) {
  const words = stripHtml(content).split(/\s+/).filter(Boolean).length
  return `${Math.max(1, Math.ceil(words / 200))} min read`
}

export function stripHtml(html) {
  return String(html || '')
    .replace(/<[^>]*>/g, ' ')
    .replace(/&nbsp;/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
}

export function plainExcerpt(content, max = 160) {
  const text = stripHtml(content)
  if (text.length <= max) return text
  return text.slice(0, max).replace(/\s+\S*$/, '') + '…'
}

export function contentToHtml(content) {
  const raw = String(content || '')
  if (/<[a-z][\s\S]*>/i.test(raw)) return raw
  return raw
    .split(/\n{2,}/)
    .filter((p) => p.trim())
    .map((p) => `<p>${p.trim().replace(/\n/g, '<br>')}</p>`)
    .join('')
}

export function wordCount(content) {
  return stripHtml(content).split(/\s+/).filter(Boolean).length
}

export function paddedCount(n) {
  return String(n).padStart(2, '0')
}

export function avatarUrl(avatar) {
  if (!avatar) return ''
  if (/^https?:\/\//.test(avatar)) return avatar
  return '/storage/' + String(avatar).replace(/^\/+/, '')
}
