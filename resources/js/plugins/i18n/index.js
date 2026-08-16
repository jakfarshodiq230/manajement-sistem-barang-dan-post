import { createI18n } from 'vue-i18n'
import { cookieRef } from '@layouts/stores/config'
import { themeConfig } from '@themeConfig'
import { en, fr, ar, id } from 'vuetify/locale'

const messages = Object.fromEntries(Object.entries(import.meta.glob('./locales/*.json', { eager: true }))
  .map(([key, value]) => [key.slice(10, -5), value.default]))

const vuetifyLocales = { en, fr, ar, id }

Object.keys(messages).forEach(lang => {
  messages[lang] = {
    ...messages[lang],
    $vuetify: vuetifyLocales[lang] || en,
  }
})

// Ensure Indonesian is supported for Vuetify components even if id.json is missing
if (!messages['id']) {
  messages['id'] = { $vuetify: id }
}

let _i18n = null
export const getI18n = () => {
  if (_i18n === null) {
    _i18n = createI18n({
      legacy: false,
      locale: cookieRef('language', themeConfig.app.i18n.defaultLocale).value,
      fallbackLocale: 'en',
      messages,
      missingWarn: false,
      fallbackWarn: false,
    })
  }
  
  return _i18n
}
export default function (app) {
  app.use(getI18n())
}
