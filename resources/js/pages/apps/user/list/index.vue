<script setup>
import { ref, computed, onMounted } from 'vue'
import AddNewUserDrawer from '@/views/apps/user/list/AddNewUserDrawer.vue'
import { paginationMeta } from '@/utils/paginationMeta'

definePage({
  meta: {
    public: true,
  },
})

const searchQuery = ref('')
const selectedRole = ref()
const selectedBranch = ref()
const selectedStatus = ref()

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const selectedRows = ref([])

// Assignment dialog state
const isAssignmentDialogVisible = ref(false)
const editingUser = ref(null)
const editingAssignments = ref([])

// Dynamic branches + roles
const availableBranches = ref([])
const availableRoles = ref([])

onMounted(async () => {
  try {
    const [branchData, roleData] = await Promise.all([
      $api('/apps/branches'),
      $api('/apps/roles'),
    ])

    availableBranches.value = branchData.data || branchData
    availableRoles.value = (roleData.data || roleData).map(r => r.role || r.name)
  } catch(e) { console.error(e) }
})

const openAssignmentDialog = user => {
  editingUser.value = { ...user }
  editingAssignments.value = (user.assignments || []).map(a => ({ ...a }))
  isAssignmentDialogVisible.value = true
}

const addAssignmentRow = () => {
  editingAssignments.value.push({ branch_id: null, role_name: '' })
}

const removeAssignmentRow = index => {
  editingAssignments.value.splice(index, 1)
}

const saveAssignments = async () => {
  await $api(`/apps/users/${editingUser.value.id}`, {
    method: 'PUT',
    body: {
      fullName: editingUser.value.fullName,
      email: editingUser.value.email,
      assignments: editingAssignments.value.map(a => ({
        branch_id: a.branch_id,
        role: a.role_name,
      })),
    },
  })
  isAssignmentDialogVisible.value = false
  fetchUsers()
}

const updateOptions = options => {
  page.value = options.page
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// Headers
const headers = [
  { title: 'Pengguna', key: 'user' },
  { title: 'Email Akun', key: 'email' },
  { title: 'Peran (Role)', key: 'role' },
  { title: 'Cabang / Toko', key: 'assignments', sortable: false },
  { title: 'Status', key: 'status' },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'end' },
]

const {
  data: usersData,
  execute: fetchUsers,
} = await useApi(createUrl('/apps/users', {
  query: {
    q: searchQuery,
    status: selectedStatus,
    role: selectedRole,
    branch_id: selectedBranch,
    itemsPerPage,
    page,
    sortBy,
    orderBy,
  },
}))

const users = computed(() => usersData.value?.users || [])
const totalUsers = computed(() => usersData.value?.stats?.totalUsers ?? usersData.value?.totalUsers ?? 0)
const activeUsersCount = computed(() => usersData.value?.stats?.activeUsers ?? users.value.filter(u => u.status === 'Active').length)
const totalBranchesCount = computed(() => usersData.value?.stats?.totalBranches ?? availableBranches.value.length)
const totalRolesCount = computed(() => usersData.value?.stats?.totalRoles ?? availableRoles.value.length)

// Search filters
const roles = computed(() => availableRoles.value.map(r => ({ title: r, value: r })))

const status = [
  { title: 'Semua Status', value: null },
  { title: 'Aktif (Active)', value: 'Active' },
  { title: 'Nonaktif (Inactive)', value: 'Inactive' },
  { title: 'Pending', value: 'Pending' },
]

const roleColors = ['primary', 'success', 'warning', 'info', 'error', 'secondary']

const resolveUserRoleVariant = role => {
  const n = String(role || '').toLowerCase()
  if (n.includes('super') || n.includes('owner')) return { color: 'error', icon: 'ri-shield-flash-line' }
  if (n.includes('pusat')) return { color: 'primary', icon: 'ri-admin-line' }
  if (n.includes('cabang')) return { color: 'info', icon: 'ri-store-2-line' }
  if (n.includes('kasir')) return { color: 'success', icon: 'ri-bank-card-line' }
  if (n.includes('gudang')) return { color: 'warning', icon: 'ri-archive-line' }
  if (n.includes('manager')) return { color: 'secondary', icon: 'ri-user-star-line' }
  return { color: 'primary', icon: 'ri-shield-user-line' }
}

