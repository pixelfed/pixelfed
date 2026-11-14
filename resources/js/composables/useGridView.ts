import { computed, ref } from 'vue'
import { useLayout } from '@/composables/useLayout'

export type GridView = 'grid' | 'feed' | 'masonry'

const STORAGE_KEY = 'pf:view'
const VIEWS: GridView[] = ['grid', 'feed', 'masonry']

function read(): GridView | null {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    return VIEWS.includes(stored as GridView) ? (stored as GridView) : null
  } catch {
    return null
  }
}

const chosen = ref<GridView | null>(read())

export function useGridView() {
  const { active } = useLayout()

  const view = computed<GridView>(() => chosen.value ?? (active.value === 'dock' ? 'masonry' : 'grid'))

  function setView(value: GridView) {
    chosen.value = value
    try {
      localStorage.setItem(STORAGE_KEY, value)
    } catch {}
  }

  return {
    view,
    setView,
  }
}
