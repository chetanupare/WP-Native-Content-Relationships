<script setup>
import DefaultTheme from 'vitepress/theme'
import { useData } from 'vitepress'
import Giscus from './components/Giscus.vue'

const { page } = useData()
</script>

<template>
  <DefaultTheme.Layout>
    <template #doc-after>
      <Giscus :key="page.relativePath" />
    </template>
  </DefaultTheme.Layout>
</template>
