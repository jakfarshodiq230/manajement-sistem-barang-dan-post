<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import AddNewUserDrawer from '@/views/apps/user/list/AddNewUserDrawer.vue'

definePage({
  meta: {
    public: true,
  },
})

// =================== STATE ===================
const searchQuery = ref('')
const itemsPerPage = ref(10)
const selectedRole = ref(null)
const selectedBranch = ref(null)
const page = ref(1)

// Drawer & Dialog
const isAddUserDrawerOpen = ref(false)
const isAssignmentDialogOpen = ref(false)
const isDeleteDialogOpen = ref(false)
const editingUser = ref(null)
const editingAssignments = ref([])
const deletingUser = ref(null)
const savingAssignment = ref(false)
const isConfirmPinDialogOpen = ref(false)
const pinVisibility = ref({})
const editingPin = ref('')
const confirmPin = ref('')
const savingPin = ref(false)

const authUserData = JSON.parse(localStorage.getItem('userData') || '{}')

// Data
const availableBranches = ref([])
const availableRoles = ref([])

// =================== FETCH ===================
const {
  data: usersData,
  execute: fetchUsers,
} = await useApi(createUrl('/apps/users', {
  query: { q: searchQuery, itemsPerPage, page, role: selectedRole, branch_id: selectedBranch },
}))

const users = computed(() => usersData.value?.users ?? [])
const totalUsers = computed(() => usersData.value?.totalUsers ?? 0)

const usersWithPinCount = computed(() => {
  return users.value.filter(u => u.pos_pin).length
})

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

// =================== HELPERS ===================
const roleColors = ['primary', 'success', 'warning', 'info', 'error', 'secondary']

const getRoleColor = name => {
  const n = String(name || '').toLowerCase()
  if (n.includes('super') || n.includes('owner')) return 'error'
  if (n.includes('pusat')) return 'primary'
  if (n.includes('cabang')) return 'info'
  if (n.includes('kasir')) return 'success'
  if (n.includes('gudang')) return 'warning'
  if (n.includes('manager')) return 'secondary'
  return 'primary'
}

const headers = [
  { title: 'Pengguna', key: 'user', sortable: false },
  { title: 'Email Akun', key: 'email' },
  { title: 'Jabatan & Cabang', key: 'assignments', sortable: false },
  { title: 'Status', key: 'status', sortable: false },
  { title: 'PIN Otorisasi Supervisor', key: 'pos_pin', sortable: false },
  { title: 'Aksi', key: 'actions', sortable: false },
]

// =================== PIN MANAGEMENT ===================
const openPinDialog = user => {
  editingUser.value = { ...user }
  isConfirmPinDialogOpen.value = true
}

const togglePinVisibility = (userId) => {
  pinVisibility.value[userId] = !pinVisibility.value[userId]
}

const generateAndSavePin = async () => {
  savingPin.value = true
  try {
    const randomPin = Math.floor(100000 + Math.random() * 900000).toString()
    await $api(`/apps/users/${editingUser.value.id}/update-pin`, {
      method: 'POST',
      body: { pin: randomPin },
    })
    useSnackbarStore().show(`PIN berhasil dibuat: ${randomPin}`, 'success')
    isConfirmPinDialogOpen.value = false
    fetchUsers()
  } catch (error) {
    useSnackbarStore().show(error.data?.message || 'Gagal membuat PIN', 'error')
  } finally {
    savingPin.value = false
  }
}

// =================== ADD USER ===================
const addNewUser = async userData => {
  await $api('/apps/users', { method: 'POST', body: userData })
  fetchUsers()
}

// =================== ASSIGNMENT ===================
const openAssignment = user => {
  editingUser.value = { ...user }
  editingAssignments.value = (user.assignments || []).map(a => ({
    branch_id: a.branch_id,
    role_name: a.role_name,
    is_primary: a.is_primary ?? false,
  }))
  if (editingAssignments.value.length === 0) {
    editingAssignments.value.push({ branch_id: null, role_name: null, is_primary: true })
  }
  isAssignmentDialogOpen.value = true
}

const addAssignmentRow = () => {
  editingAssignments.value.push({ branch_id: null, role_name: null, is_primary: false })
}

const removeAssignmentRow = (idx) => {
  editingAssignments.value.splice(idx, 1)
}

