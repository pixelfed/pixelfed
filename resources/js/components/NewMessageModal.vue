<script setup lang="ts">
import { computed, ref } from 'vue'
import AccountRow from '@/components/AccountRow.vue'
import IconButton from '@/components/IconButton.vue'
import ModalShell from '@/components/ModalShell.vue'
import SearchField from '@/components/SearchField.vue'
import { useStartConversation } from '@/composables/useDirect'
import { useSearch } from '@/composables/useDiscover'
import { useSession } from '@/composables/useSession'
import { getErrorMessage } from '@/lib/http'
import type { Account } from '@/types/pixelfed'

const emit = defineEmits<{
  close: []
  started: [id: string]
}>()

const text = ref('')
const { user } = useSession()
const results = useSearch(text)
const start = useStartConversation()

const accounts = computed(() =>
  text.value.trim() ? (results.data.value?.accounts ?? []).filter((account) => account.id !== user.value?.id) : [],
)
const failure = computed(() => (start.isError.value ? getErrorMessage(start.error.value) : ''))

function pick(account: Account) {
  if (start.isPending.value) {
    return
  }
  start.mutate([account.id], {
    onSuccess: (conversation) => emit('started', conversation.id),
  })
}
</script>

<template>
  <ModalShell label="New message" @close="emit('close')">
    <div class="flex max-h-full min-h-[420px] w-full flex-col overflow-hidden rounded-tile bg-surface">
      <header class="flex h-16 shrink-0 items-center justify-between border-b border-line pr-3 pl-7">
        <h2 class="font-display text-title-sm">New message</h2>
        <IconButton icon="close" label="Close" @click="emit('close')" />
      </header>

      <div class="shrink-0 px-5 pt-4 pb-2">
        <SearchField v-model="text" placeholder="Search people" />
      </div>

      <p v-if="failure" class="px-7 text-caption text-like" role="alert">{{ failure }}</p>

      <div class="flex min-h-0 flex-1 flex-col overflow-y-auto px-4 pb-4">
        <p v-if="!text.trim()" class="px-3 py-6 text-mute">Search for someone to message.</p>

        <p v-else-if="results.isPending.value" class="px-3 py-6 text-mute">Searching</p>

        <p v-else-if="accounts.length === 0" class="px-3 py-6 text-mute">No one found.</p>

        <button
          v-for="account in accounts"
          :key="account.id"
          type="button"
          class="rounded-2xl px-3 text-left hover:bg-tile disabled:opacity-50"
          :disabled="start.isPending.value"
          @click="pick(account)"
        >
          <AccountRow
            :name="account.acct"
            :avatar="account.avatar"
            :sub="account.display_name"
            :size="44"
            class="min-h-[60px]"
          />
        </button>
      </div>
    </div>
  </ModalShell>
</template>
