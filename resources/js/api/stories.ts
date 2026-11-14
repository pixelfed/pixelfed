import { getJson, postJson } from '@/lib/http'
import type { StoryCarouselResponse } from '@/types/stories'

export function fetchStoryCarousel(): Promise<StoryCarouselResponse> {
  return getJson<StoryCarouselResponse>('/api/v1.2/stories/carousel')
}

export function markStorySeen(id: string): Promise<unknown> {
  return postJson('/api/v1.1/stories/seen', { id })
}

export function replyToStory(id: string, caption: string): Promise<unknown> {
  return postJson('/api/v1.1/stories/comment', { sid: id, caption })
}
