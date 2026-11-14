import { useQuery } from '@tanstack/vue-query'
import { fetchInstance, fetchLandingPosts } from '@/api/instance'
import { keys } from '@/api/keys'

export function useInstance() {
  return useQuery({
    queryKey: keys.instance(),
    queryFn: fetchInstance,
    staleTime: 10 * 60_000,
  })
}

export function useLandingPosts() {
  return useQuery({
    queryKey: keys.landingPosts(),
    queryFn: fetchLandingPosts,
    staleTime: 5 * 60_000,
  })
}
