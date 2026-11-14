import { computed, ref } from 'vue'

const stack = ref<symbol[]>([])

export const openModalCount = computed(() => stack.value.length)

export function useModalStack() {
  const id = Symbol('modal')

  function push() {
    stack.value = [...stack.value, id]
    document.documentElement.style.overflow = 'hidden'
  }

  function pop() {
    stack.value = stack.value.filter((entry) => entry !== id)
    if (stack.value.length === 0) {
      document.documentElement.style.overflow = ''
    }
  }

  function isTop(): boolean {
    return stack.value.at(-1) === id
  }

  return {
    push,
    pop,
    isTop,
  }
}
