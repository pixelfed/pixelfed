<script setup lang="ts">
import AccountRow from '@/components/AccountRow.vue'
import IconButton from '@/components/IconButton.vue'
import ModalShell from '@/components/ModalShell.vue'
import { useAnswerFollowRequest } from '@/composables/useNotifications'
import type { Account } from '@/types/pixelfed'

defineProps<{
  requests: Account[]
}>()

const emit = defineEmits<{
  close: []
}>()

const answer = useAnswerFollowRequest()

const button = 'inline-flex h-touch items-center justify-center rounded-full px-[18px] text-[15px] font-semibold'
</script>

<template>
  <ModalShell label="Follow requests" @close="emit('close')">
    <div class="flex max-h-full w-full flex-col overflow-hidden rounded-tile bg-surface">
      <header class="flex h-16 shrink-0 items-center justify-between border-b border-line pr-3 pl-7">
        <h2 class="font-display text-title-sm">Follow requests</h2>
        <IconButton icon="close" label="Close" @click="emit('close')" />
      </header>

      <div class="flex min-h-0 flex-col overflow-y-auto px-7 py-3">
        <p v-if="requests.length === 0" class="py-8 text-center text-mute">No requests waiting.</p>

        <AccountRow
          v-for="account in requests"
          :key="account.id"
          :name="account.acct"
          :avatar="account.avatar"
          :sub="account.display_name"
          :to="{ name: 'profile', params: { username: account.acct } }"
          :size="44"
          class="min-h-[68px]"
        >
          <button
            type="button"
            :class="[button, 'bg-ink text-bg']"
            :disabled="answer.isPending.value"
            @click="answer.mutate({ account, accept: true })"
          >
            Confirm
          </button>
          <button
            type="button"
            :class="[button, 'border-[1.5px] border-ink']"
            :disabled="answer.isPending.value"
            @click="answer.mutate({ account, accept: false })"
          >
            Delete
          </button>
        </AccountRow>
      </div>
    </div>
  </ModalShell>
</template>
