import { keepPreviousData, useQuery } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import { searchPlaces } from '@/api/compose'
import { keys } from '@/api/keys'

export function usePlaces(query: MaybeRefOrGetter<string>) {
  const term = computed(() => toValue(query).trim())

  return useQuery({
    queryKey: computed(() => keys.places(term.value)),
    queryFn: () => searchPlaces(term.value),
    enabled: computed(() => term.value.length >= 2),
    placeholderData: keepPreviousData,
    staleTime: 5 * 60_000,
  })
}
