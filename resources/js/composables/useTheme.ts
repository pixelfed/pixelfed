import { computed, readonly, ref, watchEffect } from 'vue'

export type ThemePreference = 'system' | 'light' | 'dark'
export type ThemeScheme = 'light' | 'dark'

const STORAGE_KEY = 'pf:theme'
const media = window.matchMedia('(prefers-color-scheme: dark)')

function read(): ThemePreference {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    return stored === 'light' || stored === 'dark' ? stored : 'system'
  } catch {
    return 'system'
  }
}

const preference = ref<ThemePreference>(read())
const systemDark = ref(media.matches)

const scheme = computed<ThemeScheme>(() => {
  if (preference.value === 'system') {
    return systemDark.value ? 'dark' : 'light'
  }
  return preference.value
})

function setPreference(value: ThemePreference) {
  preference.value = value
  try {
    if (value === 'system') {
      localStorage.removeItem(STORAGE_KEY)
    } else {
      localStorage.setItem(STORAGE_KEY, value)
    }
  } catch {}
}

export function initTheme() {
  media.addEventListener('change', (event) => {
    systemDark.value = event.matches
  })
  watchEffect(() => {
    document.documentElement.dataset.theme = scheme.value
  })
}

export function useTheme() {
  return {
    preference: readonly(preference),
    scheme,
    setPreference,
  }
}
