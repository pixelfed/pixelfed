export type TimeAgoStyle = 'short' | 'long'

const TIME_AGO_STEPS: { max: number; unit: number; short: string; long: string }[] = [
  { max: 60, unit: 1, short: 's', long: 'second' },
  { max: 3600, unit: 60, short: 'm', long: 'minute' },
  { max: 86400, unit: 3600, short: 'h', long: 'hour' },
  { max: 604800, unit: 86400, short: 'd', long: 'day' },
  { max: 2592000, unit: 604800, short: 'w', long: 'week' },
  { max: 31536000, unit: 2592000, short: 'mo', long: 'month' },
  { max: Infinity, unit: 31536000, short: 'y', long: 'year' },
]

export function timeAgo(
  input: string | number | Date | null | undefined,
  style: TimeAgoStyle = 'long',
  now: number = Date.now(),
): string {
  if (input == null) {
    return ''
  }

  const ms = input instanceof Date ? input.getTime() : new Date(input).getTime()

  if (!Number.isFinite(ms)) {
    return ''
  }

  const seconds = Math.max(0, Math.floor((now - ms) / 1000))

  if (seconds < 45) {
    return style === 'short' ? 'now' : 'just now'
  }

  const step = TIME_AGO_STEPS.find((s) => seconds < s.max) ?? TIME_AGO_STEPS[TIME_AGO_STEPS.length - 1]
  const value = Math.max(1, Math.floor(seconds / step.unit))

  if (style === 'short') {
    return `${value}${step.short}`
  }

  return `${value} ${step.long}${value === 1 ? '' : 's'} ago`
}

const countFormat = new Intl.NumberFormat()

export function formatCount(value: number | null | undefined): string {
  return countFormat.format(value ?? 0)
}

export function pluralize(count: number, one: string, many: string): string {
  return `${formatCount(count)} ${count === 1 ? one : many}`
}
