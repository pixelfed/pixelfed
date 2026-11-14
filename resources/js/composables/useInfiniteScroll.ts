import { onBeforeUnmount, watch, type Ref } from 'vue'

export function useInfiniteScroll(target: Ref<HTMLElement | null>, onReach: () => void, rootMargin = '800px') {
  let observer: IntersectionObserver | null = null

  const stop = watch(
    target,
    (el) => {
      observer?.disconnect()
      if (!el) {
        return
      }
      observer = new IntersectionObserver(
        (entries) => {
          if (entries.some((entry) => entry.isIntersecting)) {
            onReach()
          }
        },
        { rootMargin },
      )
      observer.observe(el)
    },
    { immediate: true },
  )

  onBeforeUnmount(() => {
    stop()
    observer?.disconnect()
  })
}
