<script setup lang="ts">
withDefaults(
  defineProps<{
    count?: number
  }>(),
  {
    count: 6,
  },
)
</script>

<template>
  <div class="grid animate-pulse grid-cols-3 gap-1 motion-reduce:animate-none" aria-hidden="true">
    <span v-for="n in count" :key="n" class="aspect-square rounded-md bg-tile" />
  </div>
</template>
