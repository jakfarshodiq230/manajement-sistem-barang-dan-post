<script setup>
import { ref, computed, onMounted } from 'vue'
import AddNewEmployeeDrawer from './AddNewEmployeeDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'
import { useSnackbarStore } from '@/stores/snackbar'

const employees = ref([])
const branches = ref([])
const roles = ref([])
const search = ref('')
const isLoading = ref(false)

// Pagination
const page = ref(1)
const itemsPerPage = ref(10)
const totalItems = ref(0)
let searchTimeout = null

const isDrawerOpen = ref(false)
const selectedEmployee = ref(null)

const isConfirmDeleteDialogVisible = ref(false)
const employeeToDelete = ref(null)

const snackbar = useSnackbarStore()

const fetchEmployees = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }
    
    if (search.value) {
      params.search = search.value
    }
    
    const data = await $api('/apps/employees', { query: params })

    employees.value = data.data || data
    if (data.total !== undefined) {
      totalItems.value = data.total
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal memuat data karyawan', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchEmployees()
  }, 500)
}

const fetchBranches = async () => {
  try {
    const data = await $api('/apps/branches')

    branches.value = data
  } catch (error) {
    console.error(error)
  }
}

const fetchRoles = async () => {
  try {
    const data = await $api('/apps/roles')

    roles.value = data
  } catch (error) {
    console.error(error)
  }
}

onMounted(() => {
  fetchEmployees()
  fetchBranches()
  fetchRoles()
})

const openAddDrawer = () => {
  selectedEmployee.value = null
  isDrawerOpen.value = true
}

const editEmployee = employee => {
  selectedEmployee.value = employee
  isDrawerOpen.value = true
}

const saveEmployee = async employeeData => {
  try {
    if (selectedEmployee.value && selectedEmployee.value.id) {
      await $api(`/apps/employees/${selectedEmployee.value.id}`, {
        method: 'PUT',
        body: employeeData,
      })
      snackbar.show('Data karyawan berhasil diperbarui', 'success')
    } else {
      await $api('/apps/employees', {
        method: 'POST',
        body: employeeData,
      })
      snackbar.show('Karyawan baru berhasil ditambahkan', 'success')
    }
    fetchEmployees()
    isDrawerOpen.value = false
  } catch (error) {
    console.error(error)

    const errMsg = error?.response?.data?.message || 'Terjadi kesalahan saat menyimpan data karyawan'

    snackbar.show(errMsg, 'error')
  }
}

const confirmDeleteEmployee = id => {
  employeeToDelete.value = id
  isConfirmDeleteDialogVisible.value = true
}

const executeDeleteEmployee = async isConfirmed => {
  if (!isConfirmed) return
  
  try {
    await $api(`/apps/employees/${employeeToDelete.value}`, { method: 'DELETE' })
    snackbar.show('Karyawan berhasil dihapus', 'success')
    fetchEmployees()
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menghapus karyawan', 'error')
  } finally {
    employeeToDelete.value = null
  }
}

const headers = [
  { title: 'NAMA', key: 'name' },
  { title: 'CABANG', key: 'branch_name' },
  { title: 'KONTAK', key: 'phone' },
  { title: 'STATUS AKSES', key: 'user_id' },
  { title: 'STATUS', key: 'status' },
  { title: 'AKSI', key: 'actions', sortable: false },
]

const getRoleName = roleId => {
  const role = roles.value.find(r => r.id === roleId)
  
  return role ? role.role : '-'
}
</script>

<template>
  <section>
    <!-- Page Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 mb-0">
          Manajemen Karyawan
        </h2>
      </div>
      
      <div class="d-flex gap-4">
        <VBtn
          prepend-icon="ri-add-line"
          color="primary"
          @click="openAddDrawer"
        >
          Tambah Karyawan
        </VBtn>
      </div>
    </div>

    <VCard>
      <VCardItem class="pa-4 pb-0">
        <div class="d-flex align-center justify-space-between w-100">
          <VCardTitle class="px-0">
            Daftar Karyawan
          </VCardTitle>
          <div style="width: 250px;">
            <VTextField
              v-model="search"
              prepend-inner-icon="ri-search-line"
              placeholder="Cari Karyawan..."
              density="compact"
              hide-details
              variant="outlined"
              clearable
              @update:model-value="handleSearch"
            />
          </div>
        </div>
      </VCardItem>

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="employees"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        @update:options="fetchEmployees"
      >
        <template #item.name="{ item }">
          <div class="d-flex flex-column">
            <h6 class="text-h6 font-weight-regular">
              {{ item.name }}
            </h6>
            <span class="text-body-2 text-medium-emphasis">NIK: {{ item.nik || '-' }}</span>
          </div>
        </template>
        
        <template #item.branch_name="{ item }">
          <div class="d-flex flex-column">
            <span>{{ item.branch_name || 'Pusat' }}</span>
            <span class="text-body-2 text-primary font-weight-medium">{{ getRoleName(item.role_id) }}</span>
          </div>
        </template>

        <template #item.phone="{ item }">
          <div class="d-flex flex-column">
            <span>{{ item.phone || '-' }}</span>
            <span class="text-body-2 text-medium-emphasis">{{ item.email || '-' }}</span>
          </div>
        </template>
        
        <template #item.user_id="{ item }">
          <VChip
            :color="item.user_id ? 'success' : 'secondary'"
            size="small"
            class="font-weight-medium"
          >
            {{ item.user_id ? 'Bisa Login' : 'Tidak Ada Akses' }}
          </VChip>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="item.status === 'Aktif' ? 'primary' : (item.status === 'Resign' ? 'warning' : 'error')"
            size="small"
          >
            {{ item.status }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <IconBtn
            v-if="$can('write', 'Manajemen Karyawan')"
            size="small"
            @click="editEmployee(item)"
          >
            <VIcon icon="ri-pencil-line" />
          </IconBtn>
          <IconBtn
            v-if="$can('delete', 'Manajemen Karyawan')"
            size="small"
            color="error"
            @click="confirmDeleteEmployee(item.id)"
          >
            <VIcon icon="ri-delete-bin-line" />
          </IconBtn>
        </template>
      </VDataTableServer>
    </VCard>

    <AddNewEmployeeDrawer
      v-model:is-drawer-open="isDrawerOpen"
      :selected-employee="selectedEmployee"
      :branches-list="branches"
      :roles-list="roles"
      @employee-data="saveEmployee"
    />

    <SimpleConfirmDialog
      v-model:is-dialog-visible="isConfirmDeleteDialogVisible"
      title="Hapus Karyawan?"
      message="Apakah Anda yakin ingin menghapus data karyawan ini?"
      confirm-text="Ya, Hapus"
      cancel-text="Batal"
      @confirm="executeDeleteEmployee"
    />
  </section>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Manajemen Karyawan
</route>
