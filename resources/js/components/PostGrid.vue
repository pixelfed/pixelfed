<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import PostLink from '@/components/PostLink.vue'
import type { Status } from '@/types/pixelfed'
import { feedAspect, isVideo } from '@/utils/media'

const props = withDefaults(
  defineProps<{
    statuses: Status[]
    variant?: 'square' | 'masonry'
    feature?: boolean
    columns?: number
  }>(),
  {
    variant: 'square',
    feature: false,
    columns: 3,
  },
)

interface Tile {
  key: string
  item: Status
  src: string
  alt: string
  aspect: number
  album: boolean
  video: boolean
}

const tiles = computed<Tile[]>(() =>
  props.statuses.flatMap((item) => {
    const status = item.reblog ?? item
    const media = status.media_attachments?.[0]
    if (!media) {
      return []
    }
    return [
      {
        key: item.id,
        item,
        src: media.preview_url || media.url,
        alt: media.description ?? '',
        aspect: feedAspect(media),
        album: (status.media_attachments?.length ?? 0) > 1,
        video: isVideo(media),
      },
    ]
  }),
)

const stacks = computed(() => {
  const out: Tile[][] = Array.from({ length: props.columns }, () => [])
  const heights = out.map(() => 0)
  for (const tile of tiles.value) {
    const target = heights.indexOf(Math.min(...heights))
    out[target].push(tile)
    heights[target] += 1 / tile.aspect
  }
  return out
})
</script>

<template>
  <div v-if="variant === 'square'" class="grid grid-cols-3 gap-1">
    <PostLink
      v-for="(tile, index) in tiles"
      :key="tile.key"
      :status="tile.item"
      :siblings="statuses"
      class="relative block aspect-square overflow-hidden rounded-md bg-tile"
      :class="feature && index === 0 ? 'col-span-2 row-span-2' : ''"
    >
      <img :src="tile.src" :alt="tile.alt" loading="lazy" class="absolute inset-0 size-full object-cover" />
      <span v-if="tile.album || tile.video" class="absolute top-2.5 right-2.5 text-white drop-shadow">
        <AppIcon :name="tile.video ? 'play' : 'albums-outline'" :size="20" />
      </span>
    </PostLink>
  </div>

  <div v-else class="grid gap-3" :style="{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }">
    <div v-for="(stack, column) in stacks" :key="column" class="flex flex-col gap-3">
      <PostLink
        v-for="tile in stack"
        :key="tile.key"
        :status="tile.item"
        :siblings="statuses"
        class="relative block overflow-hidden rounded-sm bg-tile"
        :style="{ aspectRatio: String(tile.aspect) }"
      >
        <img :src="tile.src" :alt="tile.alt" loading="lazy" class="absolute inset-0 size-full object-cover" />
        <span v-if="tile.album || tile.video" class="absolute top-2.5 right-2.5 text-white drop-shadow">
          <AppIcon :name="tile.video ? 'play' : 'albums-outline'" :size="20" />
        </span>
      </PostLink>
    </div>
  </div>
</template>
