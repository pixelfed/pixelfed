export interface StoryItem {
  id: string
  type: 'photo' | 'video'
  url: string
  duration: number
  viewed: boolean
  created_at: string
  view_count?: number
}

export interface StoryNode {
  id: string
  username: string
  username_acct: string
  profile_id: string
  avatar: string
  is_author: boolean
  stories: StoryItem[]
  url: string
  hasViewed: boolean
}

export interface StoryCarouselResponse {
  nodes: StoryNode[]
}
