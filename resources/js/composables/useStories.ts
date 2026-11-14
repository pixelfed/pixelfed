import { useQuery } from '@tanstack/vue-query'
import { keys } from '@/api/keys'
import { fetchStoryCarousel } from '@/api/stories'

export function useStories() {
  return useQuery({
    queryKey: keys.stories(),
    queryFn: fetchStoryCarousel,
    select: (response) => response.nodes ?? [],
    staleTime: 60_000,
  })
}
