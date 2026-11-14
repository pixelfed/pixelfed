<script setup lang="ts">
import { computed, ref } from 'vue'
import CollectionCard from '@/components/CollectionCard.vue'
import { useAccountCollections } from '@/composables/useCollections'
import { useInfiniteScroll } from '@/composables/useInfiniteScroll'

const props = withDefaults(
  defineProps<{
    accountId: string
    variant?: 'rail' | 'dock'
  }>(),
  {
    variant: 'rail',
  },
)

const sentinel = ref<HTMLElement | null>(null)
const query = useAccountCollections(() => props.accountId)

const collections = computed(() => query.data.value?.pages.flat() ?? [])

useInfiniteScroll(sentinel, () => {
  if (query.hasNextPage.value && !query.isFetchingNextPage.value) {
    query.fetchNextPage()
  }
})
</script>

<template>
  <div
    v-if="query.isPending.value"
    class="grid animate-pulse grid-cols-3 gap-4 pt-1 motion-reduce:animate-none"
    aria-hidden="true"
  >
    <span v-for="n in 3" :key="n" class="aspect-square rounded-md bg-tile" />
  </div>

  <p v-else-if="query.isError.value" class="py-10 text-center text-mute">Collections did not load.</p>

  <p v-else-if="collections.length === 0" class="py-10 text-center text-mute">No collections yet.</p>

  <div
    v-else
    class="grid gap-x-4 pt-1"
    :class="
      variant === 'dock'
        ? 'grid-cols-[repeat(auto-fill,minmax(min(260px,100%),1fr))] gap-y-8'
        : 'grid-cols-[repeat(auto-fill,minmax(min(280px,100%),1fr))] gap-y-7'
    "
  >
    <CollectionCard
      v-for="collection in collections"
      :key="collection.id"
      :collection="collection"
      :variant="variant"
    />
  </div>

  <div ref="sentinel" class="h-px" />
</template>
