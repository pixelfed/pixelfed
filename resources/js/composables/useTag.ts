import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import { keys } from '@/api/keys'
import { fetchTag, fetchTagTimeline, followTag, unfollowTag } from '@/api/tags'
import { keepPe } from '@/lib/pagination'
import type { Tag } from '@/types/pixelfed'

export function useTag(name: MaybeRefOrGetter<string>) {
  return useQuery({
    queryKey: computed(() => keys.tag(toValue(name))),
    queryFn: () => fetchTag(toValue(name)),
    staleTime: 60_000,
  })
}

export function useTagTimeline(name: MaybeRefOrGetter<string>) {
  return useInfiniteQuery({
    queryKey: computed(() => keys.timeline(`tag:${toValue(name)}`)),
    queryFn: ({ pageParam }) => fetchTagTimeline(toValue(name), pageParam),
    initialPageParam: null as string | null,
    getNextPageParam: (lastPage) => keepPe(lastPage.next),
  })
}

export function useTagFollow() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (tag: Tag) => (tag.following ? unfollowTag(tag.name) : followTag(tag.name)),
    onMutate: (tag) => {
      queryClient.setQueryData<Tag>(keys.tag(tag.name), (old) => (old ? { ...old, following: !tag.following } : old))
    },
    onError: (_error, tag) => {
      queryClient.setQueryData<Tag>(keys.tag(tag.name), (old) => (old ? { ...old, following: tag.following } : old))
    },
  })
}
