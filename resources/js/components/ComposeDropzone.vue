<script setup lang="ts">
import { computed, ref } from 'vue'
import AppIcon from '@/components/AppIcon.vue'

const props = defineProps<{
  limit: number
  accept: string[]
}>()

const emit = defineEmits<{
  files: [files: File[]]
}>()

const over = ref(false)
const input = ref<HTMLInputElement | null>(null)

const allowsVideo = computed(() => props.accept.some((type) => type.startsWith('video/')))

function onDrop(event: DragEvent) {
  over.value = false
  const files = Array.from(event.dataTransfer?.files ?? [])
  if (files.length > 0) {
    emit('files', files)
  }
}

function onPick(event: Event) {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files ?? [])
  target.value = ''
  if (files.length > 0) {
    emit('files', files)
  }
}
</script>

<template>
  <div
    class="flex min-h-[420px] flex-col items-center justify-center gap-5 rounded-2xl border-[1.5px] border-dashed p-8 text-center"
    :class="over ? 'border-ink bg-tile' : 'border-edge'"
    @dragover.prevent="over = true"
    @dragleave.prevent="over = false"
    @drop.prevent="onDrop"
  >
    <span class="text-dim">
      <AppIcon name="images-outline" :size="64" />
    </span>
    <div class="flex flex-col gap-1.5">
      <p class="font-display text-title">{{ allowsVideo ? 'Drag photos and videos here' : 'Drag photos here' }}</p>
      <p class="text-[15px] text-mute">Up to {{ limit }} in one post.</p>
    </div>
    <input
      ref="input"
      type="file"
      multiple
      :accept="accept.join(',')"
      class="sr-only"
      tabindex="-1"
      aria-hidden="true"
      @change="onPick"
    />
    <button
      type="button"
      class="inline-flex h-touch items-center justify-center rounded-full bg-ink px-[18px] text-[15px] font-semibold text-bg"
      @click="input?.click()"
    >
      Choose from your device
    </button>
  </div>
</template>
