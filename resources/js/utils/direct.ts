import type { Conversation, DirectMessage } from '@/types/direct'

export function conversationTitle(conversation: Conversation): string {
  if (conversation.name) {
    return conversation.name
  }
  const names = conversation.participants.map((account) => account.acct)
  return names.length > 0 ? names.join(', ') : 'Conversation'
}

export function messagePreview(message: DirectMessage | null): string {
  if (!message) {
    return 'No messages yet'
  }
  const body = message.text?.trim()
  const text = body || (message.media.length > 0 ? 'Sent a photo' : 'Sent a message')
  return message.is_author ? `You: ${text}` : text
}

export function dayLabel(input: string, now: Date = new Date()): string {
  const date = new Date(input)
  const startOfDay = (value: Date) => new Date(value.getFullYear(), value.getMonth(), value.getDate()).getTime()
  const days = Math.round((startOfDay(now) - startOfDay(date)) / 86_400_000)

  if (days <= 0) {
    return 'Today'
  }
  if (days === 1) {
    return 'Yesterday'
  }
  return date.toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: date.getFullYear() === now.getFullYear() ? undefined : 'numeric',
  })
}
