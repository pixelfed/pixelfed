<script setup lang="ts">
import AppIcon from '@/components/AppIcon.vue'

withDefaults(
  defineProps<{
    placeholder?: string
  }>(),
  {
    placeholder: 'Search people, hashtags, posts',
  },
)

const model = defineModel<string>({ required: true })
</script>

<template>
  <label class="flex h-[52px] items-center gap-2.5 rounded-full bg-tile pr-1 pl-5">
    <span class="text-mute">
      <AppIcon name="search-outline" :size="20" />
    </span>
    <input
      v-model="model"
      type="search"
      :placeholder="placeholder"
      aria-label="Search"
      class="min-w-0 flex-1 bg-transparent text-[16px] outline-none placeholder:text-mute [&::-webkit-search-cancel-button]:hidden"
    />
    <button
      v-if="model"
      type="button"
      aria-label="Clear search"
      class="flex size-touch items-center justify-center rounded-full text-mute hover:bg-screen"
      @click="model = ''"
    >
      <AppIcon name="close" :size="20" />
    </button>
  </label>
</template>
