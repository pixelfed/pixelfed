import { getJson, postJson } from '@/lib/http'
import type { Conversation, ConversationPage, DirectMessage, MessagePage } from '@/types/direct'

const BASE = '/api/v1.1/direct/conversations'

export interface MessageInput {
  message?: string
  media_ids?: string[]
}

export function fetchConversations(cursor: string | null): Promise<ConversationPage> {
  const page = cursor ? `&cursor=${encodeURIComponent(cursor)}` : ''
  return getJson<ConversationPage>(`${BASE}?limit=20${page}`)
}

export function fetchConversation(id: string): Promise<Conversation> {
  return getJson<Conversation>(`${BASE}/${id}`)
}

export function createConversation(recipientIds: string[]): Promise<Conversation> {
  return postJson<Conversation>(BASE, { recipient_ids: recipientIds })
}

export function fetchMessages(id: string, maxId: string | null): Promise<MessagePage> {
  const page = maxId ? `&max_id=${encodeURIComponent(maxId)}` : ''
  return getJson<MessagePage>(`${BASE}/${id}/messages?limit=30${page}`)
}

export function sendMessage(id: string, input: MessageInput): Promise<DirectMessage> {
  return postJson<DirectMessage>(`${BASE}/${id}/messages`, input)
}

export function markConversationRead(id: string): Promise<unknown> {
  return postJson(`${BASE}/${id}/read`)
}
