import { useInfiniteQuery, useMutation, useQuery, useQueryClient, type InfiniteData } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import {
  createConversation,
  fetchConversation,
  fetchConversations,
  fetchMessages,
  markConversationRead,
  sendMessage,
  type MessageInput,
} from '@/api/direct'
import { keys } from '@/api/keys'
import type { Conversation, ConversationPage, DirectMessage, MessagePage } from '@/types/direct'

type ConversationData = InfiniteData<ConversationPage, string | null>
type MessageData = InfiniteData<MessagePage, string | null>

export function useConversations() {
  return useInfiniteQuery({
    queryKey: keys.conversations(),
    queryFn: ({ pageParam }) => fetchConversations(pageParam),
    initialPageParam: null as string | null,
    getNextPageParam: (lastPage) => lastPage.meta.next_cursor,
    refetchInterval: 30_000,
  })
}

export function useConversation(id: MaybeRefOrGetter<string | undefined>) {
  const queryClient = useQueryClient()

  return useQuery({
    queryKey: computed(() => keys.conversation(toValue(id) ?? '')),
    queryFn: () => fetchConversation(toValue(id) ?? ''),
    enabled: computed(() => !!toValue(id)),
    placeholderData: () =>
      queryClient
        .getQueryData<ConversationData>(keys.conversations())
        ?.pages.flatMap((page) => page.data)
        .find((conversation) => conversation.id === toValue(id)),
  })
}

export function useMessages(id: MaybeRefOrGetter<string | undefined>) {
  return useInfiniteQuery({
    queryKey: computed(() => keys.messages(toValue(id) ?? '')),
    queryFn: ({ pageParam }) => fetchMessages(toValue(id) ?? '', pageParam),
    initialPageParam: null as string | null,
    getNextPageParam: (lastPage) => (lastPage.meta.has_more ? lastPage.meta.oldest_id : null),
    enabled: computed(() => !!toValue(id)),
    refetchInterval: 15_000,
  })
}

function patchConversation(
  queryClient: ReturnType<typeof useQueryClient>,
  id: string,
  patch: (conversation: Conversation) => Conversation,
) {
  queryClient.setQueryData<ConversationData>(keys.conversations(), (old) => {
    if (!old) {
      return old
    }
    return {
      ...old,
      pages: old.pages.map((page) => ({
        ...page,
        data: page.data.map((conversation) => (conversation.id === id ? patch(conversation) : conversation)),
      })),
    }
  })
  queryClient.setQueryData<Conversation>(keys.conversation(id), (old) => (old ? patch(old) : old))
}

export function useSendMessage(id: MaybeRefOrGetter<string | undefined>) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (input: MessageInput) => sendMessage(toValue(id) ?? '', input),
    onSuccess: (message: DirectMessage) => {
      const key = message.conversation_id
      queryClient.setQueryData<MessageData>(keys.messages(key), (old) => {
        if (!old || old.pages.length === 0) {
          return old
        }
        const [first, ...rest] = old.pages
        if (first.data.some((item) => item.id === message.id)) {
          return old
        }
        return { ...old, pages: [{ ...first, data: [message, ...first.data] }, ...rest] }
      })
      patchConversation(queryClient, key, (conversation) => ({
        ...conversation,
        last_message: message,
        updated_at: message.created_at,
      }))
    },
  })
}

export function useMarkConversationRead() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: string) => markConversationRead(id),
    onMutate: (id) => {
      patchConversation(queryClient, id, (conversation) => ({ ...conversation, unread_count: 0 }))
    },
  })
}

export function useStartConversation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (recipientIds: string[]) => createConversation(recipientIds),
    onSuccess: (conversation) => {
      queryClient.setQueryData(keys.conversation(conversation.id), conversation)
      queryClient.invalidateQueries({ queryKey: keys.conversations() })
    },
  })
}
