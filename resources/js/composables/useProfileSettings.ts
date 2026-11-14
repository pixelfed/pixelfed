import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { keys } from '@/api/keys'
import { updateProfile } from '@/api/settings'

export function useUpdateProfile() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: updateProfile,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: keys.session() })
      queryClient.invalidateQueries({ queryKey: keys.accounts() })
    },
  })
}
