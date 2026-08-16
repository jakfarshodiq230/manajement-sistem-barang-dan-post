<script setup>
import { ref, onMounted } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

const notifications = ref([])
const isLoading = ref(false)
const snackbar = useSnackbarStore()

const fetchNotifications = async () => {
  isLoading.value = true
  try {
    const response = await $api('/apps/notifications?all=true')

    notifications.value = response || []
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
    snackbar.show('Gagal memuat notifikasi', 'error')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchNotifications()
})

const removeNotification = async notificationId => {
  try {
    await $api(`/apps/notifications/${notificationId}`, { method: 'DELETE' })
    notifications.value = notifications.value.filter(item => item.id !== notificationId)
    snackbar.show('Notifikasi berhasil dihapus', 'success')
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menghapus notifikasi', 'error')
  }
}

const markRead = async notificationId => {
  try {
    await $api('/apps/notifications/read', {
      method: 'POST',
      body: { ids: [notificationId] },
    })

    const index = notifications.value.findIndex(item => item.id === notificationId)
    if (index !== -1) {
      notifications.value[index].isSeen = true
    }
  } catch (error) {
    console.error(error)
  }
}

const handleNotificationClick = async notification => {
  if (!notification.isSeen) {
    await markRead(notification.id)
  }
    
  if (notification.url) {
    window.location.href = notification.url
  }
}
</script>

<template>
  <div>
    <VCard title="Semua Notifikasi">
      <VCardText>
        <VProgressLinear
          v-if="isLoading"
          indeterminate
          color="primary"
          class="mb-4"
        />

        <VList v-if="notifications.length > 0">
          <template
            v-for="(notification, index) in notifications"
            :key="notification.id"
          >
            <VDivider v-if="index > 0" />
            
            <VListItem
              class="pa-4"
              :class="[{ 'bg-grey-50': !notification.isSeen }]"
              style="cursor: pointer;"
              @click="handleNotificationClick(notification)"
            >
              <template #prepend>
                <VAvatar
                  :color="notification.color || 'primary'"
                  variant="tonal"
                  class="mr-4"
                >
                  <VIcon :icon="notification.icon || 'ri-notification-3-line'" />
                </VAvatar>
              </template>

              <VListItemTitle class="text-subtitle-1 font-weight-medium mb-1">
                {{ notification.title }}
                <VChip
                  v-if="!notification.isSeen"
                  color="error"
                  size="x-small"
                  class="ml-2"
                >
                  Baru
                </VChip>
              </VListItemTitle>
              
              <VListItemSubtitle
                class="text-body-2 mb-2"
                style="white-space: normal;"
              >
                {{ notification.subtitle }}
              </VListItemSubtitle>
              
              <div class="text-caption text-medium-emphasis">
                {{ notification.time }}
              </div>

              <template #append>
                <div class="d-flex gap-2">
                  <VBtn
                    v-if="!notification.isSeen"
                    icon
                    variant="text"
                    color="primary"
                    size="small"
                    title="Tandai Sudah Dibaca"
                    @click.stop="markRead(notification.id)"
                  >
                    <VIcon icon="ri-check-line" />
                  </VBtn>
                  <VBtn
                    icon
                    variant="text"
                    color="error"
                    size="small"
                    title="Hapus"
                    @click.stop="removeNotification(notification.id)"
                  >
                    <VIcon icon="ri-delete-bin-line" />
                  </VBtn>
                </div>
              </template>
            </VListItem>
          </template>
        </VList>
        
        <VAlert
          v-else-if="!isLoading"
          type="info"
          variant="tonal"
          class="mt-4"
        >
          Belum ada notifikasi apa pun.
        </VAlert>
      </VCardText>
    </VCard>
  </div>
</template>

<route lang="yaml">
meta:
  layout: default
</route>
