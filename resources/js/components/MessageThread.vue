<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import AvatarPair from '@/components/AvatarPair.vue'
import MessageBubble from '@/components/MessageBubble.vue'
import MessageComposer from '@/components/MessageComposer.vue'
import { useConversation, useMarkConversationRead, useMessages } from '@/composables/useDirect'
import type { DirectMessage } from '@/types/direct'
import { conversationTitle, dayLabel } from '@/utils/direct'

const props = withDefaults(
  defineProps<{
    conversationId: string
    variant?: 'rail' | 'dock'
    showBack?: boolean
  }>(),
  {
    variant: 'rail',
    showBack: false,
  },
)

interface Row {
  key: string
  stamp?: string
  message?: DirectMessage
  spaced?: boolean
}

const scroller = ref<HTMLElement | null>(null)
const content = ref<HTMLElement | null>(null)

let pinned = true
let observer: ResizeObserver | null = null

const conversation = useConversation(() => props.conversationId)
const messages = useMessages(() => props.conversationId)
const markRead = useMarkConversationRead()

const ordered = computed(() => (messages.data.value?.pages.flatMap((page) => page.data) ?? []).slice().reverse())
const newestId = computed(() => ordered.value.at(-1)?.id ?? null)
const title = computed(() => (conversation.data.value ? conversationTitle(conversation.data.value) : ''))
const participants = computed(() => conversation.data.value?.participants ?? [])
const profile = computed(() =>
  participants.value.length === 1 ? { name: 'profile', params: { username: participants.value[0].acct } } : null,
)
const subtitle = computed(() => {
  if (participants.value.length === 1) {
    return participants.value[0].display_name ?? ''
  }
  return participants.value.length > 1 ? `${participants.value.length + 1} people` : ''
})
const surface = computed(() => (props.variant === 'dock' ? 'bg' : 'tile'))

const rows = computed<Row[]>(() => {
  const out: Row[] = []
  let lastDay = ''
  let lastAuthor: boolean | null = null

  for (const message of ordered.value) {
    const day = dayLabel(message.created_at)
    if (day !== lastDay) {
      out.push({ key: `day-${message.id}`, stamp: day })
      lastDay = day
      lastAuthor = null
    }
    out.push({ key: message.id, message, spaced: lastAuthor !== null && lastAuthor !== message.is_author })
    lastAuthor = message.is_author
  }

  return out
})

function pin() {
  if (scroller.value) {
    scroller.value.scrollTop = scroller.value.scrollHeight
  }
}

function toBottom() {
  pinned = true
  nextTick(pin)
}

function onScroll() {
  const el = scroller.value
  if (el) {
    pinned = el.scrollHeight - el.scrollTop - el.clientHeight < 80
  }
}

async function loadEarlier() {
  const el = scroller.value
  const before = el?.scrollHeight ?? 0
  await messages.fetchNextPage()
  await nextTick()
  if (el) {
    el.scrollTop += el.scrollHeight - before
  }
}

watch(newestId, toBottom, { immediate: true })

onMounted(() => {
  if (content.value) {
    observer = new ResizeObserver(() => {
      if (pinned) {
        pin()
      }
    })
    observer.observe(content.value)
  }
})

onBeforeUnmount(() => observer?.disconnect())

watch(
  [() => props.conversationId, newestId],
  ([id, newest]) => {
    if (id && newest) {
      markRead.mutate(id)
    }
  },
  { immediate: true },
)
</script>

<template>
  <section :aria-label="title ? `Conversation with ${title}` : 'Conversation'" class="flex min-h-0 min-w-0 flex-col">
    <header
      class="flex shrink-0 items-center gap-3"
      :class="variant === 'dock' ? 'h-[72px] pr-4 pl-6' : 'h-16 border-b border-line pr-3 pl-6'"
    >
      <RouterLink
        v-if="showBack"
        :to="{ name: 'messages' }"
        aria-label="Back to messages"
        class="-ml-3.5 flex size-touch items-center justify-center rounded-full hover:bg-tile"
      >
        <AppIcon name="chevron-back" :size="24" />
      </RouterLink>
      <AvatarPair v-if="participants.length > 0" :accounts="participants" :size="variant === 'dock' ? 40 : 36" />
      <component :is="profile ? 'RouterLink' : 'div'" :to="profile ?? undefined" class="flex min-w-0 flex-1 flex-col">
        <span class="truncate font-semibold" :class="variant === 'dock' ? 'text-body-lg' : 'text-[15px]'">
          {{ title }}
        </span>
        <span v-if="subtitle" class="truncate text-caption text-mute">{{ subtitle }}</span>
      </component>
    </header>

    <div
      ref="scroller"
      class="flex min-h-0 flex-1 flex-col overflow-y-auto px-6"
      :class="variant === 'dock' ? 'pt-2 pb-4' : 'py-4'"
      @scroll.passive="onScroll"
    >
      <div class="flex-1" />

      <div ref="content" class="flex flex-col gap-1">
        <button
          v-if="messages.hasNextPage.value"
          type="button"
          class="min-h-touch self-center text-small font-semibold text-accent disabled:text-mute"
          :disabled="messages.isFetchingNextPage.value"
          @click="loadEarlier"
        >
          {{ messages.isFetchingNextPage.value ? 'Loading' : 'Load earlier messages' }}
        </button>

        <p v-if="messages.isPending.value" class="self-center py-6 text-mute">Loading messages</p>

        <p v-else-if="messages.isError.value" class="self-center py-6 text-mute">Messages did not load.</p>

        <p v-else-if="ordered.length === 0" class="self-center py-6 text-mute">No messages yet. Say hello.</p>

        <template v-for="row in rows" :key="row.key">
          <p v-if="row.stamp" class="self-center pt-3 pb-2 text-caption text-mute">{{ row.stamp }}</p>
          <MessageBubble
            v-else-if="row.message"
            :message="row.message"
            :surface="surface"
            :class="row.spaced ? 'mt-2' : ''"
          />
        </template>
      </div>
    </div>

    <MessageComposer
      :conversation-id="conversationId"
      :surface="surface"
      :class="variant === 'dock' ? 'px-4 pb-4' : 'px-6 pb-6'"
    />
  </section>
</template>
