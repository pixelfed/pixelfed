import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useLayout } from '@/composables/useLayout'
import type { Status } from '@/types/pixelfed'

interface PostRef {
  id: string
  username: string
}

const list = ref<PostRef[]>([])

function toRef(item: Status): PostRef {
  const status = item.reblog ?? item
  return { id: status.id, username: status.account.acct }
}

export function usePostModal() {
  const route = useRoute()
  const router = useRouter()
  const { isCompact } = useLayout()

  const backgroundPath = computed<string | null>(() => {
    const state = route.fullPath ? window.history.state : null
    const path = state?.backgroundView
    return !isCompact.value && route.name === 'post' && typeof path === 'string' ? path : null
  })

  const index = computed(() => list.value.findIndex((item) => item.id === route.params.id))
  const previous = computed(() => (index.value > 0 ? list.value[index.value - 1] : null))
  const next = computed(() =>
    index.value >= 0 && index.value < list.value.length - 1 ? list.value[index.value + 1] : null,
  )

  function location(target: PostRef) {
    return { name: 'post', params: { username: target.username, id: target.id } }
  }

  function open(status: Status, siblings: Status[] = []) {
    const target = toRef(status)
    if (isCompact.value) {
      router.push(location(target))
      return
    }
    list.value = siblings.map(toRef)
    router.push({ ...location(target), state: { backgroundView: backgroundPath.value ?? route.fullPath } })
  }

  function go(target: PostRef | null) {
    if (target && backgroundPath.value) {
      router.replace({ ...location(target), state: { backgroundView: backgroundPath.value } })
    }
  }

  function close() {
    const background = backgroundPath.value
    if (window.history.state?.back) {
      router.back()
    } else {
      router.replace(background ?? '/')
    }
  }

  return {
    backgroundPath,
    previous,
    next,
    open,
    go,
    close,
  }
}
