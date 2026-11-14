import { getJson, postJson } from '@/lib/http'
import type { Place, Status } from '@/types/pixelfed'

export type Visibility = 'public' | 'unlisted' | 'private'

export const VISIBILITIES: { value: Visibility; label: string }[] = [
  { value: 'public', label: 'Public' },
  { value: 'unlisted', label: 'Unlisted' },
  { value: 'private', label: 'Followers only' },
]

export interface StatusInput {
  status: string
  media_ids: string[]
  visibility: Visibility
  sensitive: boolean
  comments_disabled: boolean
  place_id?: string | number
  collection_ids?: string[]
}

export function createStatus(input: StatusInput): Promise<Status> {
  return postJson<Status>('/api/v1/statuses?_pe=1', input)
}

export function searchPlaces(query: string): Promise<Place[]> {
  return getJson<Place[]>(`/api/v1.1/compose/search/location?q=${encodeURIComponent(query)}`)
}
