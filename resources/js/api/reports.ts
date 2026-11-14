import { isAxiosError } from 'axios'
import { postJson } from '@/lib/http'

export type ReportType =
  'spam' | 'sensitive' | 'abusive' | 'underage' | 'violence' | 'copyright' | 'impersonation' | 'scam' | 'terrorism'

export type ReportObject = 'post' | 'user' | 'story' | 'direct_message'

export const REPORT_MESSAGE_LIMIT = 255

export const REPORT_TYPES: { value: ReportType; label: string }[] = [
  { value: 'spam', label: 'Spam' },
  { value: 'sensitive', label: 'Sensitive content that is not marked' },
  { value: 'abusive', label: 'Abusive or harmful' },
  { value: 'violence', label: 'Violence' },
  { value: 'scam', label: 'Scam or fraud' },
  { value: 'impersonation', label: 'Impersonation' },
  { value: 'copyright', label: 'Copyright infringement' },
  { value: 'underage', label: 'Underage account' },
  { value: 'terrorism', label: 'Terrorism' },
]

export interface ReportInput {
  report_type: ReportType
  object_type: ReportObject
  object_id: string
  message?: string
}

export function createReport(input: ReportInput): Promise<unknown> {
  return postJson('/api/v1.1/report', input)
}

export type ReportErrorCode =
  | 'ERROR_INVALID_PARAMS'
  | 'ERROR_MESSAGE_TOO_LONG'
  | 'ERROR_INVALID_OBJECT_ID'
  | 'ERROR_NO_SELF_REPORTS'
  | 'ERROR_REPORT_DUPLICATE'

export interface ReportError {
  code: ReportErrorCode | null
  message: string
}

export function parseReportError(error: unknown): ReportError {
  if (isAxiosError(error)) {
    const data = error.response?.data
    return {
      code: typeof data?.error_code === 'string' ? (data.error_code as ReportErrorCode) : null,
      message: typeof data?.msg === 'string' ? data.msg : '',
    }
  }
  return { code: null, message: '' }
}
