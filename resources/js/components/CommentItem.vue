<script setup lang="ts">
import { ref } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import Avatar from '@/components/Avatar.vue'
import { useFavourite } from '@/composables/useStatusActions'
import type { Status } from '@/types/pixelfed'
import { pluralize, timeAgo } from '@/utils/format'

withDefaults(
  defineProps<{
    comment: Status
    replies?: Status[]
    canInteract?: boolean
  }>(),
  {
    replies: () => [],
    canInteract: true,
  },
)

const emit = defineEmits<{
  reply: [comment: Status]
}>()

const like = useFavourite()
const expanded = ref(false)
</script>

<template>
  <div class="flex items-start gap-3">
    <RouterLink
      :to="{ name: 'profile', params: { username: comment.account.acct } }"
      :aria-label="comment.account.acct"
    >
      <Avatar :src="comment.account.avatar" :name="comment.account.username" :size="32" />
    </RouterLink>

    <div class="flex min-w-0 flex-1 flex-col gap-1">
      <p class="leading-[1.45] break-words whitespace-pre-line">
        <RouterLink :to="{ name: 'profile', params: { username: comment.account.acct } }" class="font-semibold">
          {{ comment.account.acct }}
        </RouterLink>
        {{ comment.content_text }}
      </p>
      <div class="flex gap-3.5 text-caption text-mute">
        <time :datetime="comment.created_at">{{ timeAgo(comment.created_at, 'short') }}</time>
        <span v-if="comment.favourites_count > 0">{{ pluralize(comment.favourites_count, 'like', 'likes') }}</span>
        <button v-if="canInteract" type="button" class="font-semibold" @click="emit('reply', comment)">Reply</button>
      </div>

      <button
        v-if="replies.length"
        type="button"
        class="flex min-h-7 items-center gap-2.5 text-caption font-semibold text-mute"
        :aria-expanded="expanded"
        @click="expanded = !expanded"
      >
        <span class="h-px w-6 bg-edge" />
        {{ expanded ? 'Hide replies' : `View ${pluralize(replies.length, 'reply', 'replies')}` }}
      </button>

      <div v-if="expanded" class="flex flex-col gap-4 pt-2">
        <CommentItem
          v-for="reply in replies"
          :key="reply.id"
          :comment="reply"
          :can-interact="canInteract"
          @reply="emit('reply', $event)"
        />
      </div>
    </div>

    <button
      v-if="canInteract"
      type="button"
      :aria-label="comment.favourited ? 'Unlike comment' : 'Like comment'"
      :aria-pressed="comment.favourited"
      class="-mt-2.5 -mr-3 flex size-touch shrink-0 items-center justify-center"
      :class="comment.favourited ? 'text-like' : ''"
      @click="like.mutate(comment)"
    >
      <AppIcon :name="comment.favourited ? 'heart' : 'heart-outline'" :size="16" />
    </button>
  </div>
</template>