const saveAssignments = async () => {
  savingAssignment.value = true
  try {
    await $api(`/apps/users/${editingUser.value.id}/assignments`, {
      method: 'POST',
      body: { assignments: editingAssignments.value },
    })
    useSnackbarStore().show('Penugasan peran & cabang berhasil disimpan', 'success')
    isAssignmentDialogOpen.value = false
    fetchUsers()
  } catch (error) {
    useSnackbarStore().show(error.data?.message || 'Gagal menyimpan penugasan', 'error')
  } finally {
    savingAssignment.value = false
  }
}

// =================== DELETE USER ===================
const confirmDelete = user => {
  deletingUser.value = user
  isDeleteDialogOpen.value = true
}

const deleteUser = async () => {
  try {
    await $api(`/apps/users/${deletingUser.value.id}`, { method: 'DELETE' })
    useSnackbarStore().show('Pengguna berhasil dihapus', 'success')
    isDeleteDialogOpen.value = false
    fetchUsers()
  } catch (error) {
    useSnackbarStore().show(error.data?.message || 'Gagal menghapus pengguna', 'error')
  }
}
</script>

<template>
  <div class="pengaturan-pengguna-page">
    <!-- Header Banner -->
    <div class="d-flex justify-space-between align-center flex-wrap gap-4 mb-6">
      <div>
        <div class="d-flex align-center gap-2 mb-1">
          <VChip color="primary" variant="tonal" size="small" class="font-weight-bold">
            <VIcon icon="ri-user-settings-line" size="14" class="me-1" />
            PENGATURAN PENGGUNA & STAF
          </VChip>
        </div>
        <h1 class="text-h4 font-weight-extrabold text-high-emphasis mb-1">
          Pengaturan Pengguna & Jabatan Cabang
        </h1>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Kelola akun karyawan, penugasan peran per cabang toko, dan PIN otorisasi transaksi kasir POS.
        </p>
      </div>

      <div class="d-flex gap-3">
        <VBtn
          color="primary"
          class="font-weight-bold text-none"
          prepend-icon="ri-user-add-line"
          @click="isAddUserDrawerOpen = true"
        >
          Tambah Pengguna Baru
        </VBtn>
      </div>
    </div>

    <!-- KPI Summary Cards -->
    <VRow class="mb-6">
      <VCol cols="12" sm="4">
        <VCard class="pa-5 rounded-xl border elevation-1">
          <div class="d-flex align-center gap-4">
            <VAvatar color="primary" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-group-line" size="28" />
            </VAvatar>
            <div>
              <h4 class="text-h5 font-weight-bold mb-0">{{ totalUsers }}</h4>
              <span class="text-caption text-medium-emphasis">Total Akun Pengguna</span>
            </div>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="4">
        <VCard class="pa-5 rounded-xl border elevation-1">
          <div class="d-flex align-center gap-4">
            <VAvatar color="warning" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-key-2-line" size="28" />
            </VAvatar>
            <div>
              <h4 class="text-h5 font-weight-bold mb-0">{{ usersWithPinCount }}</h4>
              <span class="text-caption text-medium-emphasis">Memiliki PIN Otorisasi</span>
            </div>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="4">
        <VCard class="pa-5 rounded-xl border elevation-1">
          <div class="d-flex align-center gap-4">
            <VAvatar color="info" variant="tonal" size="52" rounded="lg">
              <VIcon icon="ri-store-2-line" size="28" />
            </VAvatar>
            <div>
              <h4 class="text-h5 font-weight-bold mb-0">{{ availableBranches.length }}</h4>
              <span class="text-caption text-medium-emphasis">Cabang Toko Terdaftar</span>
            </div>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Table Card -->
    <VCard class="rounded-xl border elevation-1">
      <!-- Toolbar -->
      <VCardText class="pa-5">
        <VRow>
          <VCol cols="12" sm="5" md="4">
            <VTextField
              v-model="searchQuery"
              placeholder="Cari nama atau email..."
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
              :items="availableRoles"
              density="compact"
              variant="outlined"
              rounded="lg"
              clearable
              hide-details
              prepend-inner-icon="ri-shield-user-line"
            />
          </VCol>

          <VCol cols="12" sm="4" md="4">
            <VSelect
              v-model="selectedBranch"
              placeholder="Filter Cabang"
              :items="availableBranches"
              item-title="name"
              item-value="id"
              density="compact"
              variant="outlined"
              rounded="lg"
              clearable
              hide-details
              prepend-inner-icon="ri-store-2-line"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <!-- Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items="users"
        :items-length="totalUsers"
        :headers="headers"
        item-value="id"
        class="text-no-wrap"
        @update:options="opts => { page = opts.page; itemsPerPage = opts.itemsPerPage }"
      >
        <!-- User -->
        <template #item.user="{ item }">
          <div class="d-flex align-center gap-3 py-2">
            <VAvatar
              size="40"
              :color="getRoleColor(item.fullName || item.name)"
              variant="tonal"
              class="elevation-1"
            >
              <span class="text-subtitle-2 font-weight-bold">
                {{ (item.fullName || item.name || 'U').charAt(0).toUpperCase() }}
              </span>
            </VAvatar>
            <div>
              <RouterLink
                :to="{ name: 'apps-user-view-id', params: { id: item.id } }"
                class="text-high-emphasis font-weight-bold d-block text-subtitle-2 hover-primary"
              >
                {{ item.fullName || item.name }}
              </RouterLink>
              <span class="text-caption text-medium-emphasis">@{{ item.username }}</span>
            </div>
          </div>
        </template>

        <!-- Email -->
        <template #item.email="{ item }">
          <span class="text-body-2 text-medium-emphasis">{{ item.email }}</span>
        </template>

        <!-- Assignments -->
        <template #item.assignments="{ item }">
          <div class="py-2">
            <div
              v-if="item.assignments && item.assignments.length"
              class="d-flex flex-column gap-1"
            >
              <div
                v-for="(a, idx) in item.assignments"
                :key="idx"
                class="d-flex align-center gap-2"
              >
                <VChip
                  size="x-small"
                  :color="getRoleColor(a.role_name)"
                  variant="tonal"
                  class="font-weight-bold"
                >
                  <VIcon start size="10" icon="ri-shield-user-line" />
                  {{ a.role_name }}
                </VChip>
                <span class="text-caption text-medium-emphasis d-flex align-center gap-1">
                  <VIcon size="11" icon="ri-store-2-line" />
                  {{ a.branch_name }}
                </span>
              </div>
            </div>
            <VChip
              v-else
              size="x-small"
              color="warning"
              variant="tonal"
              class="font-weight-medium"
            >
              <VIcon start size="10" icon="ri-alert-line" />
              Belum ada jabatan
            </VChip>
          </div>
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <VChip
            size="small"
            color="success"
            variant="elevated"
            class="font-weight-bold"
          >
            Aktif
          </VChip>
        </template>

        <!-- PIN Column -->
        <template #item.pos_pin="{ item }">
          <div class="d-flex align-center gap-2">
            <template v-if="item.pos_pin">
              <span class="font-weight-bold text-subtitle-2 letter-spacing-2">
                {{ pinVisibility[item.id] ? item.pos_pin : '••••••' }}
              </span>
              <IconBtn size="small" variant="text" @click="togglePinVisibility(item.id)">
                <VIcon :icon="pinVisibility[item.id] ? 'ri-eye-off-line' : 'ri-eye-line'" size="16" />
              </IconBtn>
              <IconBtn size="small" color="primary" variant="tonal" @click="openPinDialog(item)">
                <VTooltip activator="parent" location="top">Generate Ulang PIN</VTooltip>
                <VIcon icon="ri-refresh-line" size="16" />
              </IconBtn>
            </template>
            <template v-else>
              <VBtn size="small" variant="tonal" color="warning" class="font-weight-bold text-none" @click="openPinDialog(item)">
                + Buat PIN
              </VBtn>
            </template>
          </div>
        </template>
        
        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1 align-center">
            <IconBtn
              size="small"
              color="primary"
              variant="tonal"
              title="Kelola Jabatan & Cabang"
              @click="openAssignment(item)"
            >
              <VIcon icon="ri-shield-user-line" size="18" />
            </IconBtn>

            <IconBtn
              size="small"
              color="error"
              variant="tonal"
              title="Hapus Pengguna"
              @click="confirmDelete(item)"
            >
              <VIcon icon="ri-delete-bin-line" size="18" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Dialogs -->
    <AddNewUserDrawer
      v-model:is-drawer-open="isAddUserDrawerOpen"
      @user-data="addNewUser"
    />

    <!-- Assignment Dialog -->
    <VDialog v-model="isAssignmentDialogOpen" max-width="600">
      <VCard class="rounded-2xl pa-5">
        <VCardItem class="pa-0 mb-4">
          <h3 class="text-h6 font-weight-bold">
            Penugasan Jabatan: {{ editingUser?.fullName }}
          </h3>
          <p class="text-caption text-medium-emphasis mb-0">
            Tentukan cabang dan peran yang dapat diakses oleh pengguna ini.
          </p>
        </VCardItem>

        <VCardText class="pa-0 mb-4">
          <div
            v-for="(row, idx) in editingAssignments"
            :key="idx"
            class="d-flex gap-2 align-center mb-3 pa-3 rounded-lg border bg-var-theme-background"
          >
            <VSelect
              v-model="row.branch_id"
              :items="availableBranches"
              item-title="name"
              item-value="id"
              label="Cabang Toko"
              density="compact"
              variant="outlined"
              hide-details
            />
            <VSelect
              v-model="row.role_name"
              :items="availableRoles"
              label="Peran / Role"
              density="compact"
              variant="outlined"
              hide-details
            />
            <IconBtn
              color="error"
              size="small"
              @click="removeAssignmentRow(idx)"
            >
              <VIcon icon="ri-delete-bin-line" />
            </IconBtn>
          </div>

          <VBtn
            variant="tonal"
            color="primary"
            size="small"
            class="font-weight-bold"
            prepend-icon="ri-add-line"
            @click="addAssignmentRow"
          >
            Tambah Penugasan Cabang
          </VBtn>
        </VCardText>

        <VCardActions class="pa-0 d-flex justify-end gap-2">
          <VBtn variant="tonal" color="secondary" @click="isAssignmentDialogOpen = false">
            Batal
          </VBtn>
          <VBtn color="primary" class="font-weight-bold" :loading="savingAssignment" @click="saveAssignments">
            Simpan Penugasan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Confirm PIN Dialog -->
    <VDialog v-model="isConfirmPinDialogOpen" max-width="440">
      <VCard class="rounded-2xl pa-6 text-center">
        <VAvatar color="warning" variant="tonal" size="64" class="mx-auto mb-4">
          <VIcon icon="ri-lock-password-line" size="36" />
        </VAvatar>
        <h3 class="text-h6 font-weight-bold mb-2">
          Generate PIN Otorisasi Baru?
        </h3>
        <p class="text-body-2 text-medium-emphasis mb-5">
          Sistem akan membuat 6-digit PIN acak baru untuk pengguna <strong>{{ editingUser?.fullName }}</strong>.
        </p>
        <div class="d-flex justify-center gap-2">
          <VBtn variant="tonal" color="secondary" @click="isConfirmPinDialogOpen = false">
            Batal
          </VBtn>
          <VBtn color="primary" class="font-weight-bold" :loading="savingPin" @click="generateAndSavePin">
            Buat PIN Sekarang
          </VBtn>
        </div>
      </VCard>
    </VDialog>

    <!-- Delete Dialog -->
    <VDialog v-model="isDeleteDialogOpen" max-width="440">
      <VCard class="rounded-2xl pa-6 text-center">
        <VAvatar color="error" variant="tonal" size="64" class="mx-auto mb-4">
          <VIcon icon="ri-delete-bin-line" size="36" />
        </VAvatar>
        <h3 class="text-h6 font-weight-bold mb-2">
          Hapus Pengguna?
        </h3>
        <p class="text-body-2 text-medium-emphasis mb-5">
          Apakah Anda yakin ingin menghapus akun <strong>{{ deletingUser?.fullName }}</strong>? Tindakan ini tidak dapat dibatalkan.
        </p>
        <div class="d-flex justify-center gap-2">
          <VBtn variant="tonal" color="secondary" @click="isDeleteDialogOpen = false">
            Batal
          </VBtn>
          <VBtn color="error" class="font-weight-bold" @click="deleteUser">
            Ya, Hapus
          </VBtn>
        </div>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.hover-primary:hover {
  color: rgb(var(--v-theme-primary)) !important;
}

.letter-spacing-2 {
  letter-spacing: 2px;
}
</style>
