import { isAxiosError } from 'axios'
import { getJson, http, refreshCsrf } from '@/lib/http'
import type { Account } from '@/types/pixelfed'
import { keys } from './keys'

export interface LoginInput {
  email: string
  password: string
  remember?: boolean
}

export type LoginResult = { status: 'ok' } | { status: 'pending'; step: string; url: string }

export async function fetchSession(): Promise<Account | null> {
  try {
    return await getJson<Account>('/api/v1/accounts/verify_credentials?_pe=1', { skipAuthRedirect: true })
  } catch (error) {
    if (isAxiosError(error) && [401, 403].includes(error.response?.status ?? 0)) {
      return null
    }
    throw error
  }
}

export const sessionQuery = {
  queryKey: keys.session(),
  queryFn: fetchSession,
  staleTime: 5 * 60_000,
  retry: false,
}

function pendingStep(responseUrl: string | undefined): { step: string; url: string } | null {
  if (!responseUrl) {
    return null
  }
  const landed = new URL(responseUrl, window.location.origin)
  const step = landed.pathname === '/login' ? landed.searchParams.get('step') : null
  return step ? { step, url: `${landed.pathname}${landed.search}` } : null
}

export async function login(input: LoginInput): Promise<LoginResult> {
  await refreshCsrf()
  const res = await http.post('/login', input, { skipAuthRedirect: true })
  const pending = pendingStep(res.request?.responseURL)
  return pending ? { status: 'pending', ...pending } : { status: 'ok' }
}

export async function logout(): Promise<void> {
  await http.post('/logout', undefined, { skipAuthRedirect: true })
}
