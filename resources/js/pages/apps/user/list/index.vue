<script setup>
import { ref, computed, onMounted } from 'vue'
import AddNewUserDrawer from '@/views/apps/user/list/AddNewUserDrawer.vue'
import { paginationMeta } from '@/utils/paginationMeta'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    public: true,
  },
})

const snackbar = useSnackbarStore()

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
const isSavingAssignments = ref(false)
const editingUser = ref(null)
const selectedBranchIds = ref([])
const branchRoleMap = ref({})
const defaultRoleForSelected = ref('')
const branchSearchFilter = ref('')

// Dynamic branches + roles
const availableBranches = ref([])
const availableRoles = ref([])

const loadBranchesAndRoles = async () => {
  try {
    const [branchData, roleData] = await Promise.all([
      $api('/apps/branches?all=true'),
      $api('/apps/roles'),
    ])

    availableBranches.value = branchData.data || branchData || []
    availableRoles.value = (roleData.data || roleData || []).map(r => r.role || r.name)
  } catch(e) {
    console.error('Failed to load branches/roles:', e)
  }
}

onMounted(async () => {
  await loadBranchesAndRoles()
  fetchUsers()
})

const openAssignmentDialog = async user => {
  editingUser.value = { ...user }
  
  if (availableBranches.value.length === 0) {
    await loadBranchesAndRoles()
  }
  
  const userAssignments = user.assignments || []
  selectedBranchIds.value = userAssignments.map(a => a.branch_id).filter(Boolean)
  
  const map = {}
  userAssignments.forEach(a => {
    if (a.branch_id) {
      map[a.branch_id] = a.role_name || a.role || user.role || availableRoles.value[0] || ''
    }
  })
  
  defaultRoleForSelected.value = user.role || userAssignments[0]?.role_name || availableRoles.value[0] || ''
  
  availableBranches.value.forEach(b => {
    if (!map[b.id]) {
      map[b.id] = defaultRoleForSelected.value
    }
  })
  
  branchRoleMap.value = map
  branchSearchFilter.value = ''
  isAssignmentDialogVisible.value = true
}

const isAllBranchesSelected = computed({
  get() {
    if (!availableBranches.value.length) return false
    return availableBranches.value.every(b => selectedBranchIds.value.includes(b.id))
  },
  set(val) {
    if (val) {
      selectedBranchIds.value = availableBranches.value.map(b => b.id)
    } else {
      selectedBranchIds.value = []
    }
  },
})

const modalBranchesList = computed(() => {
  if (!branchSearchFilter.value) return availableBranches.value
  const q = branchSearchFilter.value.toLowerCase()
  return availableBranches.value.filter(b => 
    b.name.toLowerCase().includes(q) || 
    (b.address && b.address.toLowerCase().includes(q))
  )
})

const applyDefaultRoleToSelected = () => {
  if (!defaultRoleForSelected.value) return
  
  if (selectedBranchIds.value.length === 0) {
    selectedBranchIds.value = availableBranches.value.map(b => b.id)
  }

  const newMap = { ...branchRoleMap.value }
  selectedBranchIds.value.forEach(bId => {
    newMap[bId] = defaultRoleForSelected.value
  })
  branchRoleMap.value = newMap
  
  snackbar.show(`Peran "${defaultRoleForSelected.value}" berhasil diterapkan ke ${selectedBranchIds.value.length} cabang terpilih`, 'success')
}

const toggleBranchSelection = branchId => {
  const idx = selectedBranchIds.value.indexOf(branchId)
  if (idx > -1) {
    selectedBranchIds.value.splice(idx, 1)
  } else {
    selectedBranchIds.value.push(branchId)
    if (!branchRoleMap.value[branchId]) {
      const newMap = { ...branchRoleMap.value }
      newMap[branchId] = defaultRoleForSelected.value || availableRoles.value[0] || ''
      branchRoleMap.value = newMap
    }
  }
}

