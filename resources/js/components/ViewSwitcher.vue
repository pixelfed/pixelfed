<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue'
import { useGridView, type GridView } from '@/composables/useGridView'
import type { IconName } from '@/utils/ionicons'

withDefaults(
  defineProps<{
    variant?: 'rail' | 'dock'
  }>(),
  {
    variant: 'rail',
  },
)

const OPTIONS: { key: GridView; label: string; icon: IconName }[] = [
  { key: 'grid', label: 'Grid', icon: 'grid-outline' },
  { key: 'feed', label: 'Feed', icon: 'view-feed' },
  { key: 'masonry', label: 'Masonry', icon: 'view-masonry' },
]

const { view, setView } = useGridView()
</script>

<template>
  <div
    role="radiogroup"
    aria-label="View"
    class="flex shrink-0"
    :class="variant === 'dock' ? 'gap-1 rounded-full bg-tile p-1' : 'gap-0.5'"
  >
    <button
      v-for="option in OPTIONS"
      :key="option.key"
      type="button"
      role="radio"
      :aria-checked="view === option.key"
      :aria-label="option.label"
      class="flex size-touch items-center justify-center"
      :class="
        variant === 'dock'
          ? ['rounded-full', view === option.key ? 'bg-ink text-bg' : 'hover:bg-screen']
          : ['rounded-xl', view === option.key ? 'bg-tile' : 'text-mute hover:bg-tile']
      "
      @click="setView(option.key)"
    >
      <AppIcon :name="option.icon" :size="20" />
    </button>
  </div>
</template>
