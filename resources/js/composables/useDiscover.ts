import { keepPreviousData, useQuery } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import { fetchTrending, search, type DiscoverRange } from '@/api/discover'
import { keys } from '@/api/keys'

export function useTrending(range: MaybeRefOrGetter<DiscoverRange>, enabled: MaybeRefOrGetter<boolean> = true) {
  return useQuery({
    queryKey: computed(() => keys.discover(toValue(range))),
    queryFn: () => fetchTrending(toValue(range)),
    staleTime: 5 * 60_000,
    enabled: computed(() => toValue(enabled)),
  })
}

export function useSearch(query: MaybeRefOrGetter<string>) {
  const term = computed(() => toValue(query).trim())

  return useQuery({
    queryKey: computed(() => keys.search(term.value)),
    queryFn: () => search(term.value),
    enabled: computed(() => term.value.length >= 1),
    placeholderData: keepPreviousData,
    staleTime: 60_000,
  })
}
