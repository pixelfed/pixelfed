import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed } from 'vue'
import { login, logout, sessionQuery } from '@/api/session'

export function useSession() {
  const query = useQuery(sessionQuery)
  const user = computed(() => query.data.value ?? null)
  const isAuthenticated = computed(() => user.value !== null)

  return {
    user,
    isAuthenticated,
    isLoading: query.isLoading,
    refetch: query.refetch,
  }
}

export function useLogin() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: login,
    onSuccess: async (result) => {
      if (result.status === 'ok') {
        await queryClient.fetchQuery({ ...sessionQuery, staleTime: 0 })
      }
    },
  })
}

export function useLogout() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: logout,
    onSuccess: () => {
      queryClient.clear()
      window.location.assign('/')
    },
  })
}
