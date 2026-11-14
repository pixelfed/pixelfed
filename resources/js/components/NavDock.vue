<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppIcon from '@/components/AppIcon.vue'
import Avatar from '@/components/Avatar.vue'
import { useSession } from '@/composables/useSession'

const route = useRoute()
const { user } = useSession()

const profilePath = computed(() => (user.value ? `/@${user.value.username}` : '/login'))

function isActive(path: string): boolean {
  return path === '/' ? route.path === '/' : route.path.startsWith(path)
}

const item = 'flex size-nav-item items-center justify-center rounded-full hover:bg-tile aria-[current=page]:bg-screen'
</script>

<template>
  <div class="inline-flex items-center gap-1 rounded-full border border-line bg-surface p-1.5 shadow-dock">
    <RouterLink to="/" aria-label="Home" :aria-current="isActive('/') ? 'page' : undefined" :class="item">
      <AppIcon :name="isActive('/') ? 'home' : 'home-outline'" :size="26" />
    </RouterLink>
    <RouterLink to="/search" aria-label="Search" :aria-current="isActive('/search') ? 'page' : undefined" :class="item">
      <AppIcon :name="isActive('/search') ? 'search' : 'search-outline'" :size="26" />
    </RouterLink>
    <RouterLink
      to="/compose"
      aria-label="Compose"
      class="flex size-nav-item items-center justify-center rounded-full bg-ink text-bg"
    >
      <AppIcon name="add" :size="28" />
    </RouterLink>
    <RouterLink
      to="/notifications"
      aria-label="Notifications"
      :aria-current="isActive('/notifications') ? 'page' : undefined"
      :class="item"
    >
      <AppIcon :name="isActive('/notifications') ? 'notifications' : 'notifications-outline'" :size="26" />
    </RouterLink>
    <RouterLink
      :to="profilePath"
      aria-label="Profile"
      :aria-current="isActive(profilePath) ? 'page' : undefined"
      :class="item"
    >
      <Avatar :src="user?.avatar" :name="user?.username" :size="28" />
    </RouterLink>
  </div>
</template>
