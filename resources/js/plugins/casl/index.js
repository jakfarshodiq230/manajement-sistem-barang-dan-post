import { createMongoAbility } from '@casl/ability'
import { abilitiesPlugin } from '@casl/vue'

export default function (app) {
  let userAbilityRules = []
  try {
    const stored = localStorage.getItem('userAbilityRules')
    if (stored) {
      userAbilityRules = JSON.parse(stored)
    }
  } catch (e) {
    console.error(e)
  }
  const initialAbility = createMongoAbility(userAbilityRules)

  app.use(abilitiesPlugin, initialAbility, {
    useGlobalProperties: true,
  })
}
