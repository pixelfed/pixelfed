import { useInfiniteQuery } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import { keys } from '@/api/keys'
import { fetchTimeline, type FeedType } from '@/api/timelines'
import { keepPe } from '@/lib/pagination'

export function useTimeline(feed: MaybeRefOrGetter<FeedType> = 'home') {
  return useInfiniteQuery({
    queryKey: computed(() => keys.timeline(toValue(feed))),
    queryFn: ({ pageParam }) => fetchTimeline(toValue(feed), pageParam),
    initialPageParam: null as string | null,
    getNextPageParam: (lastPage) => keepPe(lastPage.next),
  })
}
