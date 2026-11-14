<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue'
import IconButton from '@/components/IconButton.vue'
import ModalShell from '@/components/ModalShell.vue'
import { useAppearance } from '@/composables/useAppearance'
import { useLayout, type LayoutName } from '@/composables/useLayout'
import { useTheme, type ThemePreference } from '@/composables/useTheme'
import type { IconName } from '@/utils/ionicons'

const { close } = useAppearance()
const { layout, isCompact, setLayout } = useLayout()
const { preference, setPreference } = useTheme()

const LAYOUTS: { key: LayoutName; label: string; hint: string }[] = [
  { key: 'rail', label: 'Rail', hint: 'Navigation on the left, suggestions beside the feed.' },
  { key: 'dock', label: 'Dock', hint: 'Navigation at the bottom, larger photos.' },
]

const THEMES: { key: ThemePreference; label: string; icon: IconName }[] = [
  { key: 'system', label: 'System', icon: 'desktop-outline' },
  { key: 'light', label: 'Light', icon: 'sunny-outline' },
  { key: 'dark', label: 'Dark', icon: 'moon-outline' },
]
</script>

<template>
  <ModalShell label="Appearance" @close="close">
    <div class="flex w-full flex-col gap-6 overflow-y-auto rounded-tile bg-surface px-7 pt-6 pb-7">
      <div class="flex items-center justify-between">
        <h2 class="font-display text-title">Appearance</h2>
        <IconButton icon="close" label="Close" class="-mr-2.5" @click="close" />
      </div>

      <div class="flex flex-col gap-3">
        <h3 class="text-small font-semibold text-mute">Layout</h3>
        <div role="radiogroup" aria-label="Layout" class="flex gap-3">
          <button
            v-for="option in LAYOUTS"
            :key="option.key"
            type="button"
            role="radio"
            :aria-checked="layout === option.key"
            class="flex min-w-0 flex-1 basis-0 flex-col gap-3 rounded-2xl border-2 p-3 text-left"
            :class="layout === option.key ? 'border-ink' : 'border-line'"
            @click="setLayout(option.key)"
          >
            <span aria-hidden="true" class="relative block h-[104px] overflow-hidden rounded-[10px] bg-tile">
              <template v-if="option.key === 'rail'">
                <span class="absolute inset-y-0 left-0 w-[18px] border-r border-edge" />
                <span class="absolute top-3 left-1.5 size-1.5 rounded-full bg-ink" />
                <span
                  v-for="n in 3"
                  :key="n"
                  class="absolute left-1.5 size-1.5 rounded-full bg-faint"
                  :style="{ top: `${12 + n * 14}px` }"
                />
                <span class="absolute top-3.5 left-[24%] h-28 w-[45%] rounded-sm bg-faint" />
                <span class="absolute top-3.5 right-[6%] h-10 w-[19%] rounded-sm bg-screen" />
              </template>
              <template v-else>
                <span class="absolute top-3.5 left-1/2 h-28 w-[65%] -translate-x-1/2 rounded-sm bg-faint" />
                <span class="absolute bottom-2.5 left-1/2 h-4 w-16 -translate-x-1/2 rounded-full bg-ink" />
              </template>
            </span>
            <span class="flex flex-col gap-0.5 px-1 pb-1">
              <span class="flex items-center justify-between text-[15px] font-semibold">
                {{ option.label }}
                <AppIcon v-if="layout === option.key" name="checkmark-circle" :size="20" />
              </span>
              <span class="text-caption text-mute">{{ option.hint }}</span>
            </span>
          </button>
        </div>
        <p v-if="isCompact" class="text-caption text-mute">On small screens the app always uses Dock.</p>
      </div>

      <div class="flex flex-col gap-3">
        <h3 class="text-small font-semibold text-mute">Theme</h3>
        <div role="radiogroup" aria-label="Theme" class="flex gap-1 rounded-full bg-tile p-1">
          <button
            v-for="option in THEMES"
            :key="option.key"
            type="button"
            role="radio"
            :aria-checked="preference === option.key"
            class="flex h-touch flex-1 basis-0 items-center justify-center gap-2 rounded-full text-[15px] font-semibold"
            :class="preference === option.key ? 'bg-ink text-bg' : ''"
            @click="setPreference(option.key)"
          >
            <AppIcon :name="option.icon" :size="18" />
            {{ option.label }}
          </button>
        </div>
      </div>
    </div>
  </ModalShell>
</template>
