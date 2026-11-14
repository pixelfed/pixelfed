<script setup lang="ts" generic="T extends string">
withDefaults(
  defineProps<{
    options: { key: T; label: string }[]
    label: string
    variant?: 'underline' | 'pill'
  }>(),
  {
    variant: 'underline',
  },
)

const model = defineModel<T>({ required: true })
</script>

<template>
  <div
    role="tablist"
    :aria-label="label"
    class="flex"
    :class="variant === 'pill' ? 'gap-1 self-start rounded-full bg-tile p-1' : 'gap-6 border-b border-line'"
  >
    <button
      v-for="option in options"
      :key="option.key"
      type="button"
      role="tab"
      :aria-selected="model === option.key"
      class="flex h-touch items-center text-[15px]"
      :class="
        variant === 'pill'
          ? ['rounded-full px-5 font-semibold', model === option.key ? 'bg-ink text-bg' : 'hover:bg-screen']
          : model === option.key
            ? 'font-semibold shadow-[inset_0_-2px_0_var(--color-ink)]'
            : 'font-medium text-mute'
      "
      @click="model = option.key"
    >
      {{ option.label }}
    </button>
  </div>
</template>
