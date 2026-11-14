import { http } from '@/lib/http'
import type { Account } from '@/types/pixelfed'

export interface ProfileInput {
  display_name: string
  note: string
  website: string
  locked: boolean
  avatar?: File | null
}

export async function updateProfile(input: ProfileInput): Promise<Account> {
  const body = new FormData()
  body.append('display_name', input.display_name)
  body.append('note', input.note)
  body.append('website', input.website)
  body.append('locked', input.locked ? '1' : '0')
  if (input.avatar) {
    body.append('avatar', input.avatar)
  }

  const res = await http.post<Account>('/api/v1/accounts/update_credentials', body)
  return res.data
}
