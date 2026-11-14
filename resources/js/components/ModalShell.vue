<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useModalStack } from '@/composables/useModalStack'

withDefaults(
  defineProps<{
    label: string
    padding?: string
    panel?: string
  }>(),
  {
    padding: 'p-6',
    panel: 'w-full max-w-[480px]',
  },
)

const emit = defineEmits<{
  close: []
}>()

const dialog = ref<HTMLElement | null>(null)
const stack = useModalStack()
let restore: HTMLElement | null = null

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && stack.isTop()) {
    emit('close')
  }
}

onMounted(() => {
  restore = document.activeElement instanceof HTMLElement ? document.activeElement : null
  stack.push()
  window.addEventListener('keydown', onKeydown)
  dialog.value?.focus()
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  stack.pop()
  restore?.focus()
})
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 text-ink"
      :class="padding"
      @click.self="emit('close')"
    >
      <slot name="outside" />
      <div
        ref="dialog"
        role="dialog"
        aria-modal="true"
        :aria-label="label"
        tabindex="-1"
        class="flex max-h-full outline-none"
        :class="panel"
      >
        <slot />
      </div>
    </div>
  </Teleport>
</template>
