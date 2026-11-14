import { QueryClient } from '@tanstack/vue-query'
import { isAxiosError } from 'axios'

const FINAL_STATUSES = new Set([400, 401, 403, 404, 410, 419, 422, 429])

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      gcTime: 5 * 60_000,
      refetchOnWindowFocus: false,
      retry: (failureCount, error) => {
        if (isAxiosError(error) && error.response && FINAL_STATUSES.has(error.response.status)) {
          return false
        }
        return failureCount < 2
      },
    },
    mutations: {
      retry: false,
    },
  },
})
