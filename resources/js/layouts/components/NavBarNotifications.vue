<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const notifications = ref([])
const pollingInterval = ref(null)

const fetchNotifications = async () => {
  const token = useCookie('accessToken').value
  if (!token) return

  try {
    const response = await $api('/apps/notifications')

    notifications.value = response || []
  } catch (error) {
    if (error?.status !== 401) {
      console.error('Failed to fetch notifications:', error)
    }
  }
}

const onCustomRefresh = () => fetchNotifications()

onMounted(() => {
  fetchNotifications()

  // Poll every 10 seconds & listen to instant refresh events
  pollingInterval.value = setInterval(fetchNotifications, 10000)
  window.addEventListener('refresh-notifications', onCustomRefresh)
})

onUnmounted(() => {
  if (pollingInterval.value) clearInterval(pollingInterval.value)
  window.removeEventListener('refresh-notifications', onCustomRefresh)
})

const removeNotification = async notificationId => {
  try {
    await $api(`/apps/notifications/${notificationId}`, { method: 'DELETE' })
    notifications.value = notifications.value.filter(item => item.id !== notificationId)
  } catch (error) {
    console.error(error)
  }
}

const markRead = async notificationIds => {
  try {
    await $api('/apps/notifications/read', {
      method: 'POST',
      body: { ids: notificationIds },
    })
    notifications.value.forEach(item => {
      if (notificationIds.includes(item.id)) item.isSeen = true
    })
  } catch (error) {
    console.error(error)
  }
}

const markUnRead = notificationIds => {
  notifications.value.forEach(item => {
    if (notificationIds.includes(item.id)) item.isSeen = false
  })
}

const handleNotificationClick = async notification => {
  if (!notification.isSeen) {
    await markRead([notification.id])
  }
    
  if (notification.url) {
    if (notification.url.startsWith('http')) {
      window.location.href = notification.url
    } else {
      router.push(notification.url)
    }
  }
}
</script>

<template>
  <Notifications
    :notifications="notifications"
    @remove="removeNotification"
    @read="markRead"
    @unread="markUnRead"
    @click:notification="handleNotificationClick"
  />
</template>
