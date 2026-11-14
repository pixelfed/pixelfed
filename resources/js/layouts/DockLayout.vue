<script setup lang="ts">
withDefaults(
  defineProps<{
    wide?: boolean
    full?: boolean
  }>(),
  {
    wide: false,
    full: false,
  },
)
</script>

<template>
  <div class="flex flex-col bg-bg text-ink" :class="full ? 'h-dvh' : 'min-h-dvh'">
    <header class="flex min-h-[68px] shrink-0 items-center justify-between px-4 py-3 sm:px-8">
      <slot name="header" />
    </header>

    <main v-if="full" class="mx-auto flex min-h-0 w-full max-w-[1032px] flex-1 px-4 pt-2 pb-6 sm:px-6">
      <slot />
    </main>

    <main
      v-else
      class="mx-auto flex w-full flex-1 flex-col px-4 pt-2 pb-14 sm:px-6"
      :class="wide ? 'max-w-[984px]' : 'max-w-[728px]'"
    >
      <slot />
    </main>

    <nav aria-label="Main" class="sticky bottom-6 z-10 mb-6 shrink-0 self-center">
      <slot name="nav" />
    </nav>
  </div>
</template>
