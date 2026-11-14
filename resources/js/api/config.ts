import { getJson } from '@/lib/http'
import type { SiteConfig } from '@/types/pixelfed'

export function fetchSiteConfig(): Promise<SiteConfig> {
  return getJson<SiteConfig>('/api/v2/config', { skipAuthRedirect: true })
}
