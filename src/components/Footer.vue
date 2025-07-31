<template>
  <footer class="footer">
    <div class="footer-content">
      <p class="copyright-text">© {{ currentYear }} RMS Server. All Rights Reserved.</p>
    </div>
  </footer>
</template>

<script setup>
const currentYear = new Date().getFullYear()
</script>

<style scoped>
.footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  width: 100%;
  padding: 12px 0;
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  background: rgba(255, 255, 255, 0.15);
  border-top: 1px solid rgba(255, 255, 255, 0.25);
  box-shadow: 0 -2px 20px rgba(31, 38, 135, 0.15);
  z-index: 100;
}

.footer-content {
  display: flex;
  justify-content: center;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.copyright-text {
  margin: 0;
  color: #333;
  font-weight: 500;
  text-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
  font-size: 0.9em;
}

@media (max-width: 768px) {
  .copyright-text {
    font-size: 0.85em;
  }
}

body.easter-egg-active .footer {
  background: rgba(230, 190, 255, 0.25) !important;
  border-top-color: rgba(147, 112, 219, 0.3) !important;
}

body.easter-egg-active .copyright-text {
  color: #8b4f9e;
  text-shadow: 0 0 10px rgba(147, 112, 219, 0.8);
}
</style>