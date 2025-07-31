<template>
  <div id="app">
    <LoadingOverlay v-if="loading" />
    <router-view />
    <Footer />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import LoadingOverlay from './components/LoadingOverlay.vue'
import Footer from './components/Footer.vue'

const loading = ref(true)

onMounted(() => {
  setTimeout(() => {
    loading.value = false
  }, 1000)
})
</script>

<style>
#app {
  min-height: 100vh;
  position: relative;
  padding-bottom: 60px;
}
</style>