<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'rail' | 'dock'
  }>(),
  {
    variant: 'rail',
  },
)
</script>

<template>
  <div class="flex animate-pulse flex-col gap-3 py-3 motion-reduce:animate-none" aria-hidden="true">
    <div v-if="variant === 'rail'" class="flex h-14 items-center gap-2.5">
      <span class="size-8 rounded-full bg-tile" />
      <span class="h-3 w-28 rounded-full bg-tile" />
    </div>
    <div class="w-full bg-tile" :class="variant === 'rail' ? 'aspect-[4/5] rounded-md' : 'aspect-[3/2] rounded-sm'" />
    <div class="flex items-center gap-2.5">
      <span v-if="variant === 'dock'" class="size-8 rounded-full bg-tile" />
      <span class="h-3 w-40 rounded-full bg-tile" />
    </div>
  </div>
</template>
