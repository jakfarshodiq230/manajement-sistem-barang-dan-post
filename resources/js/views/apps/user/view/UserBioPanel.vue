<script setup>
import UserInfoEditDialog from '@/components/dialogs/UserInfoEditDialog.vue'

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
  const roleLower = String(roleStr).toLowerCase()

  if (roleLower.includes('super admin') || roleLower.includes('admin'))
    return { color: 'primary', icon: 'ri-shield-star-line' }
  if (roleLower.includes('kasir'))
    return { color: 'success', icon: 'ri-shopping-cart-2-line' }
  if (roleLower.includes('gudang'))
    return { color: 'warning', icon: 'ri-building-2-line' }
  if (roleLower.includes('owner'))
    return { color: 'error', icon: 'ri-vip-crown-line' }
  if (roleLower.includes('manager'))
    return { color: 'info', icon: 'ri-user-settings-line' }
  
  return { color: 'secondary', icon: 'ri-user-line' }
}

const avatarText = name => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
}
</script>

<template>
  <VRow>
    <!-- SECTION User Details -->
    <VCol cols="12">
      <VCard elevation="2" v-if="props.userData" class="border">
        <VCardText class="text-center pt-8 pb-6">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded="lg"
            :size="110"
            :color="!props.userData.avatar ? 'primary' : undefined"
            :variant="!props.userData.avatar ? 'tonal' : undefined"
            class="border mb-2"
          >
            <VImg
              v-if="props.userData.avatar"
              :src="props.userData.avatar"
            />
            <span
              v-else
              class="text-h3 font-weight-bold"
            >
              {{ avatarText(props.userData.fullName || props.userData.name) }}
            </span>
          </VAvatar>

          <!-- 👉 User fullName -->
          <h4 class="text-h5 font-weight-bold mt-3 mb-1">
            {{ props.userData.fullName || props.userData.name }}
          </h4>
          <div class="text-caption text-disabled">
            ID Akun: #{{ props.userData.id }}
          </div>

          <!-- 👉 Role chip -->
          <VChip
            :color="resolveUserRoleVariant(props.userData.role).color"
            size="small"
            variant="elevated"
            class="font-weight-bold text-capitalize mt-3"
          >
            <VIcon :icon="resolveUserRoleVariant(props.userData.role).icon" size="14" class="me-1" />
            {{ Array.isArray(props.userData.role) ? props.userData.role.join(', ') : props.userData.role }}
          </VChip>
        </VCardText>

        <VDivider />

        <!-- 👉 Details -->
        <VCardText class="pa-6">
          <div class="d-flex align-center justify-space-between mb-4">
            <h6 class="text-subtitle-1 font-weight-bold mb-0">
              Informasi Biodata & Akun
            </h6>
            <VBtn
              size="x-small"
              variant="tonal"
              color="primary"
              prepend-icon="ri-edit-line"
              @click="isUserInfoEditDialogVisible = true"
            >
              Edit Profil
            </VBtn>
          </div>

          <VList class="card-list" density="compact">
            <VListItem class="px-0 py-1">
              <template #prepend>
                <VIcon icon="ri-user-line" size="18" class="me-3 text-primary" />
              </template>
              <VListItemTitle class="text-caption text-medium-emphasis">Username</VListItemTitle>
              <div class="text-body-2 font-weight-medium">@{{ props.userData.username }}</div>
            </VListItem>

            <VListItem class="px-0 py-1">
              <template #prepend>
                <VIcon icon="ri-mail-line" size="18" class="me-3 text-info" />
              </template>
              <VListItemTitle class="text-caption text-medium-emphasis">Email Resmi</VListItemTitle>
              <div class="text-body-2 font-weight-medium">{{ props.userData.email }}</div>
            </VListItem>

            <VListItem class="px-0 py-1">
              <template #prepend>
                <VIcon icon="ri-store-2-line" size="18" class="me-3 text-warning" />
              </template>
              <VListItemTitle class="text-caption text-medium-emphasis">Penugasan Cabang</VListItemTitle>
              <div class="text-body-2 font-weight-medium">
                {{ props.userData.assignments && props.userData.assignments.length > 0 ? props.userData.assignments.map(a => a.branch_name).join(', ') : 'Gudang Pusat / Semua Cabang' }}
              </div>
            </VListItem>

            <VListItem class="px-0 py-1">
              <template #prepend>
                <VIcon icon="ri-checkbox-circle-line" size="18" class="me-3 text-success" />
              </template>
              <VListItemTitle class="text-caption text-medium-emphasis">Status Akun</VListItemTitle>
              <VChip
                :color="(props.userData.status === 'Active' || props.userData.status === 1 || props.userData.status === '1') ? 'success' : 'error'"
                size="x-small"
                variant="elevated"
                class="font-weight-bold mt-1"
              >
                {{ (props.userData.status === 'Active' || props.userData.status === 1 || props.userData.status === '1') ? 'Aktif' : 'Nonaktif' }}
              </VChip>
            </VListItem>

            <VListItem class="px-0 py-1">
              <template #prepend>
                <VIcon icon="ri-phone-line" size="18" class="me-3 text-success" />
              </template>
              <VListItemTitle class="text-caption text-medium-emphasis">No. Telepon / WhatsApp</VListItemTitle>
              <div class="text-body-2 font-weight-medium">{{ props.userData.phone || '-' }}</div>
            </VListItem>

            <VListItem class="px-0 py-1">
              <template #prepend>
                <VIcon icon="ri-map-pin-line" size="18" class="me-3 text-error" />
              </template>
              <VListItemTitle class="text-caption text-medium-emphasis">Alamat Domisili</VListItemTitle>
              <div class="text-body-2 font-weight-medium text-wrap">{{ props.userData.address || '-' }}</div>
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
  --v-card-list-gap: 0.25rem;
}
</style>
