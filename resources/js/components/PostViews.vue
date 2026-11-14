<script setup lang="ts">
import PostCardDock from '@/components/PostCardDock.vue'
import PostCardRail from '@/components/PostCardRail.vue'
import PostGrid from '@/components/PostGrid.vue'
import { useGridView } from '@/composables/useGridView'
import { useLayout } from '@/composables/useLayout'
import type { Status } from '@/types/pixelfed'

defineProps<{
  statuses: Status[]
}>()

const { active } = useLayout()
const { view } = useGridView()
</script>

<template>
  <div v-if="view === 'feed' && active === 'dock'" class="mx-auto flex w-full max-w-feed-dock flex-col gap-16 pt-4">
    <PostCardDock v-for="status in statuses" :key="status.id" :status="status" :siblings="statuses" />
  </div>

  <div v-else-if="view === 'feed'" class="mx-auto flex w-full max-w-feed-rail flex-col">
    <PostCardRail v-for="status in statuses" :key="status.id" :status="status" :siblings="statuses" />
  </div>

  <PostGrid v-else :statuses="statuses" :variant="view === 'masonry' ? 'masonry' : 'square'" />
</template>
