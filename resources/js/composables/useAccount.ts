import { useInfiniteQuery, useQuery } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import { fetchAccountStatuses, lookupAccount, type AccountFeed } from '@/api/accounts'
import { keys } from '@/api/keys'

export function useAccount(acct: MaybeRefOrGetter<string>) {
  return useQuery({
    queryKey: computed(() => keys.accountLookup(toValue(acct))),
    queryFn: () => lookupAccount(toValue(acct)),
    staleTime: 60_000,
  })
}

function olderThan(id: string): string {
  try {
    return String(BigInt(id) - 1n)
  } catch {
    return id
  }
}

export function useAccountStatuses(id: MaybeRefOrGetter<string | undefined>, feed: MaybeRefOrGetter<AccountFeed>) {
  return useInfiniteQuery({
    queryKey: computed(() => [...keys.accountStatuses(toValue(id) ?? ''), toValue(feed)]),
    queryFn: ({ pageParam }) => fetchAccountStatuses(toValue(id) ?? '', toValue(feed), pageParam),
    initialPageParam: null as string | null,
    getNextPageParam: (lastPage) => {
      const last = lastPage.data.at(-1)
      return last ? olderThan(last.id) : null
    },
    enabled: computed(() => !!toValue(id)),
  })
}
