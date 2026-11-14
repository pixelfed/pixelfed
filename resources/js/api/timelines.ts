import { getPaginated, type Paginated } from '@/lib/pagination'
import type { Status } from '@/types/pixelfed'

export type FeedType = 'home' | 'local' | 'public'

export const FEEDS: Record<FeedType, { label: string; path: string }> = {
  home: { label: 'Home', path: '/api/v1/timelines/home?_pe=1&limit=20&includes_reblogs=1' },
  local: { label: 'Local', path: '/api/v1/timelines/public?local=1&limit=20&_pe=1' },
  public: { label: 'Network', path: '/api/v1/timelines/public?remote=1&limit=20&_pe=1' },
}

export function fetchTimeline(feed: FeedType, pageUrl: string | null): Promise<Paginated<Status>> {
  return getPaginated<Status>(pageUrl ?? FEEDS[feed].path)
}
