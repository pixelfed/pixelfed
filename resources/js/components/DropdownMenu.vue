<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, provide, ref, watch } from 'vue'
import ModalShell from '@/components/ModalShell.vue'
import { dropdownKey, type DropdownPresentation } from '@/composables/useDropdown'
import { useLayout } from '@/composables/useLayout'

const GAP = 8
const MARGIN = 8

const props = withDefaults(
  defineProps<{
    label: string
    mode?: 'auto' | 'menu' | 'modal'
    align?: 'start' | 'end'
    title?: string
    width?: number
  }>(),
  {
    mode: 'auto',
    align: 'end',
    title: '',
    width: 248,
  },
)

const emit = defineEmits<{
  open: []
  close: []
}>()

const { isCompact } = useLayout()

const isOpen = ref(false)
const anchor = ref<HTMLElement | null>(null)
const menu = ref<HTMLElement | null>(null)
const position = ref<{ top: number; left: number; maxHeight: number } | null>(null)

const presentation = computed<DropdownPresentation>(() =>
  props.mode === 'modal' || (props.mode === 'auto' && isCompact.value) ? 'modal' : 'menu',
)
const menuStyle = computed(() => ({
  width: `${props.width}px`,
  top: `${position.value?.top ?? 0}px`,
  left: `${position.value?.left ?? 0}px`,
  maxHeight: position.value ? `${position.value.maxHeight}px` : undefined,
  visibility: position.value ? ('visible' as const) : ('hidden' as const),
}))

function items(): HTMLElement[] {
  return Array.from(menu.value?.querySelectorAll<HTMLElement>('[role="menuitem"]:not([disabled])') ?? [])
}

function place() {
  const trigger = anchor.value?.getBoundingClientRect()
  const el = menu.value
  if (!trigger || !el) {
    return
  }

  const viewportWidth = document.documentElement.clientWidth
  const viewportHeight = window.innerHeight
  const width = el.offsetWidth
  const height = Math.min(el.scrollHeight, viewportHeight - MARGIN * 2)
  const below = trigger.bottom + GAP
  const above = trigger.top - GAP - height
  const fitsBelow = below + height <= viewportHeight - MARGIN

  const top = fitsBelow || above < MARGIN ? Math.min(below, viewportHeight - MARGIN - height) : above
  const preferred = props.align === 'end' ? trigger.right - width : trigger.left
  const left = Math.min(Math.max(MARGIN, preferred), Math.max(MARGIN, viewportWidth - MARGIN - width))

  position.value = { top: Math.max(MARGIN, top), left, maxHeight: viewportHeight - MARGIN * 2 }
}

function focusTrigger() {
  anchor.value?.querySelector<HTMLElement>('button, a, [tabindex]')?.focus()
}

function open() {
  if (isOpen.value) {
    return
  }
  isOpen.value = true
  emit('open')
}

function close(restoreFocus = false) {
  if (!isOpen.value) {
    return
  }
  isOpen.value = false
  position.value = null
  emit('close')
  if (restoreFocus) {
    focusTrigger()
  }
}

function toggle() {
  if (isOpen.value) {
    close()
  } else {
    open()
  }
}

function onPointerDown(event: PointerEvent) {
  const target = event.target as Node
  if (!menu.value?.contains(target) && !anchor.value?.contains(target)) {
    close()
  }
}

function onMenuKeydown(event: KeyboardEvent) {
  const list = items()
  const index = list.indexOf(document.activeElement as HTMLElement)

  if (event.key === 'Escape') {
    event.preventDefault()
    event.stopPropagation()
    close(true)
  } else if (event.key === 'Tab') {
    close()
  } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
    event.preventDefault()
    event.stopPropagation()
    const step = event.key === 'ArrowDown' ? 1 : -1
    list[(index + step + list.length) % list.length]?.focus()
  } else if (event.key === 'Home' || event.key === 'End') {
    event.preventDefault()
    event.stopPropagation()
    list.at(event.key === 'Home' ? 0 : -1)?.focus()
  } else if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
    event.stopPropagation()
  }
}

function listen(active: boolean) {
  const method = active ? 'addEventListener' : 'removeEventListener'
  document[method]('pointerdown', onPointerDown as EventListener, true)
  window[method]('resize', place)
  window[method]('scroll', place, true)
}

watch([isOpen, presentation], async ([opened, kind]) => {
  listen(false)
  if (!opened || kind !== 'menu') {
    return
  }
  await nextTick()
  place()
  listen(true)
  await nextTick()
  items()[0]?.focus({ preventScroll: true })
})

onBeforeUnmount(() => listen(false))

provide(dropdownKey, { presentation, close: () => close(presentation.value === 'menu') })

defineExpose({ open, close, toggle })
</script>

<template>
  <span ref="anchor" class="inline-flex">
    <slot name="trigger" :toggle="toggle" :expanded="isOpen" />
  </span>

  <Teleport v-if="isOpen && presentation === 'menu'" to="body">
    <div
      ref="menu"
      role="menu"
      :aria-label="label"
      class="fixed z-[60] flex flex-col overflow-y-auto rounded-2xl border border-line bg-surface p-1.5 text-ink shadow-dock"
      :style="menuStyle"
      @keydown="onMenuKeydown"
    >
      <slot :close="close" />
    </div>
  </Teleport>

  <ModalShell v-else-if="isOpen" :label="label" panel="w-full max-w-[400px]" @close="close()">
    <div role="menu" :aria-label="label" class="flex w-full flex-col overflow-y-auto rounded-tile bg-surface p-2">
      <p v-if="title" class="truncate px-4 pt-3 pb-2 text-small font-semibold text-mute">{{ title }}</p>
      <slot :close="close" />
      <div role="separator" class="mx-4 my-1.5 h-px bg-line" />
      <button
        type="button"
        role="menuitem"
        class="flex h-14 w-full items-center justify-center rounded-2xl text-[16px] font-semibold hover:bg-tile"
        @click="close()"
      >
        Cancel
      </button>
    </div>
  </ModalShell>
</template>
