import type { AxiosRequestConfig } from 'axios'
import { http } from './http'

export interface Paginated<T> {
  data: T[]
  next: string | null
  prev: string | null
}

export function parseLinkHeader(header?: string | null): { next: string | null; prev: string | null } {
  const links: { next: string | null; prev: string | null } = { next: null, prev: null }
  if (!header) {
    return links
  }
  for (const part of header.split(',')) {
    const match = part.match(/<([^>]+)>\s*;\s*rel="?(\w+)"?/)
    if (!match) {
      continue
    }
    if (match[2] === 'next') {
      links.next = match[1]
    }
    if (match[2] === 'prev') {
      links.prev = match[1]
    }
  }
  return links
}

export function keepPe(url: string | null): string | null {
  if (!url || /[?&]_pe=1(&|$)/.test(url)) {
    return url
  }
  return `${url}${url.includes('?') ? '&' : '?'}_pe=1`
}

export async function getPaginated<T>(url: string, config?: AxiosRequestConfig): Promise<Paginated<T>> {
  const res = await http.get<T[]>(url, config)
  const header = res.headers.link as string | undefined
  return { data: res.data, ...parseLinkHeader(header) }
}
