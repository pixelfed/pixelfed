<script setup lang="ts">
import { useMutation } from '@tanstack/vue-query'
import { computed, ref } from 'vue'
import {
  createReport,
  parseReportError,
  REPORT_MESSAGE_LIMIT,
  REPORT_TYPES,
  type ReportObject,
  type ReportType,
} from '@/api/reports'
import AppIcon from '@/components/AppIcon.vue'
import IconButton from '@/components/IconButton.vue'
import ModalShell from '@/components/ModalShell.vue'
import { textareaClass } from '@/utils/form'
import type { IconName } from '@/utils/ionicons'

const props = withDefaults(
  defineProps<{
    objectType: ReportObject
    objectId: string
    name?: string
  }>(),
  {
    name: '',
  },
)

const emit = defineEmits<{
  close: []
}>()

interface Outcome {
  heading: string
  title: string
  body: string
  icon: IconName
}

const NOUNS: Record<ReportObject, string> = {
  post: 'post',
  user: 'account',
  story: 'story',
  direct_message: 'message',
}

const reason = ref<ReportType | null>(null)
const message = ref('')

const report = useMutation({
  mutationFn: createReport,
})

const noun = computed(() => NOUNS[props.objectType])
const heading = computed(() => {
  if (props.objectType === 'user') {
    return props.name ? `Report ${props.name}` : 'Report account'
  }
  return props.name ? `Report ${noun.value} by ${props.name}` : `Report ${noun.value}`
})
const tooLong = computed(() => message.value.length > REPORT_MESSAGE_LIMIT)
const canSend = computed(() => !!reason.value && !tooLong.value && !report.isPending.value)
const error = computed(() => (report.isError.value ? parseReportError(report.error.value) : null))

const outcome = computed<Outcome | null>(() => {
  if (report.isSuccess.value) {
    return {
      heading: 'Report sent',
      title: 'Thanks for letting us know',
      body: `The moderators will review this ${noun.value}.`,
      icon: 'checkmark-circle',
    }
  }

  switch (error.value?.code) {
    case 'ERROR_REPORT_DUPLICATE':
      return {
        heading: 'Already reported',
        title: `You already reported this ${noun.value}`,
        body: 'The moderators have your earlier report, so there is nothing more to send.',
        icon: 'alert-circle-outline',
      }
    case 'ERROR_NO_SELF_REPORTS':
      return {
        heading: 'Report not sent',
        title: `You can't report your own ${noun.value}`,
        body: '',
        icon: 'alert-circle-outline',
      }
    case 'ERROR_INVALID_OBJECT_ID':
      return {
        heading: 'Report not sent',
        title: `This ${noun.value} is no longer available`,
        body: 'It may have been removed, so there is nothing to report.',
        icon: 'alert-circle-outline',
      }
    default:
      return null
  }
})

const failure = computed(() => {
  if (!error.value || outcome.value) {
    return ''
  }
  if (error.value.code === 'ERROR_MESSAGE_TOO_LONG') {
    return `Keep the details under ${REPORT_MESSAGE_LIMIT} characters.`
  }
  if (error.value.code === 'ERROR_INVALID_PARAMS') {
    return 'Choose a reason and try again.'
  }
  return 'The report did not send. Try again.'
})

function submit() {
  if (!canSend.value || !reason.value) {
    return
  }
  report.mutate({
    report_type: reason.value,
    object_type: props.objectType,
    object_id: props.objectId,
    ...(message.value.trim() ? { message: message.value.trim() } : {}),
  })
}

const primary =
  'inline-flex h-touch items-center justify-center rounded-full bg-ink px-7 text-[15px] font-semibold text-bg disabled:opacity-40'
</script>

<template>
  <ModalShell :label="heading" @close="emit('close')">
    <div class="flex max-h-full w-full flex-col overflow-hidden rounded-tile bg-surface">
      <header class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-line pr-3 pl-7">
        <h2 class="truncate font-display text-title-sm">{{ outcome ? outcome.heading : heading }}</h2>
        <IconButton icon="close" label="Close" @click="emit('close')" />
      </header>

      <div
        v-if="outcome"
        class="flex flex-col items-center gap-4 px-7 py-10 text-center"
        :role="report.isSuccess.value ? 'status' : 'alert'"
      >
        <AppIcon :name="outcome.icon" :size="48" />
        <div class="flex flex-col gap-1">
          <p class="font-display text-title text-balance">{{ outcome.title }}</p>
          <p v-if="outcome.body" class="text-pretty text-mute">{{ outcome.body }}</p>
        </div>
        <button type="button" :class="primary" @click="emit('close')">Done</button>
      </div>

      <form v-else class="flex min-h-0 flex-col" @submit.prevent="submit">
        <div class="flex min-h-0 flex-col gap-4 overflow-y-auto px-7 py-5">
          <fieldset class="flex flex-col">
            <legend class="pb-2 text-small font-semibold text-mute">Why are you reporting this {{ noun }}?</legend>
            <label
              v-for="option in REPORT_TYPES"
              :key="option.value"
              class="-mx-3 flex min-h-12 cursor-pointer items-center gap-3 rounded-xl px-3 text-[15px] hover:bg-tile has-[:focus-visible]:bg-tile"
            >
              <input v-model="reason" type="radio" name="report-reason" :value="option.value" class="peer sr-only" />
              <span class="flex-1">{{ option.label }}</span>
              <span
                aria-hidden="true"
                class="flex size-[22px] shrink-0 items-center justify-center rounded-full border-[1.5px] border-edge peer-checked:border-ink peer-checked:bg-ink peer-checked:text-bg"
              >
                <AppIcon v-if="reason === option.value" name="checkmark" :size="14" />
              </span>
            </label>
          </fieldset>

          <div class="flex flex-col gap-1.5">
            <label for="report-message" class="text-small font-semibold text-mute">Details (optional)</label>
            <textarea
              id="report-message"
              v-model="message"
              rows="3"
              placeholder="Anything the moderators should know"
              :class="textareaClass"
            />
            <span class="self-end text-caption" :class="tooLong ? 'text-like' : 'text-mute'">
              {{ message.length }} / {{ REPORT_MESSAGE_LIMIT }}
            </span>
          </div>
        </div>

        <footer class="flex shrink-0 items-center justify-end gap-4 border-t border-line px-7 py-3.5">
          <span v-if="failure" class="mr-auto text-caption text-like" role="alert">{{ failure }}</span>
          <button type="submit" :class="primary" :disabled="!canSend">
            {{ report.isPending.value ? 'Sending' : 'Send report' }}
          </button>
        </footer>
      </form>
    </div>
  </ModalShell>
</template>