const resolveUserStatusVariant = stat => {
  const s = String(stat || '').toLowerCase()
  if (s === 'active' || s === 'aktif') return 'success'
  if (s === 'inactive' || s === 'nonaktif') return 'secondary'
  if (s === 'pending') return 'warning'
  return 'primary'
}

const isAddNewUserDrawerVisible = ref(false)

const addNewUser = async userData => {
  await $api('/apps/users', {
    method: 'POST',
    body: userData,
  })
  fetchUsers()
}

const deleteUser = async id => {
  await $api(`/apps/users/${ id }`, { method: 'DELETE' })
  const index = selectedRows.value.findIndex(row => row === id)
  if (index !== -1)
    selectedRows.value.splice(index, 1)
  fetchUsers()
}

const widgetData = computed(() => [
  {
    title: 'Total Pengguna',
    value: totalUsers.value,
    desc: 'Akun Terdaftar di Sistem',
    icon: 'ri-group-line',
    iconColor: 'primary',
  },
  {
    title: 'Pengguna Aktif',
    value: activeUsersCount.value,
    desc: 'Status Akun Aktif',
    icon: 'ri-user-follow-line',
    iconColor: 'success',
  },
  {
    title: 'Total Cabang',
    value: totalBranchesCount.value,
    desc: 'Toko & Cabang Terhubung',
    icon: 'ri-store-2-line',
    iconColor: 'info',
  },
  {
    title: 'Peran & Hak Akses',
    value: totalRolesCount.value,
    desc: 'Struktur Peran RBAC',
    icon: 'ri-shield-user-line',
    iconColor: 'warning',
  },
])
</script>

