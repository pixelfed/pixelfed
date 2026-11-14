<script setup lang="ts">
import { computed, ref } from 'vue'
import DropdownItem from '@/components/DropdownItem.vue'
import DropdownMenu from '@/components/DropdownMenu.vue'
import IconButton from '@/components/IconButton.vue'
import ReportModal from '@/components/ReportModal.vue'
import { useSession } from '@/composables/useSession'
import type { Status } from '@/types/pixelfed'

const props = withDefaults(
  defineProps<{
    status: Status
    mode?: 'auto' | 'menu' | 'modal'
    size?: number
  }>(),
  {
    mode: 'auto',
    size: 20,
  },
)

const { user } = useSession()

const dropdown = ref<InstanceType<typeof DropdownMenu> | null>(null)
const copied = ref(false)
const reporting = ref(false)

const post = computed(() => props.status.reblog ?? props.status)
const isSelf = computed(() => user.value?.id === post.value.account.id)
const postRoute = computed(() => ({
  name: 'post',
  params: { username: post.value.account.acct, id: post.value.id },
}))
const profileRoute = computed(() => ({ name: 'profile', params: { username: post.value.account.acct } }))

async function copyLink() {
  try {
    await navigator.clipboard.writeText(post.value.url)
    copied.value = true
    setTimeout(() => {
      copied.value = false
      dropdown.value?.close()
    }, 900)
  } catch {
    dropdown.value?.close()
  }
}
</script>

<template>
  <DropdownMenu ref="dropdown" label="Post options" :mode="mode" :title="`Post by ${post.account.acct}`">
    <template #trigger="{ toggle, expanded }">
      <IconButton
        icon="ellipsis-horizontal"
        label="More options"
        :size="size"
        aria-haspopup="menu"
        :aria-expanded="expanded"
        @click="toggle"
      />
    </template>

    <DropdownItem icon="open-outline" :to="postRoute">Go to post</DropdownItem>
    <DropdownItem icon="person-outline" :to="profileRoute">View profile</DropdownItem>
    <DropdownItem icon="link-outline" keep-open @select="copyLink">
      {{ copied ? 'Link copied' : 'Copy link' }}
    </DropdownItem>
    <DropdownItem v-if="user && !isSelf" icon="flag-outline" tone="danger" divided @select="reporting = true">
      Report
    </DropdownItem>
  </DropdownMenu>

  <ReportModal
    v-if="reporting"
    object-type="post"
    :object-id="post.id"
    :name="post.account.acct"
    @close="reporting = false"
  />
</template>
