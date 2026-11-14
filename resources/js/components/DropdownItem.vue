<script setup lang="ts">
import { computed } from 'vue'
import type { RouteLocationRaw } from 'vue-router'
import AppIcon from '@/components/AppIcon.vue'
import { useDropdown } from '@/composables/useDropdown'
import type { IconName } from '@/utils/ionicons'

const props = withDefaults(
  defineProps<{
    icon?: IconName | null
    tone?: 'default' | 'danger'
    to?: RouteLocationRaw | null
    href?: string
    disabled?: boolean
    keepOpen?: boolean
    divided?: boolean
  }>(),
  {
    icon: null,
    tone: 'default',
    to: null,
    href: '',
    disabled: false,
    keepOpen: false,
    divided: false,
  },
)

const emit = defineEmits<{
  select: [event: MouseEvent]
}>()

const dropdown = useDropdown()

const isModal = computed(() => dropdown?.presentation.value === 'modal')
const tag = computed(() => (props.to ? 'RouterLink' : props.href ? 'a' : 'button'))
const attrs = computed(() => {
  if (props.to) {
    return { to: props.to }
  }
  if (props.href) {
    return { href: props.href }
  }
  return { type: 'button', disabled: props.disabled }
})

function onClick(event: MouseEvent) {
  if (props.disabled) {
    event.preventDefault()
    return
  }
  emit('select', event)
  if (!props.keepOpen) {
    dropdown?.close()
  }
}
</script>

<template>
  <div v-if="divided" role="separator" class="my-1.5 h-px bg-line" :class="isModal ? 'mx-4' : 'mx-3'" />
  <component
    :is="tag"
    v-bind="attrs"
    role="menuitem"
    class="flex w-full shrink-0 items-center gap-3 text-left outline-none hover:bg-tile focus-visible:bg-tile disabled:opacity-40"
    :class="[
      isModal ? 'h-14 rounded-2xl px-4 text-[16px]' : 'h-12 rounded-xl px-3 text-[15px]',
      tone === 'danger' ? 'font-semibold text-like' : '',
    ]"
    @click="onClick"
  >
    <AppIcon v-if="icon" :name="icon" :size="22" />
    <span class="min-w-0 flex-1 truncate">
      <slot />
    </span>
  </component>
</template>
