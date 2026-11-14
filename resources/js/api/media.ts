import { http, putJson } from '@/lib/http'
import type { MediaAttachment } from '@/types/pixelfed'

export async function uploadMedia(file: File, onProgress?: (fraction: number) => void): Promise<MediaAttachment> {
  const body = new FormData()
  body.append('file', file)

  const res = await http.post<MediaAttachment>('/api/v1/media', body, {
    timeout: 0,
    onUploadProgress: (event) => {
      if (onProgress && event.total) {
        onProgress(event.loaded / event.total)
      }
    },
  })
  return res.data
}

export function updateMediaDescription(id: string, description: string): Promise<MediaAttachment> {
  return putJson<MediaAttachment>(`/api/v1/media/${id}`, { description })
}
