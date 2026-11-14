<script setup lang="ts">
import AccountRow from '@/components/AccountRow.vue'
import Suggestions from '@/components/Suggestions.vue'
import { useLogout, useSession } from '@/composables/useSession'
import { appConfig } from '@/lib/config'

const { user } = useSession()
const logout = useLogout()
</script>

<template>
  <div class="flex flex-col gap-4">
    <AccountRow
      v-if="user"
      :name="user.username"
      :avatar="user.avatar"
      :sub="appConfig.domain"
      :to="{ name: 'profile', params: { username: user.username } }"
      :size="44"
    >
      <!-- TODO: replace with SelfAccountMenu -->
      <button
        type="button"
        class="min-h-touch pl-2 text-small font-semibold text-accent"
        :disabled="logout.isPending.value"
        @click="logout.mutate()"
      >
        Log out
      </button>
    </AccountRow>

    <Suggestions variant="list" :limit="4" />

    <!-- TODO: replace with FooterLinks -->
    <nav aria-label="Footer" class="mt-2 flex flex-wrap gap-x-3.5 gap-y-1.5 text-caption text-mute">
      <a href="/site/about">About</a>
      <a href="/site/help">Help</a>
      <a href="/site/terms">Terms</a>
      <a href="/site/privacy">Privacy</a>
      <a href="/site/language">Language</a>
    </nav>
  </div>
</template>
