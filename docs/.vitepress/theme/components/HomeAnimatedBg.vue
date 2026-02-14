<script setup>
import { computed } from 'vue'
import { useData } from 'vitepress'

const { page } = useData()
const isHome = computed(() => {
  const p = page.value?.relativePath ?? ''
  return p === '' || p === 'index' || p === 'index.md'
})
</script>

<template>
  <Teleport to="body">
    <div v-if="isHome" class="home-animated-bg" aria-hidden="true">
      <div class="home-animated-bg__orb home-animated-bg__orb--1" />
      <div class="home-animated-bg__orb home-animated-bg__orb--2" />
      <div class="home-animated-bg__orb home-animated-bg__orb--3" />
      <div class="home-animated-bg__orb home-animated-bg__orb--4" />
    </div>
  </Teleport>
</template>

<style scoped>
.home-animated-bg {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 85vh;
  max-height: 900px;
  pointer-events: none;
  z-index: -1;
  overflow: hidden;
}

.home-animated-bg__orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  will-change: transform;
}

/* Brand-colored orbs — "relationship nodes" */
.home-animated-bg__orb--1 {
  width: min(400px, 60vw);
  height: min(400px, 60vw);
  background: rgba(13, 148, 136, 0.18);
  top: 10%;
  left: 5%;
  animation: orbFloat1 20s ease-in-out infinite;
}

.home-animated-bg__orb--2 {
  width: min(320px, 50vw);
  height: min(320px, 50vw);
  background: rgba(20, 184, 166, 0.14);
  top: 25%;
  right: 10%;
  animation: orbFloat2 22s ease-in-out infinite;
}

.home-animated-bg__orb--3 {
  width: min(280px, 45vw);
  height: min(280px, 45vw);
  background: rgba(15, 118, 110, 0.12);
  bottom: 20%;
  left: 15%;
  animation: orbFloat3 18s ease-in-out infinite;
}

.home-animated-bg__orb--4 {
  width: min(240px, 40vw);
  height: min(240px, 40vw);
  background: rgba(13, 148, 136, 0.1);
  bottom: 30%;
  right: 20%;
  animation: orbFloat2 24s ease-in-out infinite reverse;
}

.dark .home-animated-bg__orb--1 { background: rgba(45, 212, 191, 0.12); }
.dark .home-animated-bg__orb--2 { background: rgba(20, 184, 166, 0.1); }
.dark .home-animated-bg__orb--3 { background: rgba(94, 234, 212, 0.08); }
.dark .home-animated-bg__orb--4 { background: rgba(45, 212, 191, 0.06); }

@keyframes orbFloat1 {
  0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.9; }
  25%      { transform: translate(8%, -12%) scale(1.05); opacity: 1; }
  50%      { transform: translate(-5%, 5%) scale(0.95); opacity: 0.85; }
  75%      { transform: translate(-10%, -8%) scale(1.02); opacity: 0.95; }
}

@keyframes orbFloat2 {
  0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.9; }
  33%      { transform: translate(-8%, 10%) scale(1.08); opacity: 1; }
  66%      { transform: translate(12%, 5%) scale(0.92); opacity: 0.85; }
}

@keyframes orbFloat3 {
  0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.9; }
  50%      { transform: translate(-6%, -10%) scale(1.1); opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
  .home-animated-bg__orb {
    animation: none;
  }
}
</style>
