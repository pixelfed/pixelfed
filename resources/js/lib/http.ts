import axios, { AxiosHeaders, isAxiosError, type AxiosRequestConfig } from 'axios'

let onUnauthorized: (() => void) | null = null
let csrfRefresh: Promise<void> | null = null

export const http = axios.create({
  timeout: 30_000,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export function setUnauthorizedHandler(handler: () => void) {
  onUnauthorized = handler
}

export function refreshCsrf(): Promise<void> {
  csrfRefresh ??= axios
    .get('/sanctum/csrf-cookie', { withCredentials: true })
    .then(() => undefined)
    .finally(() => {
      csrfRefresh = null
    })
  return csrfRefresh
}

http.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (!isAxiosError(error) || !error.config) {
      return Promise.reject(error)
    }

    const config = error.config
    const status = error.response?.status

    if (status === 419 && !config.csrfRetried) {
      config.csrfRetried = true
      AxiosHeaders.from(config.headers).delete('X-XSRF-TOKEN')
      await refreshCsrf()
      return http.request(config)
    }

    if (status === 401 && !config.skipAuthRedirect) {
      onUnauthorized?.()
    }

    return Promise.reject(error)
  },
)

export async function getJson<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
  const res = await http.get<T>(url, config)
  return res.data
}

export async function postJson<T>(url: string, body?: unknown, config?: AxiosRequestConfig): Promise<T> {
  const res = await http.post<T>(url, body, config)
  return res.data
}

export async function putJson<T>(url: string, body?: unknown, config?: AxiosRequestConfig): Promise<T> {
  const res = await http.put<T>(url, body, config)
  return res.data
}

export async function patchJson<T>(url: string, body?: unknown, config?: AxiosRequestConfig): Promise<T> {
  const res = await http.patch<T>(url, body, config)
  return res.data
}

export async function deleteJson<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
  const res = await http.delete<T>(url, config)
  return res.data
}

export type ValidationErrors = Record<string, string[]>

export function getValidationErrors(error: unknown): ValidationErrors {
  if (isAxiosError(error) && error.response?.status === 422) {
    return (error.response.data?.errors ?? {}) as ValidationErrors
  }
  return {}
}

export function getErrorMessage(error: unknown, fallback = 'Something went wrong.'): string {
  if (isAxiosError(error)) {
    const data = error.response?.data
    const first = Object.values(getValidationErrors(error))[0]?.[0]
    return first || data?.message || data?.error || error.message || fallback
  }
  if (error instanceof Error) {
    return error.message || fallback
  }
  return fallback
}
