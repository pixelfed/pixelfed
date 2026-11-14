import { useQuery } from '@tanstack/vue-query'
import { computed } from 'vue'
import { fetchSiteConfig } from '@/api/config'
import { keys } from '@/api/keys'

const DEFAULTS = {
  captionLength: 500,
  altTextLength: 1000,
  albumLimit: 4,
  bioLength: 125,
  nameLength: 30,
  mimeTypes: ['image/jpeg', 'image/png'],
}

export function useSiteConfig() {
  const query = useQuery({
    queryKey: keys.siteConfig(),
    queryFn: fetchSiteConfig,
    staleTime: 30 * 60_000,
    retry: false,
  })

  const limits = computed(() => {
    const config = query.data.value
    const mimeTypes = config?.uploader?.mime_types?.filter(Boolean) ?? []

    return {
      captionLength: config?.uploader?.max_caption_length || DEFAULTS.captionLength,
      altTextLength: config?.uploader?.max_altext_length || DEFAULTS.altTextLength,
      albumLimit: config?.uploader?.album_limit || DEFAULTS.albumLimit,
      bioLength: config?.account?.max_bio_length || DEFAULTS.bioLength,
      nameLength: config?.account?.max_name_length || DEFAULTS.nameLength,
      mimeTypes: mimeTypes.length > 0 ? mimeTypes : DEFAULTS.mimeTypes,
    }
  })

  return {
    limits,
    isLoading: query.isLoading,
  }
}
