<script setup lang="ts">
import { computed, ref } from 'vue'
import { uploadMedia } from '@/api/media'
import AppIcon from '@/components/AppIcon.vue'
import { useSendMessage } from '@/composables/useDirect'
import { getErrorMessage } from '@/lib/http'

const props = withDefaults(
  defineProps<{
    conversationId: string
    surface?: 'tile' | 'bg'
  }>(),
  {
    surface: 'tile',
  },
)

const text = ref('')
const uploading = ref(false)
const failure = ref('')
const fileInput = ref<HTMLInputElement | null>(null)

const send = useSendMessage(() => props.conversationId)

const busy = computed(() => uploading.value || send.isPending.value)
const canSend = computed(() => text.value.trim().length > 0 && !busy.value)

function submit() {
  if (!canSend.value) {
    return
  }
  const message = text.value.trim()
  failure.value = ''
  send.mutate(
    { message },
    {
      onSuccess: () => {
        text.value = ''
      },
      onError: (error) => {
        failure.value = getErrorMessage(error, 'The message did not send.')
      },
    },
  )
}

async function onFile(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file || busy.value) {
    return
  }

  failure.value = ''
  uploading.value = true
  try {
    const media = await uploadMedia(file)
    await send.mutateAsync({ media_ids: [media.id] })
  } catch (error) {
    failure.value = getErrorMessage(error, 'The photo did not send.')
  } finally {
    uploading.value = false
  }
}
</script>

<template>
  <div class="flex shrink-0 flex-col gap-1.5">
    <p v-if="failure" class="px-4 text-caption text-like" role="alert">{{ failure }}</p>
    <form
      aria-label="New message"
      class="flex min-h-[52px] items-center gap-1 rounded-full pr-1.5 pl-1"
      :class="surface === 'bg' ? 'bg-bg' : 'border border-edge'"
      @submit.prevent="submit"
    >
      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        class="sr-only"
        tabindex="-1"
        aria-hidden="true"
        @change="onFile"
      />
      <button
        type="button"
        aria-label="Add a photo"
        class="flex size-touch shrink-0 items-center justify-center rounded-full hover:bg-tile disabled:opacity-40"
        :disabled="busy"
        @click="fileInput?.click()"
      >
        <AppIcon name="image-outline" :size="24" />
      </button>
      <input
        v-model="text"
        type="text"
        :placeholder="uploading ? 'Sending photo' : 'Message'"
        aria-label="Message"
        autocomplete="off"
        class="min-w-0 flex-1 bg-transparent text-[15px] outline-none placeholder:text-mute"
      />
      <button
        type="submit"
        class="min-h-touch shrink-0 px-3.5 text-label text-accent disabled:text-mute"
        :disabled="!canSend"
      >
        Send
      </button>
    </form>
  </div>
</template>
