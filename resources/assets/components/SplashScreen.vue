<template>
  <div class="splash-screen" :class="{ 'fade-out': fadeOut }">
    <img src="/img/pixelfed-icon-white.svg" alt="Pixelfed Logo" class="logo">
  </div>
</template>

<script>
export default {
  data() {
    return {
      fadeOut: false
    }
  },
  mounted() {
    setTimeout(() => {
      this.fadeOut = true
    }, 2000)
  }
}
</script>

<style scoped>
.splash-screen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: black;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  transition: opacity 1s ease-out;
}

.logo {
  max-width: 200px;
  max-height: 200px;
}

.fade-out {
  opacity: 0;
  pointer-events: none;
}
</style>
