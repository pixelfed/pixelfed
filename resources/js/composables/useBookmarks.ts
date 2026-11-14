import { useInfiniteQuery } from '@tanstack/vue-query'
import { fetchBookmarks } from '@/api/bookmarks'
import { keys } from '@/api/keys'
import { keepPe } from '@/lib/pagination'

export function useBookmarks() {
  return useInfiniteQuery({
    queryKey: keys.timeline('bookmarks'),
    queryFn: ({ pageParam }) => fetchBookmarks(pageParam),
    initialPageParam: null as string | null,
    getNextPageParam: (lastPage) => keepPe(lastPage.next),
  })
}
