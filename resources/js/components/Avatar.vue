<script setup lang="ts">
import { computed, ref, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    src?: string | null
    name?: string
    size?: number
  }>(),
  {
    src: null,
    name: '',
    size: 32,
  },
)

const failed = ref(false)

watch(
  () => props.src,
  () => {
    failed.value = false
  },
)

const box = computed(() => ({
  width: `${props.size}px`,
  height: `${props.size}px`,
  fontSize: `${Math.round(props.size * 0.4)}px`,
}))

const userLabel = computed(() => props.name.trim() ?? 'Account')
const altText = computed(() => `${userLabel.value}'s avatar'`)
</script>

<template>
  <img
    v-if="src && !failed"
    :src="src"
    :alt="altText"
    loading="lazy"
    class="block shrink-0 rounded-full bg-tile object-cover"
    :style="box"
    @error="failed = true"
  />

  <img
    v-else
    src="/storage/avatars/default.jpg"
    :alt="altText"
    loading="lazy"
    class="block shrink-0 rounded-full bg-tile object-cover"
    :style="box"
  />
</template>
