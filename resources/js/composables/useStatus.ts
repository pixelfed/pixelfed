import { useMutation, useQuery, useQueryClient, type InfiniteData } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import { keys } from '@/api/keys'
import { fetchContext, fetchStatus, postReply } from '@/api/statuses'
import { patchStatus } from '@/composables/useStatusActions'
import type { Paginated } from '@/lib/pagination'
import type { Status, StatusContext } from '@/types/pixelfed'

type TimelineData = InfiniteData<Paginated<Status>, string | null>

export function useStatus(id: MaybeRefOrGetter<string>) {
  const queryClient = useQueryClient()

  function cached(): Status | undefined {
    const target = toValue(id)
    for (const [, data] of queryClient.getQueriesData<TimelineData>({ queryKey: keys.timelines() })) {
      for (const page of data?.pages ?? []) {
        for (const item of page.data) {
          const status = item.reblog ?? item
          if (status.id === target) {
            return status
          }
        }
      }
    }
    return undefined
  }

  return useQuery({
    queryKey: computed(() => keys.status(toValue(id))),
    queryFn: () => fetchStatus(toValue(id)),
    initialData: cached,
    initialDataUpdatedAt: 0,
    staleTime: 30_000,
  })
}

export function useStatusContext(id: MaybeRefOrGetter<string>) {
  return useQuery({
    queryKey: computed(() => keys.statusContext(toValue(id))),
    queryFn: () => fetchContext(toValue(id)),
  })
}

export function useReply(postId: MaybeRefOrGetter<string>) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ parentId, text }: { parentId: string; text: string }) => postReply(parentId, text),
    onSuccess: (reply, { parentId }) => {
      queryClient.setQueryData<StatusContext>(keys.statusContext(toValue(postId)), (old) =>
        old ? { ...old, descendants: [...old.descendants, reply] } : old,
      )
      patchStatus(queryClient, parentId, (status) => ({ ...status, reply_count: (status.reply_count ?? 0) + 1 }))
    },
  })
}
