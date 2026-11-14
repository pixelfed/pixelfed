<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string
    hint?: string
    disabled?: boolean
  }>(),
  {
    hint: '',
    disabled: false,
  },
)

const model = defineModel<boolean>({ required: true })
</script>

<template>
  <button
    type="button"
    role="switch"
    :aria-checked="model"
    :disabled="disabled"
    class="flex min-h-14 w-full items-center justify-between gap-4 py-2 text-left disabled:opacity-50"
    @click="model = !model"
  >
    <span class="flex min-w-0 flex-col gap-0.5">
      <span class="text-[15px]">{{ label }}</span>
      <span v-if="hint" class="text-caption text-mute">{{ hint }}</span>
    </span>
    <span
      aria-hidden="true"
      class="relative block h-7 w-12 shrink-0 rounded-full transition-colors motion-reduce:transition-none"
      :class="model ? 'bg-ink' : 'bg-faint'"
    >
      <span
        class="absolute top-[3px] left-[3px] size-[22px] rounded-full transition-transform motion-reduce:transition-none"
        :class="model ? 'translate-x-5 bg-bg' : 'bg-surface'"
      />
    </span>
  </button>
</template>
