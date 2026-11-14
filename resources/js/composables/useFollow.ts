import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, reactive, toValue, watch, type MaybeRefOrGetter } from 'vue'
import { fetchRelationship, followAccount, unfollowAccount } from '@/api/accounts'
import { keys } from '@/api/keys'
import type { Account, Relationship } from '@/types/pixelfed'

type FollowState = 'following' | 'requested' | 'none'

const states = reactive(new Map<string, FollowState>())

function stateFrom(relationship: Relationship | null | undefined): FollowState {
  if (relationship?.following) {
    return 'following'
  }
  return relationship?.requested ? 'requested' : 'none'
}

export function seedFollowState(id: string, relationship: Relationship | null | undefined) {
  if (relationship && !states.has(id)) {
    states.set(id, stateFrom(relationship))
  }
}

export function useFollow() {
  const queryClient = useQueryClient()

  const mutation = useMutation({
    mutationFn: ({ account, previous }: { account: Account; previous: FollowState }) =>
      previous === 'none' ? followAccount(account.id) : unfollowAccount(account.id),
    onMutate: ({ account, previous }) => {
      states.set(account.id, previous === 'none' ? (account.locked ? 'requested' : 'following') : 'none')
    },
    onSuccess: (relationship, { account }) => {
      states.set(account.id, stateFrom(relationship))
      queryClient.setQueryData(keys.relationship(account.id), relationship)
    },
    onError: (_error, { account, previous }) => {
      states.set(account.id, previous)
    },
  })

  function stateOf(id: string): FollowState {
    return states.get(id) ?? 'none'
  }

  function toggle(account: Account) {
    mutation.mutate({ account, previous: stateOf(account.id) })
  }

  function labelOf(id: string): string {
    const state = stateOf(id)
    return state === 'following' ? 'Following' : state === 'requested' ? 'Requested' : 'Follow'
  }

  return {
    stateOf,
    labelOf,
    toggle,
  }
}

export function useRelationship(id: MaybeRefOrGetter<string | undefined>, enabled: MaybeRefOrGetter<boolean> = true) {
  const query = useQuery({
    queryKey: computed(() => keys.relationship(toValue(id) ?? '')),
    queryFn: () => fetchRelationship(toValue(id) ?? ''),
    enabled: computed(() => !!toValue(id) && toValue(enabled)),
  })

  watch(
    () => query.data.value,
    (relationship) => {
      const key = toValue(id)
      if (key && relationship) {
        states.set(key, stateFrom(relationship))
      }
    },
    { immediate: true },
  )

  return query
}
