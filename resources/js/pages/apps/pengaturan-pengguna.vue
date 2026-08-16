<script setup>
import AddNewUserDrawer from '@/views/apps/user/list/AddNewUserDrawer.vue'

definePage({
  meta: { action: 'read', subject: 'Pengguna' },
})

// =================== STATE ===================
const searchQuery = ref('')
const itemsPerPage = ref(10)
const page = ref(1)

// Drawer & Dialog
const isAddUserDrawerOpen = ref(false)
const isAssignmentDialogOpen = ref(false)
const isDeleteDialogOpen = ref(false)
const editingUser = ref(null)
const editingAssignments = ref([])
const deletingUser = ref(null)
const savingAssignment = ref(false)

// Data
const availableBranches = ref([])
const availableRoles = ref([])

// =================== FETCH ===================
const {
  data: usersData,
  execute: fetchUsers,
} = await useApi(createUrl('/apps/users', {
  query: { q: searchQuery, itemsPerPage, page },
}))

const users = computed(() => usersData.value?.users ?? [])
const totalUsers = computed(() => usersData.value?.totalUsers ?? 0)

onMounted(async () => {
  try {
    const [branchData, roleData] = await Promise.all([
      $api('/apps/branches'),
      $api('/apps/roles'),
    ])

    availableBranches.value = branchData
    availableRoles.value = roleData.map(r => r.role)
  } catch(e) { console.error(e) }
})

// =================== HELPERS ===================
const roleColors = ['primary', 'success', 'warning', 'info', 'error', 'secondary']

const getRoleColor = name => {
  const idx = (name || '').split('').reduce((a, c) => a + c.charCodeAt(0), 0) % roleColors.length
  
  return roleColors[idx]
}

const headers = [
  { title: 'Pengguna', key: 'user', sortable: false },
  { title: 'Email', key: 'email' },
  { title: 'Jabatan & Cabang', key: 'assignments', sortable: false },
  { title: 'Status', key: 'status', sortable: false },
  { title: 'Aksi', key: 'actions', sortable: false },
]

// =================== ADD USER ===================
const addNewUser = async userData => {
  await $api('/apps/users', { method: 'POST', body: userData })
  fetchUsers()
}

// =================== ASSIGNMENT ===================
const openAssignment = user => {
  editingUser.value = { ...user }
  editingAssignments.value = (user.assignments || []).map(a => ({ ...a }))
  isAssignmentDialogOpen.value = true
}

const addAssignmentRow = () => editingAssignments.value.push({ branch_id: null, role_name: '' })
const removeAssignmentRow = idx => editingAssignments.value.splice(idx, 1)

const saveAssignments = async () => {
  savingAssignment.value = true
  try {
    await $api(`/apps/users/${editingUser.value.id}`, {
      method: 'PUT',
      body: {
        fullName: editingUser.value.fullName,
        email: editingUser.value.email,
        assignments: editingAssignments.value
          .filter(a => a.branch_id && a.role_name)
          .map(a => ({ branch_id: a.branch_id, role: a.role_name })),
      },
    })
    isAssignmentDialogOpen.value = false
    fetchUsers()
  } finally {
    savingAssignment.value = false
  }
}

// =================== DELETE ===================
const confirmDelete = user => { deletingUser.value = user; isDeleteDialogOpen.value = true }

