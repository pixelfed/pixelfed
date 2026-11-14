import { getJson } from '@/lib/http'
import type { Paginated } from '@/lib/pagination'
import type { Collection, Status } from '@/types/pixelfed'

export const COLLECTIONS_PER_PAGE = 9

export function fetchAccountCollections(accountId: string, page: number): Promise<Collection[]> {
  return getJson<Collection[]>(`/api/v1.1/collections/accounts/${accountId}?page=${page}`, {
    skipAuthRedirect: true,
  })
}

export function fetchSelfCollections(): Promise<Collection[]> {
  return getJson<Collection[]>('/api/v1.1/collections/self')
}

export function fetchCollection(id: string): Promise<Collection> {
  return getJson<Collection>(`/api/v1.1/collections/view/${id}`, { skipAuthRedirect: true })
}

export async function fetchCollectionItems(id: string, page: number): Promise<Paginated<Status>> {
  const data = await getJson<Status[]>(`/api/v1.1/collections/items/${id}?page=${page}`, {
    skipAuthRedirect: true,
  })
  return { data, next: null, prev: null }
}
