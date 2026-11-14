<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import { useAppearance } from '@/composables/useAppearance'
import { useLogout } from '@/composables/useSession'

withDefaults(
  defineProps<{
    placement?: 'right-end' | 'bottom-end' | 'bottom-center'
  }>(),
  {
    placement: 'right-end',
  },
)

const emit = defineEmits<{
  close: []
}>()

const root = ref<HTMLElement | null>(null)
const appearance = useAppearance()
const logout = useLogout()

const PLACEMENTS = {
  'right-end': 'bottom-0 left-full ml-2',
  'bottom-end': 'top-full right-0 mt-2',
  'bottom-center': 'top-full left-1/2 mt-2 -translate-x-1/2',
}

const item = 'flex h-12 w-full items-center gap-3 rounded-xl px-3 text-left text-[15px] hover:bg-tile'

function onDocumentClick(event: MouseEvent) {
  const parent = root.value?.parentElement
  if (parent && !parent.contains(event.target as Node)) {
    emit('close')
  }
}

function openAppearance() {
  emit('close')
  appearance.open()
}

onMounted(() => document.addEventListener('click', onDocumentClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick))
</script>

<template>
  <div
    ref="root"
    role="menu"
    aria-label="More"
    class="absolute z-30 flex w-[248px] flex-col rounded-2xl border border-line bg-surface p-1.5 text-ink shadow-dock"
    :class="PLACEMENTS[placement]"
    @keydown.esc="emit('close')"
  >
    <RouterLink to="/settings" role="menuitem" :class="item" @click="emit('close')">
      <AppIcon name="settings-outline" :size="22" />
      Settings
    </RouterLink>
    <button type="button" role="menuitem" :class="item" @click="openAppearance">
      <AppIcon name="contrast-outline" :size="22" />
      Appearance
    </button>
    <RouterLink to="/bookmarks" role="menuitem" :class="item" @click="emit('close')">
      <AppIcon name="bookmark-outline" :size="22" />
      Bookmarks
    </RouterLink>
    <div class="mx-3 my-1.5 h-px bg-line" />
    <button type="button" role="menuitem" :class="item" :disabled="logout.isPending.value" @click="logout.mutate()">
      <AppIcon name="log-out-outline" :size="22" />
      Log out
    </button>
  </div>
</template>
