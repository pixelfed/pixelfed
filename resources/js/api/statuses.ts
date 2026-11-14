import { getJson, postJson } from '@/lib/http'
import type { Status, StatusContext } from '@/types/pixelfed'

function action(id: string, name: string): Promise<Status> {
  return postJson<Status>(`/api/v1/statuses/${id}/${name}?_pe=1`)
}

export const favourite = (id: string) => action(id, 'favourite')
export const unfavourite = (id: string) => action(id, 'unfavourite')
export const reblog = (id: string) => action(id, 'reblog')
export const unreblog = (id: string) => action(id, 'unreblog')
export const bookmark = (id: string) => action(id, 'bookmark')
export const unbookmark = (id: string) => action(id, 'unbookmark')

export function fetchStatus(id: string): Promise<Status> {
  return getJson<Status>(`/api/v1/statuses/${id}?_pe=1`, { skipAuthRedirect: true })
}

export function fetchContext(id: string): Promise<StatusContext> {
  return getJson<StatusContext>(`/api/v1/statuses/${id}/context?_pe=1`, { skipAuthRedirect: true })
}

export function postReply(id: string, status: string): Promise<Status> {
  return postJson<Status>('/api/v1/statuses?_pe=1', { status, in_reply_to_id: id })
}
