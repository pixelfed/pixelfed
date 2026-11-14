<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue'
import type { IconName } from '@/utils/ionicons'

export type ProfileTab = 'posts' | 'collections' | 'shares'

const model = defineModel<ProfileTab>({ required: true })

const tabs: { key: ProfileTab; label: string; icon: IconName }[] = [
  { key: 'posts', label: 'Posts', icon: 'grid-outline' },
  { key: 'collections', label: 'Collections', icon: 'albums-outline' },
  { key: 'shares', label: 'Shares', icon: 'sync' },
]
</script>

<template>
  <div role="tablist" aria-label="Profile sections" class="flex justify-center gap-12 border-t border-line">
    <button
      v-for="tab in tabs"
      :key="tab.key"
      type="button"
      role="tab"
      :aria-selected="model === tab.key"
      class="-mt-px flex h-[52px] items-center gap-2 border-t text-small font-semibold"
      :class="model === tab.key ? 'border-ink' : 'border-transparent text-mute'"
      @click="model = tab.key"
    >
      <AppIcon :name="tab.icon" :size="16" />
      {{ tab.label }}
    </button>
  </div>
</template>
