<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  label: { type: String, default: '' },
})
defineEmits(['update:modelValue'])
</script>

<template>
  <button
    type="button"
    class="toggle-switch"
    role="switch"
    :aria-checked="modelValue ? 'true' : 'false'"
    :aria-label="label || 'Toggle'"
    :disabled="disabled"
    @click="$emit('update:modelValue', !modelValue)"
  />
</template>
