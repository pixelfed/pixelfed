<script setup lang="ts">
import { computed } from 'vue'
import Avatar from '@/components/Avatar.vue'
import type { Account } from '@/types/pixelfed'

const props = withDefaults(
  defineProps<{
    accounts: Account[]
    size?: number
  }>(),
  {
    size: 44,
  },
)

const small = computed(() => Math.round(props.size * 0.68))
const box = computed(() => ({ width: `${props.size}px`, height: `${props.size}px` }))
</script>

<template>
  <Avatar v-if="accounts.length < 2" :src="accounts[0]?.avatar" :name="accounts[0]?.username" :size="size" />
  <span v-else class="relative block shrink-0" :style="box">
    <span class="absolute top-0 left-0">
      <Avatar :src="accounts[0].avatar" :name="accounts[0].username" :size="small" />
    </span>
    <span class="absolute right-0 bottom-0 rounded-full ring-2 ring-bg">
      <Avatar :src="accounts[1].avatar" :name="accounts[1].username" :size="small" />
    </span>
  </span>
</template>
