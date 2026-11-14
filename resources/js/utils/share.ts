export type ShareResult = 'shared' | 'copied' | 'failed'

export async function shareLink(url: string, title = ''): Promise<ShareResult> {
  if (typeof navigator.share === 'function') {
    try {
      await navigator.share({ url, title })
      return 'shared'
    } catch (error) {
      if (error instanceof DOMException && error.name === 'AbortError') {
        return 'shared'
      }
    }
  }

  try {
    await navigator.clipboard.writeText(url)
    return 'copied'
  } catch {
    return 'failed'
  }
}
