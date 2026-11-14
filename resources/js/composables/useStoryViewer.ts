import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, ref } from 'vue'
import { keys } from '@/api/keys'
import { markStorySeen, replyToStory } from '@/api/stories'
import { useStories } from '@/composables/useStories'
import type { StoryCarouselResponse, StoryNode } from '@/types/stories'

const openNodeId = ref<string | null>(null)
const itemIndex = ref(0)

function firstUnseen(node: StoryNode): number {
  const index = node.stories.findIndex((story) => !story.viewed)
  return index < 0 ? 0 : index
}

export function useStoryViewerState() {
  return {
    isOpen: computed(() => openNodeId.value !== null),
    open: (node: StoryNode) => {
      if (node.stories.length === 0) {
        return
      }
      itemIndex.value = firstUnseen(node)
      openNodeId.value = node.id
    },
  }
}

export function useStoryViewer() {
  const queryClient = useQueryClient()
  const stories = useStories()

  const nodes = computed(() => (stories.data.value ?? []).filter((node) => node.stories.length > 0))
  const nodeIndex = computed(() => nodes.value.findIndex((node) => node.id === openNodeId.value))
  const node = computed(() => nodes.value[nodeIndex.value] ?? null)
  const item = computed(() => node.value?.stories[itemIndex.value] ?? null)
  const previousNode = computed(() => (nodeIndex.value > 0 ? nodes.value[nodeIndex.value - 1] : null))
  const nextNode = computed(() =>
    nodeIndex.value >= 0 && nodeIndex.value < nodes.value.length - 1 ? nodes.value[nodeIndex.value + 1] : null,
  )

  function close() {
    openNodeId.value = null
    itemIndex.value = 0
  }

  function show(target: StoryNode, index: number) {
    itemIndex.value = index
    openNodeId.value = target.id
  }

  function next() {
    if (!node.value) {
      return
    }
    if (itemIndex.value < node.value.stories.length - 1) {
      itemIndex.value += 1
    } else if (nextNode.value) {
      show(nextNode.value, firstUnseen(nextNode.value))
    } else {
      close()
    }
  }

  function previous() {
    if (itemIndex.value > 0) {
      itemIndex.value -= 1
    } else if (previousNode.value) {
      show(previousNode.value, previousNode.value.stories.length - 1)
    }
  }

  function jump(target: StoryNode) {
    show(target, firstUnseen(target))
  }

  const seen = useMutation({
    mutationFn: (id: string) => markStorySeen(id),
    onMutate: (id) => {
      queryClient.setQueryData<StoryCarouselResponse>(keys.stories(), (old) => {
        if (!old) {
          return old
        }
        return {
          ...old,
          nodes: old.nodes.map((entry) => {
            if (!entry.stories.some((story) => story.id === id)) {
              return entry
            }
            const updated = entry.stories.map((story) => (story.id === id ? { ...story, viewed: true } : story))
            return { ...entry, stories: updated, hasViewed: updated.every((story) => story.viewed) }
          }),
        }
      })
    },
  })

  const reply = useMutation({
    mutationFn: ({ id, caption }: { id: string; caption: string }) => replyToStory(id, caption),
  })

  return {
    node,
    item,
    itemIndex,
    previousNode,
    nextNode,
    close,
    next,
    previous,
    jump,
    markSeen: (id: string) => seen.mutate(id),
    reply,
  }
}
