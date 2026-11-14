import { computed, readonly, ref, watchEffect } from 'vue'

export type LayoutName = 'rail' | 'dock'

const STORAGE_KEY = 'pf:layout'
const DEFAULT_LAYOUT: LayoutName = 'rail'
const compactMedia = window.matchMedia('(max-width: 699px)')

function read(): LayoutName {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    return stored === 'rail' || stored === 'dock' ? stored : DEFAULT_LAYOUT
  } catch {
    return DEFAULT_LAYOUT
  }
}

const layout = ref<LayoutName>(read())
const isCompact = ref(compactMedia.matches)
const active = computed<LayoutName>(() => (isCompact.value ? 'dock' : layout.value))

function setLayout(value: LayoutName) {
  layout.value = value
  try {
    localStorage.setItem(STORAGE_KEY, value)
  } catch {}
}

export function initLayout() {
  compactMedia.addEventListener('change', (event) => {
    isCompact.value = event.matches
  })
  watchEffect(() => {
    document.documentElement.dataset.layout = active.value
  })
}

export function useLayout() {
  return {
    layout: readonly(layout),
    active,
    isCompact: readonly(isCompact),
    setLayout,
  }
}
