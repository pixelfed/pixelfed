import type { Account, Notification, Status } from '@/types/pixelfed'

export interface NotificationEntry {
  key: string
  type: string
  accounts: Account[]
  total: number
  status?: Status
  createdAt: string
  conversationId?: string
}

export interface NotificationSection {
  key: 'new' | 'week' | 'earlier'
  label: string
  entries: NotificationEntry[]
}

const DAY = 86_400_000
const GROUPED_TYPES = ['favourite', 'share', 'reblog']

function sectionOf(createdAt: string, now: number): NotificationSection['key'] {
  const age = now - new Date(createdAt).getTime()
  if (age < DAY) {
    return 'new'
  }
  return age < DAY * 7 ? 'week' : 'earlier'
}

export function groupNotifications(items: Notification[], now: number = Date.now()): NotificationSection[] {
  const sections: NotificationSection[] = [
    { key: 'new', label: 'New', entries: [] },
    { key: 'week', label: 'This week', entries: [] },
    { key: 'earlier', label: 'Earlier', entries: [] },
  ]

  for (const item of items) {
    if (!item.account) {
      continue
    }

    const section = sections.find((candidate) => candidate.key === sectionOf(item.created_at, now))!
    const last = section.entries.at(-1)

    if (
      last &&
      GROUPED_TYPES.includes(item.type) &&
      last.type === item.type &&
      last.status &&
      last.status.id === item.status?.id
    ) {
      if (!last.accounts.some((account) => account.id === item.account!.id)) {
        last.accounts.push(item.account)
        last.total += 1
      }
      continue
    }

    section.entries.push({
      key: item.id,
      type: item.type,
      accounts: [item.account],
      total: 1,
      status: item.status,
      createdAt: item.created_at,
      conversationId: item.direct?.conversation_id,
    })
  }

  return sections.filter((section) => section.entries.length > 0)
}
