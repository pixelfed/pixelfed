import { readonly, ref } from 'vue'
import { FEEDS, type FeedType } from '@/api/timelines'

const STORAGE_KEY = 'pf:feed'

function read(): FeedType {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    return stored && stored in FEEDS ? (stored as FeedType) : 'home'
  } catch {
    return 'home'
  }
}

const feed = ref<FeedType>(read())

function setFeed(value: FeedType) {
  feed.value = value
  try {
    localStorage.setItem(STORAGE_KEY, value)
  } catch {}
}

export function useFeed() {
  return {
    feed: readonly(feed),
    setFeed,
  }
}
