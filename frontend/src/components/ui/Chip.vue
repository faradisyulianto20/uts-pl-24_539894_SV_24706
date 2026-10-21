<script setup>
defineProps({
  label: { type: String, required: true },
})
</script>

<template>
  <span class="chip">{{ label }}</span>
</template>
