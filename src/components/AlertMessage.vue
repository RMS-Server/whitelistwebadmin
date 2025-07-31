<template>
  <transition name="alert">
    <div v-if="visible" class="alert-container">
      <div class="alert-backdrop"></div>
      <div 
        :class="['alert-message', isError ? 'alert-error' : 'alert-success']"
      >
        <div class="alert-icon">
          <span v-if="!isError">✓</span>
          <span v-else>✕</span>
        </div>
        <div class="alert-content">
          {{ message }}
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  message: String,
  isError: Boolean,
  visible: Boolean
})

const emit = defineEmits(['hide'])

watch(() => props.visible, (newVal) => {
  if (newVal) {
    setTimeout(() => {
      emit('hide')
    }, 4000)
  }
})
</script>

<style scoped>
.alert-container {
  position: fixed;
  top: -250px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  animation: slideInTop 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.alert-backdrop {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  filter: blur(10px);
  z-index: -1;
}

.alert-message {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2);
  min-width: 300px;
  max-width: 450px;
  font-weight: 500;
  color: #fff;
}

.alert-success {
  background: linear-gradient(135deg, rgba(76, 175, 80, 0.9), rgba(139, 195, 74, 0.8));
  border-color: rgba(76, 175, 80, 0.5);
}

.alert-error {
  background: linear-gradient(135deg, rgba(244, 67, 54, 0.9), rgba(233, 30, 99, 0.8));
  border-color: rgba(244, 67, 54, 0.5);
}

.alert-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  font-size: 14px;
  font-weight: bold;
  flex-shrink: 0;
}

.alert-content {
  flex: 1;
  font-size: 14px;
  line-height: 1.4;
}


.alert-enter-active,
.alert-leave-active {
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.alert-enter-from {
  opacity: 0;
  transform: translateX(-50%) translateY(-100%) scale(0.9);
}

.alert-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-100%) scale(0.9);
}

@keyframes slideInTop {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-100%) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0) scale(1);
  }
}

@media (max-width: 768px) {
  .alert-container {
    left: 20px;
    right: 20px;
    transform: none;
  }
  
  .alert-message {
    min-width: auto;
    max-width: none;
  }
}
</style>