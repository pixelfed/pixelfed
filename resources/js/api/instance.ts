import { getJson } from '@/lib/http'
import type { Instance, Status } from '@/types/pixelfed'

export const LANDING_POSTS_PATH = '/api/v1/timelines/public?local=1&limit=6&_pe=1'

export function fetchInstance(): Promise<Instance> {
  return getJson<Instance>('/api/v1/instance', { skipAuthRedirect: true })
}

export async function fetchLandingPosts(): Promise<Status[]> {
  try {
    return await getJson<Status[]>(LANDING_POSTS_PATH, { skipAuthRedirect: true })
  } catch {
    return []
  }
}
