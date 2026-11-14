<script setup lang="ts">
import { computed } from 'vue'
import AccountRow from '@/components/AccountRow.vue'
import { useSession } from '@/composables/useSession'
import { useFollow } from '@/composables/useFollow'
import { useSuggestedAccounts } from '@/composables/useSuggestedAccounts'
import { pluralize } from '@/utils/format'

const props = withDefaults(
  defineProps<{
    variant?: 'list' | 'card'
    limit?: number
  }>(),
  {
    variant: 'list',
    limit: 4,
  },
)

const { user } = useSession()
const suggestions = useSuggestedAccounts()
const { stateOf, labelOf, toggle } = useFollow()

const accounts = computed(() =>
  (suggestions.data.value ?? []).filter((account) => account.id !== user.value?.id).slice(0, props.limit),
)
</script>

<template>
  <section
    v-if="accounts.length"
    aria-label="Suggested for you"
    class="flex flex-col"
    :class="variant === 'card' ? 'rounded-tile bg-tile px-6 pt-4 pb-5' : ''"
  >
    <div class="flex min-h-touch items-center justify-between">
      <h2 class="font-semibold text-mute">Suggested for you</h2>
    </div>

    <AccountRow
      v-for="account in accounts"
      :key="account.id"
      :name="account.acct"
      :avatar="account.avatar"
      :sub="pluralize(account.followers_count, 'follower', 'followers')"
      :to="{ name: 'profile', params: { username: account.acct } }"
    >
      <button
        type="button"
        class="min-h-touch pl-2 text-small font-semibold"
        :class="stateOf(account.id) === 'none' ? 'text-accent' : 'text-mute'"
        :aria-pressed="stateOf(account.id) !== 'none'"
        @click="toggle(account)"
      >
        {{ labelOf(account.id) }}
      </button>
    </AccountRow>
  </section>
</template>
