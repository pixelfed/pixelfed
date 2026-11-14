<script setup lang="ts">
import { computed } from 'vue'
import { RouterView, useRoute, useRouter, type RouteLocationNormalizedLoaded } from 'vue-router'
import AppearanceModal from '@/components/AppearanceModal.vue'
import ComposeModal from '@/components/ComposeModal.vue'
import DockHeader from '@/components/DockHeader.vue'
import NavDock from '@/components/NavDock.vue'
import NavRail from '@/components/NavRail.vue'
import PostModal from '@/components/PostModal.vue'
import StoryViewer from '@/components/StoryViewer.vue'
import { useAppearance } from '@/composables/useAppearance'
import { useComposeModal } from '@/composables/useCompose'
import { useLayout } from '@/composables/useLayout'
import { usePostModal } from '@/composables/usePostModal'
import { useSession } from '@/composables/useSession'
import { useStoryViewerState } from '@/composables/useStoryViewer'
import LandingPage from '@/pages/LandingPage.vue'
import DockLayout from './DockLayout.vue'
import RailLayout from './RailLayout.vue'

const route = useRoute()
const router = useRouter()
const { active } = useLayout()
const { user } = useSession()
const { backgroundPath } = usePostModal()
const appearance = useAppearance()
const composer = useComposeModal()
const stories = useStoryViewerState()

const view = computed<RouteLocationNormalizedLoaded>(() =>
  backgroundPath.value ? (router.resolve(backgroundPath.value) as RouteLocationNormalizedLoaded) : route,
)
const wide = computed(() => !!view.value.meta.wide)
const full = computed(() => !!view.value.meta.full)
const showLanding = computed(() => !!view.value.meta.guestLanding && !user.value)
</script>

<template>
  <LandingPage v-if="showLanding" />

  <RailLayout v-else-if="active === 'rail'" :wide="wide" :full="full">
    <template #nav>
      <NavRail />
    </template>
    <RouterView :route="view" />
    <template #aside>
      <RouterView :route="view" name="aside" />
    </template>
  </RailLayout>

  <DockLayout v-else :wide="wide" :full="full">
    <template #header>
      <DockHeader />
    </template>
    <RouterView :route="view" />
    <template #nav>
      <NavDock />
    </template>
  </DockLayout>

  <PostModal v-if="backgroundPath" />
  <AppearanceModal v-if="appearance.isOpen.value" />
  <ComposeModal v-if="composer.isOpen.value" />
  <StoryViewer v-if="stories.isOpen.value" />
</template>
