<script setup lang="ts">
import { computed } from 'vue'
import { useFollow } from '@/composables/useFollow'
import type { Account } from '@/types/pixelfed'

const props = withDefaults(
  defineProps<{
    account: Account
    variant?: 'pill' | 'text'
  }>(),
  {
    variant: 'text',
  },
)

const { stateOf, labelOf, toggle } = useFollow()

const active = computed(() => stateOf(props.account.id) !== 'none')
</script>

<template>
  <button
    v-if="variant === 'pill'"
    type="button"
    class="inline-flex h-touch items-center justify-center rounded-full px-[18px] text-[15px] font-semibold"
    :class="active ? 'border-[1.5px] border-ink' : 'bg-ink text-bg'"
    :aria-pressed="active"
    @click="toggle(account)"
  >
    {{ labelOf(account.id) }}
  </button>
  <button
    v-else
    type="button"
    class="min-h-touch text-small font-semibold"
    :class="active ? 'text-mute' : 'text-accent'"
    :aria-pressed="active"
    @click="toggle(account)"
  >
    {{ labelOf(account.id) }}
  </button>
</template>
