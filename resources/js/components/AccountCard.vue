<script setup lang="ts">
import Avatar from '@/components/Avatar.vue'
import FollowButton from '@/components/FollowButton.vue'
import type { Account } from '@/types/pixelfed'
import { pluralize } from '@/utils/format'

defineProps<{
  account: Account
}>()
</script>

<template>
  <div
    class="flex min-w-0 flex-1 basis-[150px] flex-col items-center gap-2.5 rounded-2xl bg-tile px-3 pt-5 pb-2 text-center"
  >
    <Avatar :src="account.avatar" :name="account.username" :size="64" />
    <div class="flex max-w-full flex-col gap-0.5">
      <RouterLink :to="{ name: 'profile', params: { username: account.acct } }" class="truncate font-semibold">
        {{ account.acct }}
      </RouterLink>
      <span class="text-caption text-mute">{{ pluralize(account.followers_count, 'follower', 'followers') }}</span>
    </div>
    <FollowButton :account="account" variant="text" />
  </div>
</template>
