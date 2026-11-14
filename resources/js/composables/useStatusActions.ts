import { useMutation, useQueryClient, type InfiniteData, type QueryClient, type QueryKey } from '@tanstack/vue-query'
import { keys } from '@/api/keys'
import { bookmark, favourite, reblog, unbookmark, unfavourite, unreblog } from '@/api/statuses'
import type { Paginated } from '@/lib/pagination'
import type { Status, StatusContext } from '@/types/pixelfed'

type TimelineData = InfiniteData<Paginated<Status>, string | null>
type Patch = (status: Status) => Status
type Snapshot = [QueryKey, unknown][]

export function patchStatus(queryClient: QueryClient, id: string, patch: Patch) {
  const apply = (status: Status): Status => {
    if (status.id === id) {
      return patch(status)
    }
    if (status.reblog?.id === id) {
      return { ...status, reblog: patch(status.reblog) }
    }
    return status
  }

  queryClient.setQueriesData<TimelineData>({ queryKey: keys.timelines() }, (old) => {
    if (!old) {
      return old
    }
    return {
      ...old,
      pages: old.pages.map((page) => ({ ...page, data: page.data.map(apply) })),
    }
  })

  queryClient.setQueriesData<Status>({ queryKey: keys.statuses() }, (old) => (old ? apply(old) : old))

  queryClient.setQueriesData<StatusContext>({ queryKey: keys.contexts() }, (old) => {
    if (!old) {
      return old
    }
    return {
      ancestors: old.ancestors.map(apply),
      descendants: old.descendants.map(apply),
    }
  })
}

function snapshot(queryClient: QueryClient): Snapshot {
  return [
    ...queryClient.getQueriesData({ queryKey: keys.timelines() }),
    ...queryClient.getQueriesData({ queryKey: keys.statuses() }),
    ...queryClient.getQueriesData({ queryKey: keys.contexts() }),
  ]
}

function useOptimisticAction(run: (status: Status) => Promise<Status>, patch: Patch) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: run,
    onMutate: async (status: Status) => {
      await Promise.all([
        queryClient.cancelQueries({ queryKey: keys.timelines() }),
        queryClient.cancelQueries({ queryKey: keys.statuses() }),
        queryClient.cancelQueries({ queryKey: keys.contexts() }),
      ])
      const previous = snapshot(queryClient)
      patchStatus(queryClient, status.id, patch)
      return { previous }
    },
    onError: (_error, _status, context) => {
      context?.previous.forEach(([key, data]) => queryClient.setQueryData(key, data))
    },
  })
}

export function useFavourite() {
  return useOptimisticAction(
    (status) => (status.favourited ? unfavourite(status.id) : favourite(status.id)),
    (status) => ({
      ...status,
      favourited: !status.favourited,
      favourites_count: Math.max(0, status.favourites_count + (status.favourited ? -1 : 1)),
    }),
  )
}

export function useReblog() {
  return useOptimisticAction(
    (status) => (status.reblogged ? unreblog(status.id) : reblog(status.id)),
    (status) => ({
      ...status,
      reblogged: !status.reblogged,
      reblogs_count: Math.max(0, status.reblogs_count + (status.reblogged ? -1 : 1)),
    }),
  )
}

export function useBookmark() {
  return useOptimisticAction(
    (status) => (status.bookmarked ? unbookmark(status.id) : bookmark(status.id)),
    (status) => ({ ...status, bookmarked: !status.bookmarked }),
  )
}
