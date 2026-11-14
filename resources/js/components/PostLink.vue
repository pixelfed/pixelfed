<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { usePostModal } from '@/composables/usePostModal'
import type { Status } from '@/types/pixelfed'

const props = withDefaults(
  defineProps<{
    status: Status
    siblings?: Status[]
  }>(),
  {
    siblings: () => [],
  },
)

const router = useRouter()
const { open } = usePostModal()

const post = computed(() => props.status.reblog ?? props.status)
const href = computed(
  () => router.resolve({ name: 'post', params: { username: post.value.account.acct, id: post.value.id } }).href,
)

function onClick(event: MouseEvent) {
  if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
    return
  }
  event.preventDefault()
  open(props.status, props.siblings)
}
</script>

<template>
  <a :href="href" @click="onClick">
    <slot />
  </a>
</template>
