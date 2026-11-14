<script setup lang="ts">
import { computed } from 'vue'
import ActionBar from '@/components/ActionBar.vue'
import AppIcon from '@/components/AppIcon.vue'
import Avatar from '@/components/Avatar.vue'
import PostLink from '@/components/PostLink.vue'
import PostMedia from '@/components/PostMedia.vue'
import { usePostModal } from '@/composables/usePostModal'
import type { Status } from '@/types/pixelfed'
import { timeAgo } from '@/utils/format'

const props = withDefaults(
  defineProps<{
    status: Status
    siblings?: Status[]
  }>(),
  {
    siblings: () => [],
  },
)

const { open } = usePostModal()

const post = computed(() => props.status.reblog ?? props.status)
const profile = computed(() => ({ name: 'profile', params: { username: post.value.account.acct } }))
</script>

<template>
  <article class="flex flex-col gap-3">
    <PostMedia :status="post" rounded="rounded-sm" />

    <div class="flex flex-wrap items-center justify-between gap-x-4">
      <div class="flex min-h-touch min-w-0 items-center gap-2.5">
        <RouterLink :to="profile" :aria-label="post.account.acct">
          <Avatar :src="post.account.avatar" :name="post.account.username" :size="32" />
        </RouterLink>
        <RouterLink :to="profile" class="truncate font-display text-title-sm">{{ post.account.acct }}</RouterLink>
        <PostLink :status="status" :siblings="siblings" class="shrink-0 text-small text-mute">
          <time :datetime="post.created_at">{{ timeAgo(post.created_at, 'short') }}</time>
        </PostLink>
      </div>
      <ActionBar :status="post" variant="dock" @comment="open(status, siblings)" />
    </div>

    <p v-if="post.content_text" class="text-body-lg text-pretty break-words whitespace-pre-line text-ink-soft">
      {{ post.content_text }}
    </p>

    <p v-if="status.reblog" class="flex items-center gap-1.5 text-caption text-mute">
      <AppIcon name="sync" :size="14" />
      Shared by {{ status.account.acct }}
    </p>
  </article>
</template>
