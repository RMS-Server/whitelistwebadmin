<template>
  <div class="loading-overlay" :class="{ hidden: !visible }">
    <div class="loading-spinner"></div>
  </div>
</template>

<script setup>
defineProps({
  visible: {
    type: Boolean,
    default: true
  }
})
</script>