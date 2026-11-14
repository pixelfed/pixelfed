<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import markUrl from '@/assets/pixelfed-mark.png'
import AppIcon from '@/components/AppIcon.vue'
import Avatar from '@/components/Avatar.vue'
import MoreMenu from '@/components/MoreMenu.vue'
import { useComposeModal } from '@/composables/useCompose'
import { useSession } from '@/composables/useSession'
import type { IconName } from '@/utils/ionicons'

interface RailItem {
  label: string
  to: string
  icon: IconName
  activeIcon: IconName
}

const items: RailItem[] = [
  { label: 'Home', to: '/', icon: 'home-outline', activeIcon: 'home' },
  { label: 'Search', to: '/search', icon: 'search-outline', activeIcon: 'search' },
  { label: 'Messages', to: '/messages', icon: 'paper-plane-outline', activeIcon: 'paper-plane' },
  { label: 'Notifications', to: '/notifications', icon: 'notifications-outline', activeIcon: 'notifications' },
]

const route = useRoute()
const { user } = useSession()
const menuOpen = ref(false)
const composer = useComposeModal()

const profilePath = computed(() => (user.value ? `/@${user.value.username}` : '/login'))

function isActive(path: string): boolean {
  return path === '/' ? route.path === '/' : route.path.startsWith(path)
}
</script>

<template>
  <RouterLink to="/" aria-label="Pixelfed home" class="flex size-nav-item items-center justify-center">
    <img :src="markUrl" alt="" class="size-8 dark:invert" />
  </RouterLink>

  <div class="mt-5 flex flex-col items-center gap-2">
    <RouterLink
      v-for="item in items"
      :key="item.to"
      :to="item.to"
      :aria-label="item.label"
      :aria-current="isActive(item.to) ? 'page' : undefined"
      class="flex size-nav-item items-center justify-center rounded-2xl hover:bg-tile aria-[current=page]:bg-tile"
    >
      <AppIcon :name="isActive(item.to) ? item.activeIcon : item.icon" :size="26" />
    </RouterLink>

    <button
      type="button"
      aria-label="Compose"
      aria-haspopup="dialog"
      class="flex size-nav-item items-center justify-center rounded-2xl hover:bg-tile"
      :class="composer.isOpen.value ? 'bg-tile' : ''"
      @click="composer.open()"
    >
      <AppIcon :name="composer.isOpen.value ? 'camera' : 'camera-outline'" :size="26" />
    </button>

    <RouterLink
      :to="profilePath"
      aria-label="Profile"
      :aria-current="isActive(profilePath) ? 'page' : undefined"
      class="flex size-nav-item items-center justify-center rounded-2xl hover:bg-tile aria-[current=page]:bg-tile"
    >
      <Avatar :src="user?.avatar" :name="user?.username" :size="28" />
    </RouterLink>
  </div>

  <div class="relative">
    <button
      type="button"
      aria-label="More"
      aria-haspopup="menu"
      :aria-expanded="menuOpen"
      class="flex size-nav-item items-center justify-center rounded-2xl hover:bg-tile aria-expanded:bg-tile"
      @click="menuOpen = !menuOpen"
    >
      <AppIcon name="menu-outline" :size="26" />
    </button>
    <MoreMenu v-if="menuOpen" placement="right-end" @close="menuOpen = false" />
  </div>
</template>
