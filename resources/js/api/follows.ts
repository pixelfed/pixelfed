import { getJson } from '@/lib/http'
import { getPaginated, type Paginated } from '@/lib/pagination'
import type { Account, Relationship } from '@/types/pixelfed'

export type FollowListKind = 'followers' | 'following'

export function fetchFollowList(
  accountId: string,
  kind: FollowListKind,
  pageUrl: string | null,
): Promise<Paginated<Account>> {
  return getPaginated<Account>(pageUrl ?? `/api/v1/accounts/${accountId}/${kind}?_pe=1&limit=40`, {
    skipAuthRedirect: true,
  })
}

export function fetchRelationships(ids: string[]): Promise<Relationship[]> {
  if (ids.length === 0) {
    return Promise.resolve([])
  }
  const query = ids.map((id) => `id[]=${encodeURIComponent(id)}`).join('&')
  return getJson<Relationship[]>(`/api/v1/accounts/relationships?${query}`)
}
