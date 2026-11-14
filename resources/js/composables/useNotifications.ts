import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import { keys } from '@/api/keys'
import {
  acceptFollowRequest,
  fetchFollowRequests,
  fetchNotifications,
  rejectFollowRequest,
  type NotificationFilter,
} from '@/api/notifications'
import { keepPe } from '@/lib/pagination'
import type { Account } from '@/types/pixelfed'

export function useNotifications(filter: MaybeRefOrGetter<NotificationFilter>) {
  return useInfiniteQuery({
    queryKey: computed(() => keys.notificationList(toValue(filter))),
    queryFn: ({ pageParam }) => fetchNotifications(toValue(filter), pageParam),
    initialPageParam: null as string | null,
    getNextPageParam: (lastPage) => keepPe(lastPage.next),
  })
}

export function useFollowRequests() {
  return useQuery({
    queryKey: keys.followRequests(),
    queryFn: fetchFollowRequests,
  })
}

export function useAnswerFollowRequest() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ account, accept }: { account: Account; accept: boolean }) =>
      accept ? acceptFollowRequest(account.id) : rejectFollowRequest(account.id),
    onSuccess: (_relationship, { account }) => {
      queryClient.setQueryData<Account[]>(keys.followRequests(), (old) =>
        old ? old.filter((item) => item.id !== account.id) : old,
      )
      queryClient.invalidateQueries({ queryKey: keys.session() })
    },
  })
}
