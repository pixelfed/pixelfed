<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string
    for: string
    error?: string
    count?: number | null
    max?: number | null
  }>(),
  {
    error: '',
    count: null,
    max: null,
  },
)
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label :for="$props.for" class="text-small font-semibold text-mute">{{ label }}</label>
    <slot />
    <div v-if="error || count != null" class="flex items-start justify-between gap-3 text-caption">
      <span class="text-like" role="alert">{{ error }}</span>
      <span v-if="count != null" class="shrink-0 text-mute" :class="max != null && count > max ? 'text-like' : ''">
        {{ count }}
        <template v-if="max != null">/ {{ max }}</template>
      </span>
    </div>
  </div>
</template>
