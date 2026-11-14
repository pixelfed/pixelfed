<script setup lang="ts">
import { computed, ref } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import ConversationRow from '@/components/ConversationRow.vue'
import IconButton from '@/components/IconButton.vue'
import { useConversations } from '@/composables/useDirect'
import { useInfiniteScroll } from '@/composables/useInfiniteScroll'
import { conversationTitle } from '@/utils/direct'

const props = withDefaults(
  defineProps<{
    activeId?: string
    variant?: 'rail' | 'dock'
  }>(),
  {
    activeId: '',
    variant: 'rail',
  },
)

const emit = defineEmits<{
  compose: []
}>()

const filter = ref('')
const sentinel = ref<HTMLElement | null>(null)
const query = useConversations()

const conversations = computed(() => query.data.value?.pages.flatMap((page) => page.data) ?? [])
const visible = computed(() => {
  const term = filter.value.trim().toLowerCase()
  if (!term) {
    return conversations.value
  }
  return conversations.value.filter((conversation) => conversationTitle(conversation).toLowerCase().includes(term))
})

useInfiniteScroll(
  sentinel,
  () => {
    if (query.hasNextPage.value && !query.isFetchingNextPage.value) {
      query.fetchNextPage()
    }
  },
  '200px',
)
</script>

<template>
  <section aria-label="Conversations" class="flex min-h-0 min-w-0 flex-col" :class="variant === 'dock' ? 'gap-3' : ''">
    <header v-if="variant === 'rail'" class="flex h-16 shrink-0 items-center justify-between pr-3 pl-6">
      <h1 class="font-display text-title">Messages</h1>
      <IconButton icon="create-outline" label="New message" @click="emit('compose')" />
    </header>

    <div class="flex shrink-0 items-center gap-2" :class="variant === 'rail' ? 'px-4 pb-3' : ''">
      <label
        class="flex min-w-0 flex-1 items-center gap-2.5 rounded-full bg-tile"
        :class="variant === 'dock' ? 'h-[52px] px-5' : 'h-touch px-4'"
      >
        <span class="text-mute">
          <AppIcon name="search-outline" :size="variant === 'dock' ? 20 : 18" />
        </span>
        <input
          v-model="filter"
          type="search"
          placeholder="Search messages"
          aria-label="Search messages"
          class="min-w-0 flex-1 bg-transparent outline-none placeholder:text-mute [&::-webkit-search-cancel-button]:hidden"
          :class="variant === 'dock' ? 'text-[16px]' : 'text-[15px]'"
        />
      </label>
      <button
        v-if="variant === 'dock'"
        type="button"
        aria-label="New message"
        class="flex size-[52px] shrink-0 items-center justify-center rounded-full bg-ink text-bg"
        @click="emit('compose')"
      >
        <AppIcon name="create-outline" :size="22" />
      </button>
    </div>

    <div class="flex min-h-0 flex-1 flex-col gap-0.5 overflow-y-auto" :class="variant === 'rail' ? 'px-3 pb-4' : ''">
      <div
        v-if="query.isPending.value"
        class="flex animate-pulse flex-col gap-4 p-3 motion-reduce:animate-none"
        aria-hidden="true"
      >
        <div v-for="n in 4" :key="n" class="flex items-center gap-3">
          <span class="size-14 rounded-full bg-tile" />
          <span class="h-3.5 flex-1 rounded-full bg-tile" />
        </div>
      </div>

      <p v-else-if="query.isError.value" class="p-3 text-mute">Messages did not load.</p>

      <p v-else-if="visible.length === 0" class="p-3 text-mute">
        {{ filter ? 'No conversations match.' : 'No messages yet.' }}
      </p>

      <ConversationRow
        v-for="conversation in visible"
        v-else
        :key="conversation.id"
        :conversation="conversation"
        :active="conversation.id === props.activeId"
      />

      <div ref="sentinel" class="h-px shrink-0" />
    </div>
  </section>
</template>
