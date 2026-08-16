<script setup>
const props = defineProps({
  userData: {
    type: Object,
    required: true,
  },
})

const isUserInfoEditDialogVisible = ref(false)


const resolveUserRoleVariant = role => {
  if (!role) return { color: 'primary', icon: 'ri-user-line' }
  const roleStr = Array.isArray(role) ? role[0] : role
  const roleLowerCase = String(roleStr).toLowerCase()

  if (roleLowerCase === 'subscriber')
    return {
      color: 'primary',
      icon: 'ri-user-line',
    }
  if (roleLowerCase === 'author')
    return {
      color: 'warning',
      icon: 'ri-settings-2-line',
    }
  if (roleLowerCase === 'maintainer')
    return {
      color: 'success',
      icon: 'ri-database-2-line',
    }
  if (roleLowerCase === 'editor')
    return {
      color: 'info',
      icon: 'ri-pencil-line',
    }
  if (roleLowerCase === 'admin')
    return {
      color: 'error',
      icon: 'ri-server-line',
    }
  
  return {
    color: 'primary',
    icon: 'ri-user-line',
  }
}
</script>

<template>
  <VRow>
    <!-- SECTION User Details -->
    <VCol cols="12">
      <VCard v-if="props.userData">
        <VCardText class="text-center pt-12 pb-6">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded="lg"
            :size="120"
            :color="!props.userData.avatar ? 'primary' : undefined"
            :variant="!props.userData.avatar ? 'tonal' : undefined"
          >
            <VImg
              v-if="props.userData.avatar"
              :src="props.userData.avatar"
            />
            <span
              v-else
              class="text-5xl font-weight-medium"
            >
              {{ avatarText(props.userData.fullName) }}
            </span>
          </VAvatar>

          <!-- 👉 User fullName -->
          <h5 class="text-h5 mt-4">
            {{ props.userData.fullName }}
          </h5>

          <!-- 👉 Role chip -->
          <VChip
            :color="resolveUserRoleVariant(props.userData.role).color"
            size="small"
            class="text-capitalize mt-4"
          >
            {{ props.userData.role }}
          </VChip>
        </VCardText>

        <!-- 👉 Details -->
        <VCardText class="pb-6">
          <h5 class="text-h5">
            Details
          </h5>

          <VDivider class="my-4" />

          <!-- 👉 User Details list -->
          <VList class="card-list">
            <VListItem>
              <VListItemTitle class="text-sm">
                <span class="font-weight-medium">Username:</span>
                <span class="text-body-1">
                  @{{ props.userData.username }}
                </span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle class="text-sm">
                <span class="font-weight-medium">
                  Email:
                </span>
                <span class="text-body-1">{{ props.userData.email }}</span>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle class="text-sm">
                <span class="font-weight-medium">Role: </span>
                <span class="text-capitalize text-body-1">{{ props.userData.role }}</span>
              </VListItemTitle>
            </VListItem>
          </VList>
        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->
  </VRow>

  <!-- 👉 Edit user info dialog -->
  <UserInfoEditDialog
    v-model:is-dialog-visible="isUserInfoEditDialogVisible"
    :user-data="props.userData"
  />
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 0.5rem;
}

.current-plan {
  border: 2px solid rgb(var(--v-theme-primary));
}

.text-capitalize {
  text-transform: capitalize !important;
}
</style>
