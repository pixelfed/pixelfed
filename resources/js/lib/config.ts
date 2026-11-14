const el = document.getElementById('app')

export const appConfig = {
  base: el?.dataset.base || '/',
  name: el?.dataset.name || 'Pixelfed',
  domain: el?.dataset.domain || window.location.host,
}