<template>
  <section class="user-list-page">
    <!-- Header Banner -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <div class="d-flex align-center gap-2 mb-1">
          <VChip color="primary" variant="tonal" size="small" class="font-weight-bold">
            <VIcon icon="ri-user-line" size="14" class="me-1" />
            MANAJEMEN AKUN
          </VChip>
        </div>
        <h1 class="text-h4 font-weight-extrabold text-high-emphasis mb-1">
          Daftar Akun Pengguna
        </h1>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Kelola seluruh akun pengguna, peran, status aktivitas, dan penugasan cabang toko.
        </p>
      </div>

      <div class="d-flex gap-3">
        <VBtn
          color="primary"
          class="font-weight-bold text-none"
          prepend-icon="ri-user-add-line"
          @click="isAddNewUserDrawerVisible = true"
        >
          Tambah Pengguna Baru
        </VBtn>
      </div>
    </div>

    <!-- 👉 Real KPI Widgets -->
    <div class="d-flex mb-6">
      <VRow>
        <template
          v-for="(data, id) in widgetData"
          :key="id"
        >
          <VCol
            cols="12"
            md="3"
            sm="6"
          >
            <VCard class="rounded-xl border elevation-1">
              <VCardText>
                <div class="d-flex justify-space-between align-center">
                  <div class="d-flex flex-column gap-y-1">
                    <span class="text-body-2 font-weight-medium text-medium-emphasis">{{ data.title }}</span>
                    <h4 class="text-h4 font-weight-bold text-high-emphasis mb-0">
                      {{ data.value }}
                    </h4>
                    <p class="text-caption text-medium-emphasis mb-0">
                      {{ data.desc }}
                    </p>
                  </div>
                  <VAvatar
                    :color="data.iconColor"
                    variant="tonal"
                    rounded="lg"
                    size="48"
                  >
                    <VIcon
                      :icon="data.icon"
                      size="26"
                    />
                  </VAvatar>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </template>
      </VRow>
    </div>

    <!-- Filter Card -->
    <VCard class="mb-6 rounded-xl border elevation-1">
      <VCardItem class="pb-2">
        <VCardTitle class="text-subtitle-1 font-weight-bold d-flex align-center gap-2">
          <VIcon icon="ri-filter-3-line" size="20" color="primary" />
          Filter Pengguna & Penugasan
        </VCardTitle>
      </VCardItem>
      
      <VCardText>
        <VRow>
          <!-- 👉 Filter Role -->
          <VCol
            cols="12"
            sm="4"
          >
            <VSelect
              v-model="selectedRole"
              label="Filter Peran (Role)"
              placeholder="Pilih Peran"
              :items="roles"
              clearable
              rounded="lg"
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-shield-user-line"
              hide-details
            />
          </VCol>
          
          <!-- 👉 Filter Branch -->
          <VCol
            cols="12"
            sm="4"
          >
            <VSelect
              v-model="selectedBranch"
              label="Filter Cabang / Toko"
              placeholder="Semua Cabang"
              :items="availableBranches"
              item-title="name"
              item-value="id"
              clearable
              rounded="lg"
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-store-2-line"
              hide-details
            />
          </VCol>
          
          <!-- 👉 Filter Status -->
          <VCol
            cols="12"
            sm="4"
          >
            <VSelect
              v-model="selectedStatus"
              label="Filter Status Akun"
              placeholder="Pilih Status"
              :items="status"
              clearable
              rounded="lg"
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-checkbox-circle-line"
              hide-details
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VCardText class="d-flex flex-wrap gap-4 align-center py-4">
        <div class="d-flex align-center gap-4 flex-wrap w-100 justify-space-between">
          <!-- 👉 Search  -->
          <div class="app-user-search-filter" style="max-width: 320px; width: 100%;">
            <VTextField
              v-model="searchQuery"
              placeholder="Cari nama, email, no. telp..."
              density="compact"
              variant="outlined"
              rounded="lg"
              prepend-inner-icon="ri-search-line"
              hide-details
              clearable
            />
          </div>
          
          <div class="text-caption text-medium-emphasis">
            Menampilkan {{ users.length }} dari {{ totalUsers }} pengguna
          </div>
        </div>
      </VCardText>

      <!-- SECTION datatable -->
      <VDataTableServer
        v-model:model-value="selectedRows"
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items="users"
        item-value="id"
        :items-length="totalUsers"
        :headers="headers"
        show-select
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <!-- User -->
        <template #item.user="{ item }">
          <div class="d-flex align-center py-2">
            <VAvatar
              size="38"
              :variant="!item.avatar ? 'tonal' : undefined"
              :color="!item.avatar ? resolveUserRoleVariant(Array.isArray(item.role) ? item.role[0] : item.role).color : undefined"
              rounded="lg"
              class="me-3"
            >
              <VImg
                v-if="item.avatar"
                :src="item.avatar"
              />
              <span v-else class="font-weight-bold">{{ avatarText(item.fullName) }}</span>
            </VAvatar>

            <div class="d-flex flex-column">
              <span class="text-subtitle-2 font-weight-bold text-high-emphasis">
                {{ item.fullName }}
              </span>
              <span class="text-caption text-medium-emphasis">ID: #{{ item.id }}</span>
            </div>
          </div>
        </template>
        
        <!-- Email -->
        <template #item.email="{ item }">
          <span class="text-body-2 text-medium-emphasis">{{ item.email }}</span>
        </template>

        <!-- Role -->
        <template #item.role="{ item }">
          <div class="d-flex flex-wrap gap-1">
            <template v-if="item.role && item.role.length > 0">
              <VChip
                v-for="(roleName, idx) in item.role"
                :key="idx"
                size="small"
                variant="tonal"
                :color="resolveUserRoleVariant(roleName).color"
                class="font-weight-medium"
              >
                <VIcon
                  start
                  size="13"
                  :icon="resolveUserRoleVariant(roleName).icon"
                />
                {{ roleName }}
              </VChip>
            </template>
            <VChip
              v-else
              size="small"
              color="secondary"
              variant="tonal"
            >
              Belum Diatur
            </VChip>
          </div>
        </template>
        
        <!-- Assignments / Branch -->
        <template #item.assignments="{ item }">
          <div class="d-flex flex-wrap gap-1">
            <template v-if="item.assignments && item.assignments.length > 0">
              <VChip
                v-for="(a, idx) in item.assignments"
                :key="idx"
                size="small"
                variant="tonal"
                color="info"
                class="font-weight-medium"
              >
                <VIcon start size="13" icon="ri-store-2-line" />
                {{ a.branch_name }}
              </VChip>
            </template>
            <span v-else class="text-caption text-medium-emphasis">Semua Cabang (Global)</span>
          </div>
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <VChip
            :color="resolveUserStatusVariant(item.status)"
            size="small"
            variant="elevated"
            class="font-weight-bold"
          >
            {{ item.status }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-end gap-1">
            <IconBtn
              size="small"
              color="primary"
              variant="tonal"
              title="Kelola Penugasan Cabang"
              @click="openAssignmentDialog(item)"
            >
              <VIcon icon="ri-shield-user-line" size="18" />
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

        <!-- Pagination -->
        <template #bottom>
          <VDivider />

          <div class="d-flex justify-end flex-wrap gap-x-6 px-4 py-2">
            <div class="d-flex align-center gap-x-2 text-medium-emphasis text-body-2">
              Baris per halaman:
              <VSelect
                v-model="itemsPerPage"
                class="per-page-select"
                variant="plain"
                density="compact"
                :items="[10, 20, 25, 50, 100]"
                hide-details
              />
            </div>

            <p class="d-flex align-center text-body-2 text-high-emphasis me-2 mb-0">
              {{ paginationMeta({ page, itemsPerPage }, totalUsers) }}
            </p>

            <div class="d-flex gap-x-2 align-center me-2">
              <VBtn
                icon="ri-arrow-left-s-line"
                variant="text"
                density="comfortable"
                color="high-emphasis"
                :disabled="page <= 1"
                @click="page <= 1 ? page = 1 : page--"
              />

              <VBtn
                icon="ri-arrow-right-s-line"
                density="comfortable"
                variant="text"
                color="high-emphasis"
                :disabled="page >= Math.ceil(totalUsers / itemsPerPage)"
                @click="page >= Math.ceil(totalUsers / itemsPerPage) ? page = Math.ceil(totalUsers / itemsPerPage) : page++ "
              />
            </div>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- 👉 Add New User Drawer -->
    <AddNewUserDrawer
      v-model:is-drawer-open="isAddNewUserDrawerVisible"
      @user-data="addNewUser"
    />

    <!-- 👉 Assignment Dialog -->
    <VDialog
      v-model="isAssignmentDialogVisible"
      max-width="600"
    >
      <VCard v-if="editingUser" class="rounded-2xl pa-6">
        <VCardItem class="pa-0 mb-4">
          <div class="d-flex align-center gap-3">
            <VAvatar color="primary" variant="tonal" size="48" rounded="xl">
              <VIcon icon="ri-shield-user-line" size="26" />
            </VAvatar>
            <div>
              <h3 class="text-h6 font-weight-bold mb-0">
                Kelola Penugasan: {{ editingUser.fullName }}
              </h3>
              <p class="text-caption text-medium-emphasis mb-0">
                Atur penugasan cabang dan peran untuk pengguna ini.
              </p>
            </div>
          </div>
        </VCardItem>

        <VDivider class="mb-4" />

        <VCardText class="pa-0 mb-4">
          <div
            v-for="(assignment, idx) in editingAssignments"
            :key="idx"
            class="d-flex gap-3 mb-3 align-center"
          >
            <!-- Branch -->
            <VSelect
              v-model="assignment.branch_id"
              :items="availableBranches"
              item-title="name"
              item-value="id"
              label="Cabang / Toko"
              density="compact"
              variant="outlined"
              rounded="lg"
              hide-details
              style="min-width: 180px"
            />
            <!-- Role -->
            <VSelect
              v-model="assignment.role_name"
              :items="availableRoles"
              label="Jabatan / Peran"
              density="compact"
              variant="outlined"
              rounded="lg"
              hide-details
              style="min-width: 160px"
            />
            <!-- Delete row -->
            <IconBtn
              color="error"
              variant="tonal"
              @click="removeAssignmentRow(idx)"
            >
              <VIcon
                icon="ri-delete-bin-line"
                size="18"
              />
            </IconBtn>
          </div>

          <VBtn
            variant="tonal"
            color="primary"
            size="small"
            prepend-icon="ri-add-line"
            class="mt-2 font-weight-bold"
            @click="addAssignmentRow"
          >
            Tambah Penugasan Cabang
          </VBtn>
        </VCardText>

        <VCardActions class="pa-0 d-flex justify-end gap-2">
          <VBtn
            variant="tonal"
            color="secondary"
            @click="isAssignmentDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            class="font-weight-bold"
            @click="saveAssignments"
          >
            Simpan Penugasan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss" scoped>
.app-user-search-filter {
  inline-size: 15.625rem;
}
</style>
