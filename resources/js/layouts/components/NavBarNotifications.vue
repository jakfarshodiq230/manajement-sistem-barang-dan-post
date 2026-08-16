<script setup>
import { onMounted, onUnmounted, ref } from 'vue'

const notifications = ref([])
const pollingInterval = ref(null)

const fetchNotifications = async () => {
  try {
    const response = await $api('/apps/notifications')

    notifications.value = response || []
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
  }
}

onMounted(() => {
  fetchNotifications()

  // Poll every 15 seconds
  pollingInterval.value = setInterval(fetchNotifications, 15000)
})

onUnmounted(() => {
  if (pollingInterval.value) clearInterval(pollingInterval.value)
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
  // Database notification API currently only supports markAsRead.
  // We can just visually mark them unread or add an endpoint for it if really needed.
  notifications.value.forEach(item => {
    if (notificationIds.includes(item.id)) item.isSeen = false
  })
}

const handleNotificationClick = async notification => {
  if (!notification.isSeen) {
    await markRead([notification.id])
  }
    
  if (notification.url) {
    // Navigate if there's an attached URL
    window.location.href = notification.url
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
