import { getJson, postJson } from '@/lib/http'
import { getPaginated, type Paginated } from '@/lib/pagination'
import type { Account, Relationship, Status } from '@/types/pixelfed'

export type AccountFeed = 'posts' | 'shares'

export async function fetchSuggestedAccounts(): Promise<Account[]> {
  const page = await getPaginated<Account>('/api/v1.2/discover/accounts/popular')
  return page.data
}

export function lookupAccount(acct: string): Promise<Account> {
  return getJson<Account>(`/api/v1/accounts/lookup?acct=${encodeURIComponent(acct)}&_pe=1`, {
    skipAuthRedirect: true,
  })
}

export function fetchAccountStatuses(id: string, feed: AccountFeed, maxId: string | null): Promise<Paginated<Status>> {
  const filter = feed === 'shares' ? 'only_reposts=1' : 'only_media=1&exclude_replies=1'
  const cursor = maxId ? `&max_id=${encodeURIComponent(maxId)}` : ''
  return getPaginated<Status>(`/api/v1/accounts/${id}/statuses?_pe=1&${filter}&limit=21${cursor}`, {
    skipAuthRedirect: true,
  })
}

export async function fetchRelationship(id: string): Promise<Relationship | null> {
  const list = await getJson<Relationship[]>(`/api/v1/accounts/relationships?id[]=${id}`)
  return list[0] ?? null
}

export function followAccount(id: string): Promise<Relationship> {
  return postJson<Relationship>(`/api/v1/accounts/${id}/follow`)
}

export function unfollowAccount(id: string): Promise<Relationship> {
  return postJson<Relationship>(`/api/v1/accounts/${id}/unfollow`)
}
