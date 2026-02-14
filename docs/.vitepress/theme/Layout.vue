<script setup>
import DefaultTheme from 'vitepress/theme'
import { useData } from 'vitepress'
import Giscus from './components/Giscus.vue'
import HomeAnimatedBg from './components/HomeAnimatedBg.vue'

const { page } = useData()
</script>

<template>
  <ClientOnly>
    <HomeAnimatedBg />
  </ClientOnly>
  <DefaultTheme.Layout>
    <template #doc-after>
      <Giscus :key="page.relativePath" />
    </template>
  </DefaultTheme.Layout>
</template>
