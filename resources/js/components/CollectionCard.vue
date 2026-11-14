<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue'
import type { Collection } from '@/types/pixelfed'
import { pluralize } from '@/utils/format'

withDefaults(
  defineProps<{
    collection: Collection
    variant?: 'rail' | 'dock'
  }>(),
  {
    variant: 'rail',
  },
)
</script>

<template>
  <RouterLink :to="{ name: 'collection', params: { id: collection.id } }" class="flex min-w-0 flex-col gap-2.5">
    <span
      class="relative block overflow-hidden bg-tile"
      :class="variant === 'dock' ? 'aspect-[4/5] rounded-tile' : 'aspect-square rounded-md'"
    >
      <img :src="collection.thumb" alt="" loading="lazy" class="absolute inset-0 size-full object-cover" />
      <span class="absolute top-3 right-3 text-white drop-shadow">
        <AppIcon name="albums-outline" :size="20" />
      </span>
    </span>
    <span class="flex min-w-0 flex-col gap-0.5 px-0.5">
      <span class="truncate font-semibold" :class="variant === 'dock' ? 'text-body-lg' : 'text-[15px]'">
        {{ collection.title || 'Untitled' }}
      </span>
      <span class="text-caption text-mute">{{ pluralize(collection.post_count ?? 0, 'post', 'posts') }}</span>
    </span>
  </RouterLink>
</template>
