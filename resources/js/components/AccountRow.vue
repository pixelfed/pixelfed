<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import Avatar from '@/components/Avatar.vue'

withDefaults(
  defineProps<{
    name: string
    avatar?: string | null
    sub?: string
    to?: RouteLocationRaw | null
    size?: number
  }>(),
  {
    avatar: null,
    sub: '',
    to: null,
    size: 36,
  },
)
</script>

<template>
  <div class="flex min-h-[52px] items-center gap-3">
    <Avatar :src="avatar" :name="name" :size="size" />
    <div class="flex min-w-0 flex-1 flex-col">
      <RouterLink v-if="to" :to="to" class="truncate font-semibold">{{ name }}</RouterLink>
      <span v-else class="truncate font-semibold">{{ name }}</span>
      <span v-if="sub" class="truncate text-mute" :class="size >= 44 ? 'text-small' : 'text-caption'">{{ sub }}</span>
    </div>
    <slot />
  </div>
</template>
