import { ref } from 'vue'

const isOpen = ref(false)

export function useAppearance() {
  return {
    isOpen,
    open: () => {
      isOpen.value = true
    },
    close: () => {
      isOpen.value = false
    },
  }
}
