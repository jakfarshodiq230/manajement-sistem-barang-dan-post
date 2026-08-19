<script setup>
import { avatarText } from '@core/utils/formatters'

const searchQuery = ref('')
const selectedRole = ref()
const selectedStatus = ref()
const selectedRows = ref([])

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const updateOptions = options => {
  page.value = options.page
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// Headers
const headers = [
  {
    title: 'Pengguna',
    key: 'user',
  },
  {
    title: 'Email',
    key: 'email',
  },
  {
    title: 'Peran (Role)',
    key: 'role',
  },
  {
    title: 'Status',
    key: 'status',
  },
  {
    title: 'Aksi',
    key: 'actions',
    sortable: false,
  },
]

const {
  data: usersData,
  execute: fetchUsers,
} = await useApi(createUrl('/apps/users', {
  query: {
    q: searchQuery,
    status: selectedStatus,
    role: selectedRole,
    itemsPerPage,
    page,
    sortBy,
    orderBy,
  },
}))

const users = computed(() => usersData.value?.users || [])
const totalUsers = computed(() => usersData.value?.totalUsers || 0)

// Real POS Roles
const roles = [
  { title: 'Super Admin', value: 'Super Admin' },
  { title: 'Owner', value: 'Owner' },
  { title: 'Admin Pusat', value: 'Admin Pusat' },
  { title: 'Admin Cabang', value: 'Admin Cabang' },
  { title: 'Kasir', value: 'Kasir' },
  { title: 'Admin Gudang', value: 'Admin Gudang' },
  { title: 'Manager', value: 'Manager' },
]

const statusOptions = [
  { title: 'Aktif', value: 'active' },
  { title: 'Nonaktif', value: 'inactive' },
  { title: 'Pending', value: 'pending' },
]

const resolveUserRoleVariant = role => {
  if (!role) return { color: 'primary', icon: 'ri-user-line' }
  const roleStr = Array.isArray(role) ? role[0] : role
  const r = String(roleStr).toLowerCase()
  if (r.includes('super') || r.includes('owner')) {
    return { color: 'error', icon: 'ri-vip-crown-line' }
  }
  if (r.includes('pusat')) {
    return { color: 'primary', icon: 'ri-building-line' }
  }
  if (r.includes('cabang')) {
    return { color: 'info', icon: 'ri-store-2-line' }
  }
  if (r.includes('kasir')) {
    return { color: 'success', icon: 'ri-shopping-cart-2-line' }
  }
  if (r.includes('gudang')) {
    return { color: 'warning', icon: 'ri-archive-stack-line' }
  }
  if (r.includes('manager')) {
    return { color: 'secondary', icon: 'ri-user-star-line' }
  }
  return { color: 'primary', icon: 'ri-user-line' }
}

const resolveUserStatusVariant = stat => {
  const s = String(stat || '').toLowerCase()
  if (s === 'active' || s === 'aktif') return 'success'
  if (s === 'pending') return 'warning'
  return 'secondary'
}

const deleteUser = async id => {
  if (confirm('Apakah Anda yakin ingin menghapus akun pengguna ini?')) {
    await $api(`/apps/users/${id}`, { method: 'DELETE' })
    fetchUsers()
  }
}
</script>

<template>
  <VCard class="rounded-xl border elevation-1">
    <!-- Filter & Search Toolbar -->
    <VCardText class="pa-5">
      <VRow>
        <VCol cols="12" sm="6" md="4">
          <VTextField
            v-model="searchQuery"
            placeholder="Cari nama atau email pengguna..."
            prepend-inner-icon="ri-search-line"
            density="compact"
            variant="outlined"
            rounded="lg"
            hide-details
            clearable
          />
        </VCol>

        <VCol cols="12" sm="3" md="4">
          <VSelect
            v-model="selectedRole"
            placeholder="Filter Peran"
            :items="roles"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            rounded="lg"
            clearable
            hide-details
            prepend-inner-icon="ri-shield-user-line"
          />
        </VCol>

        <VCol cols="12" sm="3" md="4">
          <VSelect
            v-model="selectedStatus"
            placeholder="Filter Status"
            :items="statusOptions"
            item-title="title"
            item-value="value"
            density="compact"
            variant="outlined"
            rounded="lg"
            clearable
            hide-details
            prepend-inner-icon="ri-toggle-line"
          />
        </VCol>
      </VRow>
    </VCardText>

    <VDivider />

    <!-- Data Table -->
    <VDataTableServer
      v-model:model-value="selectedRows"
      v-model:items-per-page="itemsPerPage"
      :items-per-page-options="[
        { value: 10, title: '10' },
        { value: 20, title: '20' },
        { value: 50, title: '50' },
      ]"
      :items="users"
      item-value="id"
      :items-length="totalUsers"
      :headers="headers"
      class="text-no-wrap"
      @update:options="updateOptions"
    >
      <!-- User Column -->
      <template #item.user="{ item }">
        <div class="d-flex align-center py-2">
          <VAvatar
            size="38"
            :variant="!item.avatar ? 'tonal' : undefined"
            :color="!item.avatar ? resolveUserRoleVariant(item.role).color : undefined"
            class="me-3 elevation-1"
          >
            <VImg
              v-if="item.avatar"
              :src="item.avatar"
            />
            <span v-else class="font-weight-bold">{{ avatarText(item.fullName || item.name) }}</span>
          </VAvatar>
          <div class="d-flex flex-column">
            <RouterLink
              :to="{ name: 'apps-user-view-id', params: { id: item.id } }"
              class="text-high-emphasis font-weight-bold text-subtitle-2 hover-primary"
            >
              {{ item.fullName || item.name }}
            </RouterLink>
            <span class="text-caption text-medium-emphasis">ID: #{{ item.id }}</span>
          </div>
        </div>
      </template>

      <!-- Email Column -->
      <template #item.email="{ item }">
        <span class="text-body-2 text-medium-emphasis">{{ item.email }}</span>
      </template>

      <!-- Role Column -->
      <template #item.role="{ item }">
        <VChip
          size="small"
          :color="resolveUserRoleVariant(item.role).color"
          variant="tonal"
          class="font-weight-bold"
        >
          <VIcon
            size="14"
            :icon="resolveUserRoleVariant(item.role).icon"
            class="me-1"
          />
          {{ Array.isArray(item.role) ? item.role.join(', ') : item.role }}
        </VChip>
      </template>

      <!-- Status Column -->
      <template #item.status="{ item }">
        <VChip
          :color="resolveUserStatusVariant(item.status)"
          size="small"
          variant="elevated"
          class="font-weight-bold"
        >
          {{ String(item.status).toLowerCase() === 'active' || String(item.status).toLowerCase() === 'aktif' ? 'Aktif' : 'Nonaktif' }}
        </VChip>
      </template>

      <!-- Actions Column -->
      <template #item.actions="{ item }">
        <div class="d-flex align-center gap-1">
          <IconBtn
            size="small"
            color="primary"
            variant="tonal"
            title="Lihat Detail Profil"
            :to="{ name: 'apps-user-view-id', params: { id: item.id } }"
          >
            <VIcon icon="ri-eye-line" size="18" />
          </IconBtn>

          <IconBtn
            size="small"
            color="error"
            variant="tonal"
            title="Hapus Pengguna"
            @click="deleteUser(item.id)"
          >
            <VIcon icon="ri-delete-bin-line" size="18" />
          </IconBtn>
        </div>
      </template>
    </VDataTableServer>
  </VCard>
</template>

<style scoped>
.hover-primary:hover {
  color: rgb(var(--v-theme-primary)) !important;
}
</style>
