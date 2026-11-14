import { useInfiniteQuery, useQuery } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import {
  COLLECTIONS_PER_PAGE,
  fetchAccountCollections,
  fetchCollection,
  fetchCollectionItems,
  fetchSelfCollections,
} from '@/api/collections'
import { keys } from '@/api/keys'

export function useAccountCollections(accountId: MaybeRefOrGetter<string | undefined>) {
  return useInfiniteQuery({
    queryKey: computed(() => keys.accountCollections(toValue(accountId) ?? '')),
    queryFn: ({ pageParam }) => fetchAccountCollections(toValue(accountId) ?? '', pageParam),
    initialPageParam: 1,
    getNextPageParam: (lastPage, pages) => (lastPage.length >= COLLECTIONS_PER_PAGE ? pages.length + 1 : null),
    enabled: computed(() => !!toValue(accountId)),
  })
}

export function useSelfCollections(enabled: MaybeRefOrGetter<boolean> = true) {
  return useQuery({
    queryKey: keys.selfCollections(),
    queryFn: fetchSelfCollections,
    enabled: computed(() => toValue(enabled)),
    staleTime: 60_000,
  })
}

export function useCollection(id: MaybeRefOrGetter<string>) {
  return useQuery({
    queryKey: computed(() => keys.collection(toValue(id))),
    queryFn: () => fetchCollection(toValue(id)),
    staleTime: 60_000,
  })
}

export function useCollectionItems(id: MaybeRefOrGetter<string>) {
  return useInfiniteQuery({
    queryKey: computed(() => keys.timeline(`collection:${toValue(id)}`)),
    queryFn: ({ pageParam }) => fetchCollectionItems(toValue(id), pageParam),
    initialPageParam: 1,
    getNextPageParam: (lastPage, pages) => (lastPage.data.length > 0 ? pages.length + 1 : null),
  })
}
