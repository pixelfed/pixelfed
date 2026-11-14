<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue'
import IconButton from '@/components/IconButton.vue'
import PostMenu from '@/components/PostMenu.vue'
import { useBookmark, useFavourite, useReblog } from '@/composables/useStatusActions'
import type { Status } from '@/types/pixelfed'
import { formatCount } from '@/utils/format'

const props = withDefaults(
  defineProps<{
    status: Status
    variant?: 'rail' | 'dock'
  }>(),
  {
    variant: 'rail',
  },
)

const emit = defineEmits<{
  comment: []
}>()

const like = useFavourite()
const share = useReblog()
const save = useBookmark()
</script>

<template>
  <div v-if="props.variant === 'rail'" class="-mx-[9px] flex h-12 items-center gap-0.5">
    <IconButton
      :icon="status.favourited ? 'heart' : 'heart-outline'"
      :label="status.favourited ? 'Unlike' : 'Like'"
      :pressed="status.favourited"
      :tone="status.favourited ? 'like' : 'default'"
      :size="27"
      @click="like.mutate(status)"
    />
    <IconButton icon="chatbubble-outline" label="Comment" :size="25" @click="emit('comment')" />
    <IconButton
      icon="sync"
      :label="status.reblogged ? 'Undo share' : 'Share'"
      :pressed="status.reblogged"
      :size="27"
      :class="status.reblogged ? 'text-accent' : ''"
      @click="share.mutate(status)"
    />
    <span class="flex-1" />
    <IconButton
      :icon="status.bookmarked ? 'bookmark' : 'bookmark-outline'"
      :label="status.bookmarked ? 'Remove bookmark' : 'Bookmark'"
      :pressed="!!status.bookmarked"
      :size="25"
      @click="save.mutate(status)"
    />
  </div>

  <div v-else class="-mr-2.5 flex max-w-full flex-wrap items-center">
    <button
      type="button"
      :aria-label="`${status.favourited ? 'Unlike' : 'Like'}, ${formatCount(status.favourites_count)} likes`"
      :aria-pressed="status.favourited"
      class="flex min-h-touch items-center gap-1.5 rounded-full px-2 text-label hover:bg-tile"
      @click="like.mutate(status)"
    >
      <span :class="status.favourited ? 'text-like' : ''">
        <AppIcon :name="status.favourited ? 'heart' : 'heart-outline'" :size="24" />
      </span>
      {{ formatCount(status.favourites_count) }}
    </button>
    <button
      type="button"
      :aria-label="`Comment, ${formatCount(status.reply_count)} comments`"
      class="flex min-h-touch items-center gap-1.5 rounded-full px-2 text-label hover:bg-tile"
      @click="emit('comment')"
    >
      <AppIcon name="chatbubble-outline" :size="22" />
      {{ formatCount(status.reply_count) }}
    </button>
    <IconButton
      icon="sync"
      :label="status.reblogged ? 'Undo share' : 'Share'"
      :pressed="status.reblogged"
      :class="status.reblogged ? 'text-accent' : ''"
      @click="share.mutate(status)"
    />
    <IconButton
      :icon="status.bookmarked ? 'bookmark' : 'bookmark-outline'"
      :label="status.bookmarked ? 'Remove bookmark' : 'Bookmark'"
      :pressed="!!status.bookmarked"
      :size="22"
      @click="save.mutate(status)"
    />
    <PostMenu :status="status" />
  </div>
</template>