const saveAssignments = async () => {
  isSavingAssignments.value = true
  try {
    const assignments = selectedBranchIds.value.map(branchId => ({
      branch_id: branchId,
      role: branchRoleMap.value[branchId] || defaultRoleForSelected.value || 'Kasir',
    }))

    await $api(`/apps/users/${editingUser.value.id}`, {
      method: 'PUT',
      body: {
        fullName: editingUser.value.fullName,
        email: editingUser.value.email,
        assignments,
      },
    })
    snackbar.show('Penugasan cabang berhasil disimpan', 'success')
    isAssignmentDialogVisible.value = false
    fetchUsers()
  } catch (err) {
    console.error('Failed to save assignments:', err)
    snackbar.show('Gagal menyimpan penugasan cabang: ' + (err?.data?.message || err?.message || 'Error'), 'error')
  } finally {
    isSavingAssignments.value = false
  }
}

const updateOptions = options => {
  page.value = options.page
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  fetchUsers()
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

const users = ref([])
const totalUsers = ref(0)
const stats = ref({})
const isLoading = ref(false)

const fetchUsers = async () => {
  isLoading.value = true
  try {
    const params = {
      q: searchQuery.value || undefined,
      status: selectedStatus.value || undefined,
      role: selectedRole.value || undefined,
      branch_id: selectedBranch.value || undefined,
      itemsPerPage: itemsPerPage.value,
      page: page.value,
      sortBy: sortBy.value || undefined,
      orderBy: orderBy.value || undefined,
    }

    const res = await $api('/apps/users', { params })
    users.value = res.users || res.data || []
    totalUsers.value = res.stats?.totalUsers ?? res.totalUsers ?? users.value.length
    stats.value = res.stats || {}
  } catch (error) {
    console.error('Failed to fetch users:', error)
  } finally {
    isLoading.value = false
  }
}

watch([searchQuery, selectedStatus, selectedRole, selectedBranch], () => {
  page.value = 1
  fetchUsers()
})

const activeUsersCount = computed(() => stats.value.activeUsers ?? users.value.filter(u => u.status === 'Active' || u.status == 1).length)
const totalBranchesCount = computed(() => stats.value.totalBranches ?? availableBranches.value.length)
const totalRolesCount = computed(() => stats.value.totalRoles ?? availableRoles.value.length)

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

    <!-- 👉 Assignment Dialog (Multi-Branch Checklist) -->
    <VDialog
      v-model="isAssignmentDialogVisible"
      max-width="680"
      persistent
    >
      <VCard v-if="editingUser" class="rounded-2xl pa-6">
        <!-- Header -->
        <VCardItem class="pa-0 mb-4">
          <div class="d-flex justify-space-between align-center flex-wrap gap-3">
            <div class="d-flex align-center gap-3">
              <VAvatar color="primary" variant="tonal" size="48" rounded="xl">
                <VIcon icon="ri-shield-user-line" size="26" />
              </VAvatar>
              <div>
                <h3 class="text-h6 font-weight-bold mb-0">
                  Kelola Penugasan Cabang
                </h3>
                <div class="d-flex align-center gap-2 mt-1">
                  <span class="text-body-2 font-weight-medium text-high-emphasis">{{ editingUser.fullName }}</span>
                  <span class="text-caption text-disabled">({{ editingUser.email }})</span>
                </div>
              </div>
            </div>
            <VChip
              :color="selectedBranchIds.length > 0 ? 'primary' : 'secondary'"
              variant="tonal"
              size="small"
              class="font-weight-bold"
            >
              <VIcon icon="ri-store-2-line" size="14" class="me-1" />
              {{ selectedBranchIds.length }} / {{ availableBranches.length }} Cabang Ditugaskan
            </VChip>
          </div>
        </VCardItem>

        <VDivider class="mb-4" />

        <VCardText class="pa-0 mb-4">
          <!-- Quick Role Bar -->
          <div class="bg-grey-50 rounded-xl pa-3 mb-4 border">
            <div class="d-flex align-center justify-space-between flex-wrap gap-3">
              <div class="flex-grow-1" style="min-width: 220px;">
                <VSelect
                  v-model="defaultRoleForSelected"
                  :items="availableRoles"
                  label="Peran / Jabatan Utama"
                  placeholder="Pilih Peran"
                  density="compact"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                  prepend-inner-icon="ri-shield-check-line"
                />
              </div>
              <VBtn
                variant="tonal"
                color="primary"
                size="small"
                prepend-icon="ri-sparkling-line"
                class="font-weight-bold"
                :disabled="!selectedBranchIds.length || !defaultRoleForSelected"
                @click="applyDefaultRoleToSelected"
              >
                Terapkan Peran ke Semua Cabang Terpilih
              </VBtn>
            </div>
          </div>

          <!-- Branch Checklist Header & Search -->
          <div class="d-flex align-center justify-space-between flex-wrap gap-3 mb-3">
            <div class="d-flex align-center">
              <VCheckbox
                v-model="isAllBranchesSelected"
                color="primary"
                density="compact"
                hide-details
                class="font-weight-bold"
              >
                <template #label>
                  <span class="font-weight-bold text-body-1 text-high-emphasis">
                    Pilih Semua Cabang (Select All)
                  </span>
                </template>
              </VCheckbox>
            </div>

            <VTextField
              v-model="branchSearchFilter"
              placeholder="Cari Cabang / Toko..."
              density="compact"
              variant="outlined"
              rounded="lg"
              hide-details
              clearable
              prepend-inner-icon="ri-search-line"
              style="max-width: 240px;"
            />
          </div>

          <!-- Scrollable Branch List -->
          <div
            class="d-flex flex-column gap-2 overflow-y-auto pe-1"
            style="max-height: 320px;"
          >
            <div
              v-for="branch in modalBranchesList"
              :key="branch.id"
              class="d-flex align-center justify-space-between p-3 rounded-xl border transition-all"
              :class="selectedBranchIds.includes(branch.id) ? 'bg-primary-lighten-5 border-primary' : 'bg-surface'"
              style="cursor: pointer; padding: 10px 14px;"
              @click="toggleBranchSelection(branch.id)"
            >
              <!-- Checkbox & Branch Info -->
              <div class="d-flex align-center gap-3">
                <VCheckbox
                  :model-value="selectedBranchIds.includes(branch.id)"
                  color="primary"
                  density="compact"
                  hide-details
                  @click.stop="toggleBranchSelection(branch.id)"
                />
                <VAvatar
                  :color="selectedBranchIds.includes(branch.id) ? 'primary' : 'secondary'"
                  variant="tonal"
                  size="36"
                  rounded="lg"
                >
                  <VIcon icon="ri-store-2-line" size="20" />
                </VAvatar>
                <div>
                  <div class="d-flex align-center gap-2">
                    <span class="font-weight-bold text-body-1 text-high-emphasis">
                      {{ branch.name }}
                    </span>
                    <VChip
                      v-if="branch.type"
                      size="x-small"
                      variant="outlined"
                      color="secondary"
                    >
                      {{ branch.type }}
                    </VChip>
                  </div>
                  <span class="text-caption text-medium-emphasis">
                    {{ branch.city || branch.province || 'Cabang Resmi' }}
                  </span>
                </div>
              </div>

              <!-- Role Selector for Checked Branch -->
              <div
                v-if="selectedBranchIds.includes(branch.id)"
                style="min-width: 170px;"
                @click.stop
              >
                <VSelect
                  v-model="branchRoleMap[branch.id]"
                  :items="availableRoles"
                  density="compact"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                  placeholder="Pilih Peran"
                />
              </div>
              <div v-else>
                <VChip
                  size="small"
                  variant="tonal"
                  color="secondary"
                  class="text-caption"
                >
                  Tidak Aktif
                </VChip>
              </div>
            </div>

            <!-- Empty State for Search -->
            <div
              v-if="modalBranchesList.length === 0"
              class="text-center py-6 text-disabled"
            >
              <VIcon icon="ri-store-line" size="32" class="mb-2" />
              <p class="mb-0 text-caption">Tidak ada cabang yang cocok dengan pencarian.</p>
            </div>
          </div>
        </VCardText>

        <VDivider class="mb-4" />

        <!-- Actions -->
        <VCardActions class="pa-0 d-flex justify-end gap-3">
          <VBtn
            variant="tonal"
            color="secondary"
            :disabled="isSavingAssignments"
            @click="isAssignmentDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            class="font-weight-bold px-6"
            :loading="isSavingAssignments"
            prepend-icon="ri-check-line"
            @click="saveAssignments"
          >
            Simpan Penugasan ({{ selectedBranchIds.length }} Cabang)
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
