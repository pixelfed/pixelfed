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
  <div class="flex min-h-dvh bg-bg text-ink">
    <nav
      aria-label="Main"
      class="sticky top-0 flex h-dvh w-rail shrink-0 flex-col items-center justify-between gap-2 border-r border-line py-3.5"
    >
      <slot name="nav" />
    </nav>

    <main v-if="full" class="flex h-dvh min-w-0 flex-1">
      <slot />
    </main>

    <main v-else class="flex min-w-0 flex-1 justify-center gap-16 px-6 pb-16">
      <div class="w-full" :class="wide ? 'max-w-[936px]' : 'max-w-feed-rail'">
        <slot />
      </div>

      <aside v-if="!wide" class="hidden w-aside shrink-0 pt-7 lg:block">
        <slot name="aside" />
      </aside>
    </main>
  </div>
</template>
