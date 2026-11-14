<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { FEEDS, type FeedType } from '@/api/timelines'
import AppIcon from '@/components/AppIcon.vue'
import { useFeed } from '@/composables/useFeed'
import type { IconName } from '@/utils/ionicons'

const props = withDefaults(
  defineProps<{
    size?: 'md' | 'sm'
    align?: 'start' | 'center'
  }>(),
  {
    size: 'md',
    align: 'start',
  },
)

const FEED_ICONS: Record<FeedType, IconName> = {
  home: 'home-outline',
  local: 'people-outline',
  public: 'earth-outline',
}

const { feed, setFeed } = useFeed()
const open = ref(false)
const root = ref<HTMLElement | null>(null)

const options = computed(() =>
  (Object.keys(FEEDS) as FeedType[]).map((key) => ({ key, label: FEEDS[key].label, icon: FEED_ICONS[key] })),
)

const label = computed(() => FEEDS[feed.value].label)

function choose(key: FeedType) {
  setFeed(key)
  open.value = false
}

function onDocumentClick(event: MouseEvent) {
  if (root.value && !root.value.contains(event.target as Node)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('click', onDocumentClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick))
</script>

<template>
  <div ref="root" class="relative" @keydown.esc="open = false">
    <button
      type="button"
      :aria-label="`${label} feed, switch feed`"
      aria-haspopup="menu"
      :aria-expanded="open"
      class="inline-flex min-h-touch items-center gap-1.5 font-display"
      :class="props.size === 'sm' ? 'text-title-sm' : 'text-title'"
      @click="open = !open"
    >
      {{ label }}
      <AppIcon name="chevron-down" :size="props.size === 'sm' ? 15 : 16" />
    </button>

    <div
      v-if="open"
      role="menu"
      class="absolute top-full z-20 mt-1 flex w-52 flex-col rounded-2xl border border-line bg-surface p-1.5"
      :class="props.align === 'center' ? 'left-1/2 -translate-x-1/2' : 'left-0'"
    >
      <button
        v-for="option in options"
        :key="option.key"
        type="button"
        role="menuitemradio"
        :aria-checked="option.key === feed"
        class="flex min-h-touch items-center gap-3 rounded-xl px-3 text-left text-body hover:bg-tile"
        :class="option.key === feed ? 'font-semibold' : ''"
        @click="choose(option.key)"
      >
        <AppIcon :name="option.icon" :size="20" />
        <span class="flex-1">{{ option.label }}</span>
        <AppIcon v-if="option.key === feed" name="checkmark" :size="18" />
      </button>
    </div>
  </div>
</template>
