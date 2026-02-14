<script setup>
import { onMounted, ref, watch } from 'vue'
import { useData } from 'vitepress'

const { theme, page } = useData()
const container = ref(null)

function loadGiscus() {
  const giscus = theme.value?.giscus
  if (!giscus?.repoId || !giscus?.categoryId) return

  const script = document.createElement('script')
  script.src = 'https://giscus.app/client.js'
  script.setAttribute('data-repo', giscus.repo || '')
  script.setAttribute('data-repo-id', giscus.repoId)
  script.setAttribute('data-category', giscus.category || 'Announcements')
  script.setAttribute('data-category-id', giscus.categoryId)
  script.setAttribute('data-mapping', giscus.mapping || 'pathname')
  script.setAttribute('data-strict', giscus.strict !== false ? '0' : '1')
  script.setAttribute('data-reactions-enabled', giscus.reactionsEnabled !== false ? '1' : '0')
  script.setAttribute('data-emit-metadata', '0')
  script.setAttribute('data-input-position', giscus.inputPosition || 'bottom')
  script.setAttribute('data-theme', giscus.theme || 'preferred_color_scheme')
  script.setAttribute('data-lang', giscus.lang || 'en')
  script.setAttribute('crossorigin', 'anonymous')
  script.async = true

  if (container.value) {
    container.value.innerHTML = ''
    container.value.appendChild(script)
  }
}

onMounted(loadGiscus)
watch(() => page.value.relativePath, loadGiscus)
</script>

<template>
  <div v-if="theme?.giscus?.repoId && theme?.giscus?.categoryId" class="giscus-wrapper">
    <p class="giscus-label">Comments (GitHub Discussions)</p>
    <div ref="container"></div>
  </div>
</template>

<style scoped>
.giscus-wrapper {
  margin-top: 2.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--vp-c-divider);
}

.giscus-label {
  font-size: 0.85rem;
  color: var(--vp-c-text-2);
  margin-bottom: 0.75rem;
}
</style>
