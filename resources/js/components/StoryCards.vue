<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import Avatar from '@/components/Avatar.vue'
import { useStories } from '@/composables/useStories'
import type { StoryNode } from '@/types/stories'

const emit = defineEmits<{
  open: [node: StoryNode]
  create: []
}>()

const stories = useStories()

const nodes = computed(() => stories.data.value ?? [])
const own = computed(() => nodes.value.find((node) => node.is_author) ?? null)
const others = computed(() => nodes.value.filter((node) => !node.is_author))

function cover(node: StoryNode): string | null {
  const photo = node.stories.find((story) => story.type === 'photo')
  return photo?.url ?? null
}
</script>

<template>
  <section aria-label="Stories" class="flex [scrollbar-width:none] gap-3 overflow-x-auto p-1">
    <button
      type="button"
      class="flex w-[calc((100%-60px)/6)] min-w-24 shrink-0 flex-col gap-2.5 text-center text-caption text-mute"
      @click="own ? emit('open', own) : emit('create')"
    >
      <span
        class="relative flex aspect-[9/14] items-center justify-center overflow-hidden rounded-2xl bg-tile"
        :class="own && !own.hasViewed ? 'outline-2 outline-offset-2 outline-accent' : ''"
      >
        <img v-if="own && cover(own)" :src="cover(own) ?? ''" alt="" class="absolute inset-0 size-full object-cover" />
        <span v-else class="flex size-9 items-center justify-center rounded-full bg-accent text-on-accent">
          <AppIcon name="add" :size="22" />
        </span>
      </span>
      <span class="truncate">Your story</span>
    </button>

    <button
      v-for="node in others"
      :key="node.id"
      type="button"
      class="flex w-[calc((100%-60px)/6)] min-w-24 shrink-0 flex-col gap-2.5 text-center text-caption"
      :class="node.hasViewed ? 'text-mute' : ''"
      @click="emit('open', node)"
    >
      <span
        class="relative flex aspect-[9/14] items-center justify-center overflow-hidden rounded-2xl bg-tile"
        :class="node.hasViewed ? '' : 'outline-2 outline-offset-2 outline-accent'"
      >
        <img
          v-if="cover(node)"
          :src="cover(node) ?? ''"
          alt=""
          loading="lazy"
          class="absolute inset-0 size-full object-cover"
        />
        <Avatar v-else :src="node.avatar" :name="node.username" :size="56" />
      </span>
      <span class="truncate">{{ node.username }}</span>
    </button>
  </section>
</template>
