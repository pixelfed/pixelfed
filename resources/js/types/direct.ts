import type { Account, MediaAttachment } from '@/types/pixelfed'

export interface DirectMessage {
  id: string
  conversation_id: string
  sender_id: string
  is_author: boolean
  type: string | null
  text: string | null
  media: MediaAttachment[]
  sensitive: boolean
  in_reply_to_id: string | null
  edited_at: string | null
  created_at: string
}

export interface Conversation {
  id: string
  type: string
  name: string | null
  participants: Account[]
  participant_count: number
  state: string
  unread_count: number
  muted: boolean
  hidden: boolean
  last_read_message_id: string | null
  last_message: DirectMessage | null
  created_at: string
  updated_at: string
}

export interface ConversationPage {
  data: Conversation[]
  meta: {
    next_cursor: string | null
    prev_cursor: string | null
  }
}

export interface MessagePage {
  data: DirectMessage[]
  meta: {
    has_more: boolean
    newest_id: string | null
    oldest_id: string | null
  }
}
