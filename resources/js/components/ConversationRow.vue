<script setup lang="ts">
import { computed } from 'vue'
import AvatarPair from '@/components/AvatarPair.vue'
import type { Conversation } from '@/types/direct'
import { conversationTitle, messagePreview } from '@/utils/direct'
import { timeAgo } from '@/utils/format'

const props = withDefaults(
  defineProps<{
    conversation: Conversation
    active?: boolean
  }>(),
  {
    active: false,
  },
)

const unread = computed(() => props.conversation.unread_count > 0)
const stamp = computed(() =>
  timeAgo(props.conversation.last_message?.created_at ?? props.conversation.updated_at, 'short'),
)
</script>

<template>
  <RouterLink
    :to="{ name: 'messages', params: { id: conversation.id } }"
    :aria-current="active ? 'true' : undefined"
    class="flex min-h-[72px] items-center gap-3 rounded-2xl px-3 py-2 hover:bg-tile"
    :class="active ? 'bg-tile' : ''"
  >
    <AvatarPair :accounts="conversation.participants" :size="56" />
    <span class="flex min-w-0 flex-1 flex-col">
      <span class="truncate text-[15px]" :class="unread || active ? 'font-semibold' : ''">
        {{ conversationTitle(conversation) }}
      </span>
      <span class="flex min-w-0 gap-1 text-small" :class="unread ? 'font-semibold' : 'text-mute'">
        <span class="truncate">{{ messagePreview(conversation.last_message) }}</span>
        <span v-if="stamp" class="shrink-0">· {{ stamp }}</span>
      </span>
    </span>
    <span v-if="unread" role="img" aria-label="Unread" class="size-2.5 shrink-0 rounded-full bg-accent" />
  </RouterLink>
</template>
