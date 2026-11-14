import type { MediaAttachment } from '@/types/pixelfed'

const MIN_ASPECT = 4 / 5
const MAX_ASPECT = 1.91

export function aspectFor(media?: MediaAttachment): number {
  const original = media?.meta?.original
  const aspect = original?.aspect

  if (aspect && Number.isFinite(aspect) && aspect > 0) {
    return aspect
  }

  if (original?.width && original?.height && original.width > 0 && original.height > 0) {
    return original.width / original.height
  }

  return 1
}

export function feedAspect(media?: MediaAttachment): number {
  return Math.min(MAX_ASPECT, Math.max(MIN_ASPECT, aspectFor(media)))
}

export function isVideo(media?: MediaAttachment): boolean {
  return media?.type === 'video' || media?.type === 'gifv'
}
