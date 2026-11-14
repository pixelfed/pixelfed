import { getJson, postJson } from '@/lib/http'
import { getPaginated, type Paginated } from '@/lib/pagination'
import type { Status, Tag } from '@/types/pixelfed'

export function fetchTag(name: string): Promise<Tag> {
  return getJson<Tag>(`/api/v1/tags/${encodeURIComponent(name)}?_pe=1`)
}

export function followTag(name: string): Promise<unknown> {
  return postJson(`/api/v1/tags/${encodeURIComponent(name)}/follow`)
}

export function unfollowTag(name: string): Promise<unknown> {
  return postJson(`/api/v1/tags/${encodeURIComponent(name)}/unfollow`)
}

export function fetchTagTimeline(name: string, pageUrl: string | null): Promise<Paginated<Status>> {
  return getPaginated<Status>(
    pageUrl ?? `/api/v1/timelines/tag/${encodeURIComponent(name)}?_pe=1&only_media=1&limit=24`,
  )
}
