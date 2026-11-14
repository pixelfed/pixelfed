<script setup lang="ts">
import { computed } from 'vue'
import AvatarPair from '@/components/AvatarPair.vue'
import FollowButton from '@/components/FollowButton.vue'
import PostLink from '@/components/PostLink.vue'
import { formatCount, timeAgo } from '@/utils/format'
import { isVideo } from '@/utils/media'
import type { NotificationEntry } from '@/utils/notifications'

const props = withDefaults(
  defineProps<{
    entry: NotificationEntry
    variant?: 'rail' | 'dock'
  }>(),
  {
    variant: 'rail',
  },
)

const first = computed(() => props.entry.accounts[0])
const second = computed(() => props.entry.accounts[1])
const others = computed(() => props.entry.total - Math.min(2, props.entry.accounts.length))
const media = computed(() => props.entry.status?.media_attachments?.[0])
const thumb = computed(() => (media.value ? media.value.preview_url || media.value.url : null))
const noun = computed(() => {
  if (!media.value) {
    return 'post'
  }
  return isVideo(media.value) ? 'video' : 'photo'
})
const excerpt = computed(() => (props.entry.status?.content_text ?? '').trim())

const message = computed(() => {
  switch (props.entry.type) {
    case 'favourite':
      return `liked your ${noun.value}.`
    case 'share':
    case 'reblog':
      return `shared your ${noun.value}.`
    case 'comment':
      return excerpt.value ? `commented: ${excerpt.value}` : 'commented on your post.'
    case 'mention':
      return excerpt.value ? `mentioned you: ${excerpt.value}` : 'mentioned you.'
    case 'follow':
      return 'started following you.'
    case 'follow_request':
      return 'asked to follow you.'
    case 'direct':
      return 'sent you a message.'
    case 'tagged':
      return 'tagged you in a post.'
    case 'story:react':
      return 'reacted to your story.'
    case 'story:comment':
      return 'replied to your story.'
    default:
      return 'sent you a notification.'
  }
})

function profile(acct: string) {
  return { name: 'profile', params: { username: acct } }
}

interface Part {
  text: string
  to?: ReturnType<typeof profile>
}

const parts = computed<Part[]>(() => {
  const out: Part[] = [{ text: first.value.acct, to: profile(first.value.acct) }]

  if (second.value) {
    out.push({ text: others.value > 0 ? ', ' : ' and ' })
    out.push({ text: second.value.acct, to: profile(second.value.acct) })
  }

  if (others.value > 0) {
    out.push({ text: ` and ${formatCount(others.value)} ${others.value === 1 ? 'other' : 'others'}` })
  }

  out.push({ text: ` ${message.value} ` })

  return out
})
</script>

<template>
  <div class="flex items-center gap-3.5 py-1.5" :class="variant === 'dock' ? 'min-h-[72px]' : 'min-h-[60px]'">
    <RouterLink :to="profile(first.acct)" :aria-label="first.acct">
      <AvatarPair :accounts="entry.accounts" :size="variant === 'dock' ? 52 : 44" />
    </RouterLink>

    <p class="line-clamp-3 min-w-0 flex-1 text-pretty wrap-anywhere" :class="variant === 'dock' ? 'text-[15px]' : ''">
      <template v-for="(part, index) in parts" :key="index">
        <RouterLink v-if="part.to" :to="part.to" class="font-semibold">{{ part.text }}</RouterLink>
        <template v-else>{{ part.text }}</template>
      </template>
      <span class="whitespace-nowrap text-mute">{{ timeAgo(entry.createdAt, 'short') }}</span>
    </p>

    <FollowButton v-if="entry.type === 'follow'" :account="first" variant="text" class="shrink-0 pl-2" />

    <RouterLink
      v-else-if="entry.type === 'direct'"
      :to="{ name: 'messages', params: { id: entry.conversationId } }"
      class="flex min-h-touch shrink-0 items-center pl-2 text-small font-semibold text-accent"
    >
      Open
    </RouterLink>

    <PostLink
      v-else-if="entry.status"
      :status="entry.status"
      class="shrink-0"
      :class="thumb ? '' : 'flex min-h-touch items-center pl-2 text-small font-semibold text-accent'"
      :aria-label="thumb ? 'Open post' : undefined"
    >
      <img
        v-if="thumb"
        :src="thumb"
        alt=""
        loading="lazy"
        class="block bg-tile object-cover"
        :class="variant === 'dock' ? 'size-14 rounded-sm' : 'size-11 rounded-md'"
      />
      <template v-else>View</template>
    </PostLink>
  </div>
</template>
