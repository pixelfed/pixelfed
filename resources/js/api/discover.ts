import { getJson } from '@/lib/http'
import type { SearchResults, Status } from '@/types/pixelfed'

export type DiscoverRange = 'daily' | 'monthly' | 'yearly'

export const RANGES: { value: DiscoverRange; label: string }[] = [
  { value: 'daily', label: 'Today' },
  { value: 'monthly', label: 'This month' },
  { value: 'yearly', label: 'This year' },
]

export function fetchTrending(range: DiscoverRange): Promise<Status[]> {
  return getJson<Status[]>(`/api/v1.1/discover/posts/trending?range=${range}`)
}

export function search(query: string): Promise<SearchResults> {
  const resolve = /@|https?:\/\//.test(query)
  return getJson<SearchResults>(
    `/api/v2/search?q=${encodeURIComponent(query)}&limit=20&resolve=${resolve ? 'true' : 'false'}&_pe=1`,
  )
}
