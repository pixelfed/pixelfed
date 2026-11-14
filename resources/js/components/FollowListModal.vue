<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import type { FollowListKind } from '@/api/follows'
import AccountRow from '@/components/AccountRow.vue'
import FollowButton from '@/components/FollowButton.vue'
import IconButton from '@/components/IconButton.vue'
import ModalShell from '@/components/ModalShell.vue'
import SegmentedTabs from '@/components/SegmentedTabs.vue'
import { useFollowList } from '@/composables/useFollowList'
import { useInfiniteScroll } from '@/composables/useInfiniteScroll'
import { useSession } from '@/composables/useSession'
import type { Account } from '@/types/pixelfed'
import { formatCount } from '@/utils/format'

const props = withDefaults(
  defineProps<{
    account: Account
    initial?: FollowListKind
  }>(),
  {
    initial: 'followers',
  },
)

const emit = defineEmits<{
  close: []
}>()

const route = useRoute()
const { user } = useSession()

const kind = ref<FollowListKind>(props.initial)
const sentinel = ref<HTMLElement | null>(null)
const scroller = ref<HTMLElement | null>(null)

const list = useFollowList(() => props.account.id, kind)

const tabs = computed<{ key: FollowListKind; label: string }[]>(() => [
  { key: 'followers', label: `${formatCount(props.account.followers_count)} followers` },
  { key: 'following', label: `${formatCount(props.account.following_count)} following` },
])
const accounts = computed(() => list.data.value?.pages.flatMap((page) => page.data) ?? [])

useInfiniteScroll(
  sentinel,
  () => {
    if (list.hasNextPage.value && !list.isFetchingNextPage.value) {
      list.fetchNextPage()
    }
  },
  '200px',
)

watch(
  () => accounts.value.length,
  async () => {
    await nextTick()
    const el = scroller.value
    if (el && el.scrollHeight <= el.clientHeight && list.hasNextPage.value && !list.isFetchingNextPage.value) {
      list.fetchNextPage()
    }
  },
)

watch(
  () => route.fullPath,
  () => emit('close'),
)
</script>

<template>
  <ModalShell :label="`${account.acct}, ${kind}`" @close="emit('close')">
    <div class="flex h-[min(640px,calc(100dvh-3rem))] w-full flex-col overflow-hidden rounded-tile bg-surface">
      <header class="flex h-16 shrink-0 items-center justify-between gap-3 pr-3 pl-7">
        <h2 class="truncate font-display text-title-sm">{{ account.acct }}</h2>
        <IconButton icon="close" label="Close" @click="emit('close')" />
      </header>

      <SegmentedTabs v-model="kind" :options="tabs" label="List" class="shrink-0 px-7" />

      <div ref="scroller" class="flex min-h-0 flex-1 flex-col overflow-y-auto px-7 py-2">
        <div
          v-if="list.isPending.value"
          class="flex animate-pulse flex-col gap-4 py-3 motion-reduce:animate-none"
          aria-hidden="true"
        >
          <div v-for="n in 6" :key="n" class="flex items-center gap-3">
            <span class="size-11 rounded-full bg-tile" />
            <span class="h-3.5 flex-1 rounded-full bg-tile" />
          </div>
        </div>

        <p v-else-if="list.isError.value" class="py-8 text-center text-mute">This list did not load.</p>

        <p v-else-if="accounts.length === 0" class="py-8 text-center text-mute">
          {{ kind === 'followers' ? 'No followers to show.' : 'Not following anyone you can see.' }}
        </p>

        <AccountRow
          v-for="item in accounts"
          v-else
          :key="item.id"
          :name="item.acct"
          :avatar="item.avatar"
          :sub="item.display_name"
          :to="{ name: 'profile', params: { username: item.acct } }"
          :size="44"
          class="min-h-[60px]"
        >
          <FollowButton v-if="user && item.id !== user.id" :account="item" variant="text" class="shrink-0 pl-2" />
        </AccountRow>

        <div ref="sentinel" class="h-px shrink-0" />
      </div>
    </div>
  </ModalShell>
</template>
