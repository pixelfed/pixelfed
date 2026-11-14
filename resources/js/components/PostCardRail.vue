<script setup lang="ts">
import { computed } from 'vue'
import ActionBar from '@/components/ActionBar.vue'
import AppIcon from '@/components/AppIcon.vue'
import Avatar from '@/components/Avatar.vue'
import PostLink from '@/components/PostLink.vue'
import PostMedia from '@/components/PostMedia.vue'
import PostMenu from '@/components/PostMenu.vue'
import { usePostModal } from '@/composables/usePostModal'
import type { Status } from '@/types/pixelfed'
import { pluralize, timeAgo } from '@/utils/format'

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
const place = computed(() =>
  post.value.place ? [post.value.place.name, post.value.place.country].filter(Boolean).join(', ') : '',
)
const replies = computed(() => post.value.reply_count ?? 0)
</script>

<template>
  <article class="flex flex-col border-b border-line pt-3 pb-5">
    <p v-if="status.reblog" class="flex items-center gap-1.5 pt-1 text-caption text-mute">
      <AppIcon name="sync" :size="14" />
      Shared by {{ status.account.acct }}
    </p>

    <header class="flex h-14 items-center gap-2.5">
      <RouterLink :to="profile" :aria-label="post.account.acct">
        <Avatar :src="post.account.avatar" :name="post.account.username" :size="32" />
      </RouterLink>
      <div class="flex min-w-0 flex-1 flex-col justify-center">
        <RouterLink :to="profile" class="truncate leading-[18px] font-semibold">{{ post.account.acct }}</RouterLink>
        <span v-if="place" class="truncate text-caption text-mute">{{ place }}</span>
      </div>
      <div class="-mr-3 flex">
        <PostMenu :status="status" />
      </div>
    </header>

    <PostMedia :status="post" rounded="rounded-md" />

    <ActionBar :status="post" variant="rail" @comment="open(status, siblings)" />

    <p v-if="post.favourites_count > 0" class="font-semibold">
      {{ pluralize(post.favourites_count, 'like', 'likes') }}
    </p>

    <p v-if="post.content_text" class="mt-1 break-words whitespace-pre-line">
      <RouterLink :to="profile" class="font-semibold">{{ post.account.acct }}</RouterLink>
      {{ post.content_text }}
    </p>

    <PostLink v-if="replies > 0" :status="status" :siblings="siblings" class="mt-1 text-mute">
      {{ replies === 1 ? 'View 1 comment' : `View all ${pluralize(replies, 'comment', 'comments')}` }}
    </PostLink>

    <PostLink :status="status" :siblings="siblings" class="mt-1.5 text-micro text-mute uppercase">
      <time :datetime="post.created_at">{{ timeAgo(post.created_at, 'long') }}</time>
    </PostLink>
  </article>
</template>
