<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue'
import type { IconName } from '@/utils/ionicons'

withDefaults(
  defineProps<{
    icon: IconName
    label: string
    size?: number
    pressed?: boolean
    tone?: 'default' | 'like'
    dot?: boolean
  }>(),
  {
    size: 24,
    pressed: undefined,
    tone: 'default',
    dot: false,
  },
)
</script>

<template>
  <button
    type="button"
    :aria-label="label"
    :aria-pressed="pressed"
    class="relative flex size-touch shrink-0 items-center justify-center rounded-full hover:bg-tile"
    :class="tone === 'like' ? 'text-like' : ''"
  >
    <AppIcon :name="icon" :size="size" />
    <span v-if="dot" class="absolute top-[7px] right-[6px] size-2.5 rounded-full bg-accent ring-2 ring-bg" />
  </button>
</template>
