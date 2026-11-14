import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, reactive, ref } from 'vue'
import { createStatus, type Visibility } from '@/api/compose'
import { keys } from '@/api/keys'
import { updateMediaDescription, uploadMedia } from '@/api/media'
import { getErrorMessage } from '@/lib/http'
import type { Place } from '@/types/pixelfed'

export interface ComposeItem {
  key: string
  file: File
  previewUrl: string
  kind: 'image' | 'video'
  alt: string
  progress: number
  state: 'uploading' | 'ready' | 'error'
  mediaId: string | null
  error: string
}

const MAX_COLLECTIONS = 3

const isOpen = ref(false)
const items = ref<ComposeItem[]>([])
const selectedKey = ref<string | null>(null)
const notice = ref('')
const draft = reactive({
  caption: '',
  visibility: 'public' as Visibility,
  sensitive: false,
  comments: true,
  place: null as Place | null,
  collectionIds: [] as string[],
})

let counter = 0

async function upload(key: string) {
  const find = () => items.value.find((item) => item.key === key)
  const target = find()
  if (!target) {
    return
  }

  target.state = 'uploading'
  target.progress = 0
  target.error = ''

  try {
    const media = await uploadMedia(target.file, (fraction) => {
      const current = find()
      if (current) {
        current.progress = fraction
      }
    })
    const current = find()
    if (current) {
      current.mediaId = media.id
      current.state = 'ready'
      current.progress = 1
    }
  } catch (error) {
    const current = find()
    if (current) {
      current.state = 'error'
      current.error = getErrorMessage(error, 'The upload failed.')
    }
  }
}

function addFiles(files: File[], limit: number, mimeTypes: string[]) {
  notice.value = ''
  const room = Math.max(0, limit - items.value.length)
  const accepted = files.filter((file) => mimeTypes.includes(file.type))

  if (accepted.length < files.length) {
    notice.value = 'Some files were skipped because their type is not supported here.'
  }
  if (accepted.length > room) {
    notice.value = `A post can hold up to ${limit} ${limit === 1 ? 'file' : 'files'}.`
  }

  for (const file of accepted.slice(0, room)) {
    const key = `item-${++counter}`
    items.value.push({
      key,
      file,
      previewUrl: URL.createObjectURL(file),
      kind: file.type.startsWith('video/') ? 'video' : 'image',
      alt: '',
      progress: 0,
      state: 'uploading',
      mediaId: null,
      error: '',
    })
    selectedKey.value ??= key
    upload(key)
  }
}

function removeItem(key: string) {
  const index = items.value.findIndex((item) => item.key === key)
  if (index < 0) {
    return
  }
  URL.revokeObjectURL(items.value[index].previewUrl)
  items.value.splice(index, 1)
  if (selectedKey.value === key) {
    selectedKey.value = items.value[Math.min(index, items.value.length - 1)]?.key ?? null
  }
}

function reset() {
  items.value.forEach((item) => URL.revokeObjectURL(item.previewUrl))
  items.value = []
  selectedKey.value = null
  notice.value = ''
  draft.caption = ''
  draft.visibility = 'public'
  draft.sensitive = false
  draft.comments = true
  draft.place = null
  draft.collectionIds = []
}

function toggleCollection(id: string) {
  const index = draft.collectionIds.indexOf(id)
  if (index >= 0) {
    draft.collectionIds.splice(index, 1)
  } else if (draft.collectionIds.length < MAX_COLLECTIONS) {
    draft.collectionIds.push(id)
  }
}

export function useComposeModal() {
  return {
    isOpen,
    open: () => {
      isOpen.value = true
    },
    close: () => {
      isOpen.value = false
    },
  }
}

export function useCompose() {
  const queryClient = useQueryClient()

  const selected = computed(() => items.value.find((item) => item.key === selectedKey.value) ?? null)
  const selectedIndex = computed(() => items.value.findIndex((item) => item.key === selectedKey.value))
  const isUploading = computed(() => items.value.some((item) => item.state === 'uploading'))
  const hasFailed = computed(() => items.value.some((item) => item.state === 'error'))
  const isEmpty = computed(() => items.value.length === 0)

  const publish = useMutation({
    mutationFn: async () => {
      const ready = items.value.filter((item) => item.state === 'ready' && item.mediaId)

      await Promise.all(
        ready
          .filter((item) => item.alt.trim())
          .map((item) => updateMediaDescription(item.mediaId as string, item.alt.trim())),
      )

      return createStatus({
        status: draft.caption.trim(),
        media_ids: ready.map((item) => item.mediaId as string),
        visibility: draft.visibility,
        sensitive: draft.sensitive,
        comments_disabled: !draft.comments,
        ...(draft.place ? { place_id: draft.place.id } : {}),
        ...(draft.collectionIds.length > 0 ? { collection_ids: [...draft.collectionIds] } : {}),
      })
    },
    onSuccess: () => {
      reset()
      queryClient.invalidateQueries({ queryKey: keys.timelines() })
      queryClient.invalidateQueries({ queryKey: keys.accounts() })
      queryClient.invalidateQueries({ queryKey: keys.collections() })
    },
  })

  return {
    items,
    selected,
    selectedIndex,
    selectedKey,
    draft,
    notice,
    isEmpty,
    isUploading,
    hasFailed,
    maxCollections: MAX_COLLECTIONS,
    addFiles,
    removeItem,
    retry: upload,
    reset,
    toggleCollection,
    publish,
  }
}
