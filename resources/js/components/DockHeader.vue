<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import markUrl from '@/assets/pixelfed-mark.png'
import AppIcon from '@/components/AppIcon.vue'
import FeedSwitcher from '@/components/FeedSwitcher.vue'

const route = useRoute()

const isHome = computed(() => route.name === 'home')
const onMessages = computed(() => route.name === 'messages')
const title = computed(() => (typeof route.meta.title === 'string' ? route.meta.title : ''))
</script>

<template>
  <RouterLink to="/" aria-label="Pixelfed home" class="-ml-[7px] flex size-touch items-center justify-center">
    <img :src="markUrl" alt="" class="size-[30px] dark:invert" />
  </RouterLink>

  <FeedSwitcher v-if="isHome" size="sm" align="center" />
  <span v-else class="font-display text-title-sm">{{ title }}</span>

  <RouterLink
    to="/messages"
    aria-label="Messages"
    :aria-current="onMessages ? 'page' : undefined"
    class="-mr-2.5 flex size-touch items-center justify-center rounded-full hover:bg-tile aria-[current=page]:bg-screen"
  >
    <AppIcon :name="onMessages ? 'paper-plane' : 'paper-plane-outline'" :size="26" />
  </RouterLink>
</template>
