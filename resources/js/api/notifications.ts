import { getJson, postJson } from '@/lib/http'
import { getPaginated, type Paginated } from '@/lib/pagination'
import type { Account, Notification, Relationship } from '@/types/pixelfed'

export type NotificationFilter = 'all' | 'mentions' | 'follows'

const TYPE_PARAMS: Record<NotificationFilter, string> = {
  all: '',
  mentions: '&types[]=mention',
  follows: '&types[]=follow',
}

export function fetchNotifications(
  filter: NotificationFilter,
  pageUrl: string | null,
): Promise<Paginated<Notification>> {
  return getPaginated<Notification>(pageUrl ?? `/api/v1/notifications?_pe=1&limit=20${TYPE_PARAMS[filter]}`)
}

export function fetchFollowRequests(): Promise<Account[]> {
  return getJson<Account[]>('/api/v1/follow_requests?limit=40')
}

export function acceptFollowRequest(id: string): Promise<Relationship> {
  return postJson<Relationship>(`/api/v1/follow_requests/${id}/authorize`)
}

export function rejectFollowRequest(id: string): Promise<Relationship> {
  return postJson<Relationship>(`/api/v1/follow_requests/${id}/reject`)
}
