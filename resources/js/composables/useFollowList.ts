import { useInfiniteQuery } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import { fetchFollowList, fetchRelationships, type FollowListKind } from '@/api/follows'
import { keys } from '@/api/keys'
import { seedFollowState } from '@/composables/useFollow'
import { useSession } from '@/composables/useSession'
import { keepPe } from '@/lib/pagination'

export function useFollowList(accountId: MaybeRefOrGetter<string>, kind: MaybeRefOrGetter<FollowListKind>) {
  const { user } = useSession()

  return useInfiniteQuery({
    queryKey: computed(() => [...keys.account(toValue(accountId)), 'follow-list', toValue(kind)] as const),
    queryFn: async ({ pageParam }) => {
      const page = await fetchFollowList(toValue(accountId), toValue(kind), pageParam)

      if (user.value) {
        const ids = page.data.map((account) => account.id).filter((id) => id !== user.value?.id)
        const relationships = await fetchRelationships(ids).catch(() => [])
        relationships.forEach((relationship) => seedFollowState(relationship.id, relationship))
      }

      return page
    },
    initialPageParam: null as string | null,
    getNextPageParam: (lastPage) => keepPe(lastPage.prev),
    staleTime: 30_000,
  })
}