const deleteUser = async () => {
  await $api(`/apps/users/${deletingUser.value.id}`, { method: 'DELETE' })
  isDeleteDialogOpen.value = false
  fetchUsers()
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h4 class="text-h4 font-weight-bold">
          Pengaturan Pengguna
        </h4>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola pengguna, jabatan, dan hak akses per cabang
        </p>
      </div>
      <VBtn
        v-if="$can('create', 'Pengguna')"
        color="primary"
        prepend-icon="ri-user-add-line"
        @click="isAddUserDrawerOpen = true"
      >
        Tambah Pengguna
      </VBtn>
    </div>

    <!-- Main Card -->
    <VCard>
      <!-- Toolbar -->
      <VCardText class="d-flex gap-4 align-center flex-wrap pa-4">
        <VTextField
          v-model="searchQuery"
          placeholder="Cari nama atau email..."
          prepend-inner-icon="ri-search-line"
          density="compact"
          style="max-inline-size: 300px"
          hide-details
          clearable
        />
        <VSpacer />
        <VChip
          color="primary"
          variant="tonal"
          size="small"
        >
          <VIcon
            start
            size="14"
            icon="ri-group-line"
          />
          {{ totalUsers }} Pengguna
        </VChip>
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
              size="38"
              :color="getRoleColor(item.fullName)"
              variant="tonal"
            >
              <span class="text-sm font-weight-bold">
                {{ item.fullName?.charAt(0)?.toUpperCase() }}
              </span>
            </VAvatar>
            <div>
              <RouterLink
                :to="{ name: 'apps-user-view-id', params: { id: item.id } }"
                class="text-link font-weight-semibold d-block text-base"
              >
                {{ item.fullName }}
              </RouterLink>
              <span class="text-xs text-medium-emphasis">@{{ item.username }}</span>
            </div>
          </div>
        </template>

        <!-- Email -->
        <template #item.email="{ item }">
          <span class="text-body-2">{{ item.email }}</span>
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
                  class="font-weight-medium"
                >
                  <VIcon
                    start
                    size="10"
                    icon="ri-shield-user-line"
                  />
                  {{ a.role_name }}
                </VChip>
                <span class="text-xs text-medium-emphasis d-flex align-center gap-1">
                  <VIcon
                    size="10"
                    icon="ri-store-2-line"
                  />
                  {{ a.branch_name }}
                </span>
              </div>
            </div>
            <VChip
              v-else
              size="x-small"
              color="warning"
              variant="tonal"
            >
              <VIcon
                start
                size="10"
                icon="ri-alert-line"
              />
              Belum ada jabatan
            </VChip>
          </div>
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <VChip
            size="small"
            color="success"
            variant="tonal"
          >
            <VIcon
              start
              size="12"
              icon="ri-checkbox-circle-line"
            />
            Aktif
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1 align-center">
            <VTooltip
              v-if="$can('write', 'Pengguna')"
              text="Kelola Jabatan"
              location="top"
            >
              <template #activator="{ props }">
                <IconBtn
                  v-bind="props"
                  size="small"
                  color="primary"
                  @click="openAssignment(item)"
                >
                  <VIcon
                    icon="ri-shield-user-line"
                    size="18"
                  />
                </IconBtn>
              </template>
            </VTooltip>

            <VTooltip
              text="Lihat Detail"
              location="top"
            >
              <template #activator="{ props }">
                <IconBtn
                  v-bind="props"
                  size="small"
                  :to="{ name: 'apps-user-view-id', params: { id: item.id } }"
                >
                  <VIcon
                    icon="ri-eye-line"
                    size="18"
                  />
                </IconBtn>
              </template>
            </VTooltip>

            <VTooltip
              v-if="$can('delete', 'Pengguna')"
              location="top"
            >
              <template #activator="{ props }">
                <IconBtn
                  v-bind="props"
                  size="small"
                  color="error"
                  @click="confirmDelete(item)"
                >
                  <VIcon
                    icon="ri-delete-bin-7-line"
                    size="18"
                  />
                </IconBtn>
              </template>
            </VTooltip>
          </div>
        </template>

        <!-- Pagination -->
        <template #bottom>
          <VDivider />
          <div class="d-flex justify-end align-center gap-x-6 px-4 py-2">
            <span class="text-sm text-medium-emphasis">Baris per halaman:</span>
            <VSelect
              v-model="itemsPerPage"
              :items="[10, 25, 50]"
              density="compact"
              variant="plain"
              style="max-width: 80px"
            />
            <span class="text-sm">
              {{ Math.min((page - 1) * itemsPerPage + 1, totalUsers) }}-{{ Math.min(page * itemsPerPage, totalUsers) }} dari {{ totalUsers }}
            </span>
            <div class="d-flex gap-1">
              <VBtn
                icon="ri-arrow-left-s-line"
                variant="text"
                density="comfortable"
                :disabled="page <= 1"
                @click="page--"
              />
              <VBtn
                icon="ri-arrow-right-s-line"
                variant="text"
                density="comfortable"
                :disabled="page >= Math.ceil(totalUsers / itemsPerPage)"
                @click="page++"
              />
            </div>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- ====== Add User Drawer ====== -->
    <AddNewUserDrawer
      v-model:is-drawer-open="isAddUserDrawerOpen"
      @user-data="addNewUser"
    />

    <!-- ====== Assignment Dialog ====== -->
    <VDialog
      v-model="isAssignmentDialogOpen"
      max-width="600"
      persistent
    >
      <VCard v-if="editingUser">
        <VCardTitle class="d-flex align-center gap-3 pa-5 pb-3">
          <VAvatar
            size="40"
            :color="getRoleColor(editingUser.fullName)"
            variant="tonal"
          >
            <span class="font-weight-bold">{{ editingUser.fullName?.charAt(0)?.toUpperCase() }}</span>
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-bold">
              {{ editingUser.fullName }}
            </div>
            <div class="text-caption text-medium-emphasis">
              Kelola penugasan jabatan per cabang
            </div>
          </div>
          <VSpacer />
          <IconBtn @click="isAssignmentDialogOpen = false">
            <VIcon icon="ri-close-line" />
          </IconBtn>
        </VCardTitle>

        <VDivider />

        <VCardText class="pt-5">
          <div class="text-subtitle-2 font-weight-semibold mb-3 d-flex align-center gap-2">
            <VIcon
              icon="ri-list-check-3"
              size="16"
              color="primary"
            />
            Penugasan Aktif
          </div>

          <div
            v-if="editingAssignments.length === 0"
            class="text-center py-6 text-medium-emphasis"
          >
            <VIcon
              icon="ri-shield-user-line"
              size="48"
              color="secondary"
              class="mb-2 d-block mx-auto"
            />
            <p class="text-body-2">
              Belum ada jabatan. Tambah penugasan baru di bawah.
            </p>
          </div>

          <div
            v-for="(assignment, idx) in editingAssignments"
            :key="idx"
            class="d-flex gap-3 mb-3 align-center"
          >
            <VChip
              size="x-small"
              :color="getRoleColor(assignment.role_name)"
              class="flex-shrink-0"
            >
              {{ idx + 1 }}
            </VChip>
            <VSelect
              v-model="assignment.branch_id"
              :items="availableBranches"
              item-title="name"
              item-value="id"
              label="Cabang / Toko"
              density="compact"
              variant="outlined"
              hide-details
              style="flex: 1"
            />
            <VSelect
              v-model="assignment.role_name"
              :items="availableRoles"
              label="Jabatan"
              density="compact"
              variant="outlined"
              hide-details
              style="flex: 1"
            />
            <VBtn
              icon
              variant="tonal"
              color="error"
              size="small"
              @click="removeAssignmentRow(idx)"
            >
              <VIcon
                icon="ri-delete-bin-7-line"
                size="15"
              />
            </VBtn>
          </div>

          <VBtn
            variant="tonal"
            color="primary"
            size="small"
            prepend-icon="ri-add-circle-line"
            class="mt-2"
            @click="addAssignmentRow"
          >
            Tambah Penugasan
          </VBtn>

          <VAlert
            type="info"
            variant="tonal"
            density="compact"
            icon="ri-information-line"
            class="mt-4 text-caption"
          >
            Tambah lebih dari 1 baris jika pengguna bertugas di beberapa cabang sekaligus.
          </VAlert>
        </VCardText>

        <VDivider />
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn
            variant="outlined"
            color="secondary"
            @click="isAssignmentDialogOpen = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            :loading="savingAssignment"
            prepend-icon="ri-save-line"
            @click="saveAssignments"
          >
            Simpan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ====== Delete Dialog ====== -->
    <VDialog
      v-model="isDeleteDialogOpen"
      max-width="420"
    >
      <VCard v-if="deletingUser">
        <VCardText class="pa-6 text-center">
          <VAvatar
            color="error"
            variant="tonal"
            size="64"
            class="mb-4"
          >
            <VIcon
              icon="ri-delete-bin-7-line"
              size="32"
            />
          </VAvatar>
          <h5 class="text-h5 mb-2">
            Hapus Pengguna?
          </h5>
          <p class="text-body-1 text-medium-emphasis mb-0">
            Pengguna <strong>{{ deletingUser.fullName }}</strong> dan semua data jabatannya akan dihapus permanen.
          </p>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn
            variant="outlined"
            @click="isDeleteDialogOpen = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            prepend-icon="ri-delete-bin-7-line"
            @click="deleteUser"
          >
            Hapus
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
