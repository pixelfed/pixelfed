import { getPaginated, type Paginated } from '@/lib/pagination'
import type { Status } from '@/types/pixelfed'

export function fetchBookmarks(pageUrl: string | null): Promise<Paginated<Status>> {
  return getPaginated<Status>(pageUrl ?? '/api/v1/bookmarks?_pe=1&limit=24')
}
