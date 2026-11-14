<script setup lang="ts">
import { computed } from 'vue'
import type { DirectMessage } from '@/types/direct'
import { feedAspect, isVideo } from '@/utils/media'

const props = withDefaults(
  defineProps<{
    message: DirectMessage
    surface?: 'tile' | 'bg'
  }>(),
  {
    surface: 'tile',
  },
)

const text = computed(() => props.message.text?.trim() ?? '')
</script>

<template>
  <div
    class="flex max-w-[min(440px,78%)] flex-col gap-1"
    :class="message.is_author ? 'items-end self-end' : 'items-start self-start'"
  >
    <template v-for="item in message.media" :key="item.id">
      <video
        v-if="isVideo(item)"
        :src="item.url"
        :poster="item.preview_url"
        controls
        playsinline
        class="block w-60 max-w-full rounded-[20px] bg-tile"
        :style="{ aspectRatio: String(feedAspect(item)) }"
      />
      <a v-else :href="item.url" target="_blank" rel="noopener" aria-label="Open photo">
        <img
          :src="item.preview_url || item.url"
          :alt="item.description ?? ''"
          class="block w-60 max-w-full rounded-[20px] bg-tile object-cover"
          :style="{ aspectRatio: String(feedAspect(item)) }"
        />
      </a>
    </template>

    <p
      v-if="text"
      class="rounded-[20px] px-3.5 py-2.5 text-[15px] leading-[1.4] text-pretty wrap-anywhere whitespace-pre-line"
      :class="message.is_author ? 'bg-ink text-bg' : surface === 'bg' ? 'bg-bg' : 'bg-tile'"
    >
      {{ text }}
    </p>
  </div>
</template>
