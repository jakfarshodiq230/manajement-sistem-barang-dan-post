import { ofetch } from 'ofetch'

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  async onRequest({ options }) {
    const accessToken = useCookie('accessToken').value
    options.headers = new Headers(options.headers || {})
    options.headers.set('Accept', 'application/json')
    if (accessToken)
      options.headers.set('Authorization', `Bearer ${accessToken}`)
  },
  async onResponseError({ response }) {
    if (response.status === 401) {
      useCookie('accessToken').value = null
      useCookie('userData').value = null
      useCookie('userAbilityRules').value = null
      if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }
  },
})
