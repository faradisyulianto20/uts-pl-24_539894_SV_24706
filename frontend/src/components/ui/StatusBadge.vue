<script setup>
defineProps({
  label: { type: String, required: true },
  variant: { type: String, default: 'accent' }, // 'accent' | 'neutral'
})
</script>

<template>
  <span class="badge" :class="{ 'badge-neutral': variant === 'neutral' }">
    <span class="badge-dot" />
    {{ label }}
  </span>
</template>
