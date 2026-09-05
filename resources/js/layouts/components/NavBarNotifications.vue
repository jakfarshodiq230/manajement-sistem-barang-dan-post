<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const notifications = ref([])
const currentHash = ref('')
const pollingInterval = ref(null)

const fetchNotifications = async (force = false) => {
  const token = useCookie('accessToken').value
  if (!token) return

  // Skip polling if tab is minimized / in background, unless forced
  if (typeof document !== 'undefined' && document.hidden && !force) {
    return
  }

  try {
    const response = await $api('/apps/notifications', {
      query: {
        hash: force ? undefined : (currentHash.value || undefined),
      },
    })

    // If server reports no changes, do nothing (0 CPU / 0 re-render)
    if (response?.not_modified) {
      return
    }

    if (response?.hash) {
      currentHash.value = response.hash
      notifications.value = response.data || []
    } else if (Array.isArray(response)) {
      notifications.value = response
    }
  } catch (error) {
    if (error?.status !== 401 && error?.statusCode !== 304) {
      console.error('Failed to fetch notifications:', error)
    }
  }
}

const onCustomRefresh = () => fetchNotifications(true)

const handleVisibilityChange = () => {
  if (typeof document !== 'undefined' && !document.hidden) {
    // When user returns to tab, perform a lightweight check
    fetchNotifications()
  }
}

onMounted(() => {
  fetchNotifications(true)

  // Polling with ultra-lightweight checksum check (20s)
  pollingInterval.value = setInterval(() => fetchNotifications(false), 20000)
  
  if (typeof document !== 'undefined') {
    document.addEventListener('visibilitychange', handleVisibilityChange)
  }
  window.addEventListener('refresh-notifications', onCustomRefresh)
})

onUnmounted(() => {
  if (pollingInterval.value) clearInterval(pollingInterval.value)
  if (typeof document !== 'undefined') {
    document.removeEventListener('visibilitychange', handleVisibilityChange)
  }
  window.removeEventListener('refresh-notifications', onCustomRefresh)
})

const removeNotification = async notificationId => {
  try {
    await $api(`/apps/notifications/${notificationId}`, { method: 'DELETE' })
    notifications.value = notifications.value.filter(item => item.id !== notificationId)
    currentHash.value = '' // invalidate cache hash
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
    currentHash.value = '' // invalidate cache hash
  } catch (error) {
    console.error(error)
  }
}

const markUnRead = notificationIds => {
  notifications.value.forEach(item => {
    if (notificationIds.includes(item.id)) item.isSeen = false
  })
  currentHash.value = ''
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
