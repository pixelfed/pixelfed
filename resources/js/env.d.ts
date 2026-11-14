import 'axios'
import 'vue-router'

declare module 'axios' {
  interface AxiosRequestConfig {
    skipAuthRedirect?: boolean
    csrfRetried?: boolean
  }
}

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    guestOnly?: boolean
    title?: string
    wide?: boolean
    guestLanding?: boolean
    full?: boolean
  }
}
