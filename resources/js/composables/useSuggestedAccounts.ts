import { useQuery } from '@tanstack/vue-query'
import { fetchSuggestedAccounts } from '@/api/accounts'
import { keys } from '@/api/keys'

export function useSuggestedAccounts() {
  return useQuery({
    queryKey: keys.suggestions(),
    queryFn: fetchSuggestedAccounts,
    staleTime: 5 * 60_000,
  })
}
