export interface Account {
  id: string
  username: string
  acct: string
  display_name: string
  avatar: string
  note?: string
  note_text?: string
  website?: string | null
  locked?: boolean
  followers_count: number
  following_count: number
  statuses_count: number
  pronouns?: string[]
  location?: string | null
  is_admin?: boolean
  created_at?: string
  moved?: MovedAccount | null
  [key: string]: any
}

export type MovedAccount = Pick<Account, 'id' | 'acct' | 'avatar'>

export interface MediaAttachment {
  id: string
  type: string
  url: string
  preview_url?: string
  remote_url?: string | null
  blurhash?: string
  description?: string | null
  meta?: {
    original?: { width: number; height: number; aspect: number }
    [key: string]: any
  }
  [key: string]: any
}

export interface Status {
  id: string
  content: string
  content_text?: string
  created_at: string
  favourited: boolean
  favourites_count: number
  reblogged: boolean
  reblogs_count: number
  bookmarked?: boolean
  reply_count?: number
  sensitive?: boolean
  spoiler_text?: string
  visibility?: string
  pf_type?: string
  media_attachments?: MediaAttachment[]
  account: Account
  reblog?: Status | null
  in_reply_to_id?: string | null
  place?: Place | null
  [key: string]: any
}

export interface Place {
  id: string | number
  slug?: string
  name: string
  country?: string | null
  url?: string
}

export interface Relationship {
  id: string
  following: boolean
  followed_by: boolean
  requested: boolean
  blocking?: boolean
  muting?: boolean
  domain_blocking?: boolean
  [key: string]: any
}

export interface Hashtag {
  name: string
  url: string
  count?: number
}

export interface SearchResults {
  accounts: Account[]
  hashtags: Hashtag[]
  statuses: Status[]
}

export interface InstanceRule {
  id: string
  text: string
}

export interface Instance {
  uri: string
  title: string
  short_description?: string | null
  description?: string | null
  thumbnail?: string | null
  registrations?: boolean
  stats?: {
    user_count: number
    status_count: number
    domain_count: number
  }
  rules?: InstanceRule[]
  [key: string]: any
}

export interface StatusContext {
  ancestors: Status[]
  descendants: Status[]
}

export interface Tag {
  name: string
  url: string
  following: boolean
  count?: number
}

export interface Collection {
  id: string
  pid: string
  visibility: string
  title: string | null
  description: string | null
  thumb: string
  url: string
  updated_at: string | null
  published_at: string | null
  avatar?: string
  username?: string
  post_count?: number
}

export type NotificationType =
  | 'favourite'
  | 'comment'
  | 'mention'
  | 'follow'
  | 'follow_request'
  | 'reblog'
  | 'share'
  | 'direct'
  | 'tagged'
  | 'story:react'
  | 'story:comment'
  | (string & {})

export interface Notification {
  id: string
  type: NotificationType
  created_at: string
  account?: Account
  status?: Status
  relationship?: Relationship
  direct?: { conversation_id: string; message_id: string }
}

export interface SiteConfig {
  uploader: {
    max_photo_size: number
    max_caption_length: number
    max_altext_length: number
    album_limit: number
    media_types: string
    mime_types: string[]
  }
  account: {
    max_avatar_size: number
    max_bio_length: number
    max_name_length: number
  }
  features: {
    stories: boolean
    video: boolean
    [key: string]: any
  }
  [key: string]: any
}
