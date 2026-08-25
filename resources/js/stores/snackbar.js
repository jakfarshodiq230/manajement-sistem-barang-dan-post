import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSnackbarStore = defineStore('snackbar', () => {
  const isVisible = ref(false)
  const message = ref('')
  const color = ref('success') // success, error, warning, info
  const timeout = ref(4000)

  const show = (msg, msgColor = 'success', msgTimeout = 4000) => {
    if (typeof msg === 'object' && msg !== null) {
      message.value = msg.text || msg.message || ''
      color.value = msg.color || msgColor
      timeout.value = msg.timeout || msgTimeout
    } else {
      message.value = msg
      color.value = msgColor
      timeout.value = msgTimeout
    }
    isVisible.value = true
  }

  const hide = () => {
    isVisible.value = false
  }

  return {
    isVisible,
    message,
    color,
    timeout,
    show,
    hide,
  }
})
