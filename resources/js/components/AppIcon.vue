<script setup lang="ts">
import { computed } from 'vue'
import { ionicons, type IconName } from '@/utils/ionicons'

const props = withDefaults(
  defineProps<{
    name: IconName
    size?: number
  }>(),
  {
    size: 24,
  },
)

const body = computed(() => ionicons[props.name])
</script>

<template>
  <svg
    viewBox="0 0 512 512"
    :width="size"
    :height="size"
    fill="currentColor"
    aria-hidden="true"
    class="block shrink-0"
    v-html="body"
  />
</template>
