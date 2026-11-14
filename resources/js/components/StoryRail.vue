<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import Avatar from '@/components/Avatar.vue'
import { useSession } from '@/composables/useSession'
import { useStories } from '@/composables/useStories'
import type { StoryNode } from '@/types/stories'

const emit = defineEmits<{
  open: [node: StoryNode]
  create: []
}>()

const { user } = useSession()
const stories = useStories()

const nodes = computed(() => stories.data.value ?? [])
const own = computed(() => nodes.value.find((node) => node.is_author) ?? null)
const others = computed(() => nodes.value.filter((node) => !node.is_author))
</script>

<template>
  <section
    aria-label="Stories"
    class="flex [scrollbar-width:none] gap-0.5 overflow-x-auto border-b border-line pt-2 pb-4"
  >
    <button
      type="button"
      class="flex w-[76px] shrink-0 flex-col items-center gap-1.5 text-micro"
      @click="own ? emit('open', own) : emit('create')"
    >
      <span
        class="relative flex size-[68px] items-center justify-center rounded-full border-2"
        :class="own && !own.hasViewed ? 'border-accent' : 'border-transparent'"
      >
        <Avatar :src="user?.avatar" :name="user?.username" :size="56" />
        <span
          v-if="!own"
          class="absolute right-0.5 bottom-0.5 flex size-[22px] items-center justify-center rounded-full bg-accent text-on-accent ring-2 ring-bg"
        >
          <AppIcon name="add" :size="14" />
        </span>
      </span>
      <span class="max-w-[72px] truncate text-mute">Your story</span>
    </button>

    <button
      v-for="node in others"
      :key="node.id"
      type="button"
      class="flex w-[76px] shrink-0 flex-col items-center gap-1.5 text-micro"
      @click="emit('open', node)"
    >
      <span
        class="flex size-[68px] items-center justify-center rounded-full"
        :class="node.hasViewed ? 'border-[1.5px] border-edge' : 'border-2 border-accent'"
      >
        <Avatar :src="node.avatar" :name="node.username" :size="56" />
      </span>
      <span class="max-w-[72px] truncate" :class="node.hasViewed ? 'text-mute' : ''">{{ node.username }}</span>
    </button>
  </section>
</template>
