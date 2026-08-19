<script setup>
import { ref, computed, onMounted } from 'vue'
import AddNewEmployeeDrawer from './AddNewEmployeeDrawer.vue'
import SimpleConfirmDialog from '@/components/dialogs/SimpleConfirmDialog.vue'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    public: true,
  },
})

const employees = ref([])
const branches = ref([])
const roles = ref([])
const search = ref('')
const selectedBranch = ref('all')
const selectedStatus = ref('all')
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

const summaryData = ref({
  total: 0,
  active: 0,
  with_user: 0,
})

const stats = computed(() => {
  const all = employees.value || []
  return {
    total: summaryData.value.total || totalItems.value || all.length,
    active: summaryData.value.active || all.filter(e => !e.status || e.status === 'Aktif' || e.status === 'aktif').length,
    withUser: summaryData.value.with_user || all.filter(e => !!e.user_id).length,
  }
})

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
    if (selectedBranch.value !== 'all') {
      params.branch_id = selectedBranch.value
    }
    if (selectedStatus.value !== 'all') {
      params.status = selectedStatus.value
    }
    
    const data = await $api('/apps/employees', { query: params })

    employees.value = data.data || data
    if (data.total !== undefined) {
      totalItems.value = data.total
    }
    if (data.summary) {
      summaryData.value = data.summary
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
  }, 400)
}

const fetchBranches = async () => {
  try {
    const data = await $api('/apps/branches')
    branches.value = data.data || data
  } catch (error) {
    console.error(error)
  }
}

const fetchRoles = async () => {
  try {
    const data = await $api('/apps/roles')
    roles.value = data.data || data
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
  if (!isConfirmed || !employeeToDelete.value) return
  
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
  { title: 'IDENTITAS KARYAWAN', key: 'name' },
  { title: 'PENUGASAN CABANG', key: 'branch_name' },
  { title: 'KONTAK & TELEPON', key: 'phone', sortable: false },
  { title: 'AKUN LOGIN SISTEM', key: 'user_id', align: 'center' },
  { title: 'STATUS', key: 'status', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-center justify-space-between mb-4 gap-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Manajemen Karyawan & Staf Operasional
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola data kepegawaian internal, penempatan cabang toko/gudang, dan integrasi akun login sistem.
        </p>
      </div>
      
      <div class="d-flex gap-3">
        <VBtn
          color="secondary"
          variant="tonal"
          prepend-icon="ri-refresh-line"
          :loading="isLoading"
          @click="fetchEmployees"
        >
          Muat Ulang
        </VBtn>

        <VBtn
          color="primary"
          prepend-icon="ri-user-add-line"
          @click="openAddDrawer"
        >
          Tambah Karyawan
        </VBtn>
      </div>
    </div>

    <!-- KPI Summary Row -->
    <VRow class="mb-4">
      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-primary font-weight-bold">TOTAL STAF KARYAWAN</div>
              <div class="text-h4 font-weight-bold text-primary mt-1">{{ stats.total }} <span class="text-caption text-medium-emphasis">Orang</span></div>
            </div>
            <VAvatar color="primary" variant="tonal" rounded size="44">
              <VIcon icon="ri-team-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Seluruh karyawan terdaftar</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-success font-weight-bold">KARYAWAN AKTIF</div>
              <div class="text-h4 font-weight-bold text-success mt-1">{{ stats.active }} <span class="text-caption text-medium-emphasis">Aktif</span></div>
            </div>
            <VAvatar color="success" variant="tonal" rounded size="44">
              <VIcon icon="ri-user-follow-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Bertugas di cabang toko & gudang</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="4">
        <VCard elevation="2" class="pa-4 border-s-lg border-info">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-info font-weight-bold">TERHUBUNG AKUN POS</div>
              <div class="text-h4 font-weight-bold text-info mt-1">{{ stats.withUser }} <span class="text-caption text-medium-emphasis">Akun</span></div>
            </div>
            <VAvatar color="info" variant="tonal" rounded size="44">
              <VIcon icon="ri-shield-user-line" size="24" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Memiliki hak akses login aplikasi</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Table Card -->
    <VCard elevation="2">
      <!-- Card Toolbar -->
      <VCardItem class="pa-4">
        <VRow align="center">
          <VCol cols="12" sm="6" md="4">
            <VTextField
              v-model="search"
              prepend-inner-icon="ri-search-line"
              placeholder="Cari nama, email, posisi, telepon..."
              density="compact"
              variant="outlined"
              hide-details
              clearable
              @update:model-value="handleSearch"
            />
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VSelect
              v-model="selectedBranch"
              :items="[{ id: 'all', name: 'Semua Cabang' }, ...branches]"
              item-title="name"
              item-value="id"
              density="compact"
              variant="outlined"
              hide-details
              @update:model-value="fetchEmployees"
            />
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VSelect
              v-model="selectedStatus"
              :items="[
                { title: 'Semua Status', value: 'all' },
                { title: 'Karyawan Aktif', value: 'Aktif' },
                { title: 'Nonaktif / Resign', value: 'Nonaktif' }
              ]"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
              @update:model-value="fetchEmployees"
            />
          </VCol>

          <VCol cols="12" md="2" class="text-right d-none d-md-block">
            <div class="text-caption text-medium-emphasis">
              Total: <strong>{{ totalItems }}</strong> Staf
            </div>
          </VCol>
        </VRow>
      </VCardItem>

      <VDivider />

      <!-- Data Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="employees"
        :items-length="totalItems"
        :loading="isLoading"
        hover
        class="text-no-wrap"
        @update:options="fetchEmployees"
      >
        <!-- Employee Name & Avatar -->
        <template #item.name="{ item }">
          <div class="d-flex align-center py-2">
            <VAvatar
              size="40"
              color="primary"
              variant="tonal"
              class="me-3 rounded-lg border flex-shrink-0"
            >
              <VIcon icon="ri-user-3-line" size="22" />
            </VAvatar>
            <div>
              <div class="font-weight-bold text-subtitle-2">{{ item.name }}</div>
              <div class="text-caption text-disabled">{{ item.position || 'Staf Operasional' }}</div>
            </div>
          </div>
        </template>

        <!-- Branch Assignment -->
        <template #item.branch_name="{ item }">
          <VChip
            v-if="item.branch"
            size="small"
            variant="tonal"
            color="secondary"
            class="font-weight-medium"
          >
            <VIcon icon="ri-store-2-line" size="14" class="me-1" />
            {{ item.branch.name }}
          </VChip>
          <span v-else class="text-disabled text-caption">Gudang / Pusat</span>
        </template>

        <!-- Contact & Email -->
        <template #item.phone="{ item }">
          <div class="d-flex flex-column gap-1">
            <div class="text-caption d-flex align-center">
              <VIcon size="14" icon="ri-phone-line" class="me-1 text-success" />
              <span>{{ item.phone || '-' }}</span>
            </div>
            <div v-if="item.email" class="text-caption d-flex align-center">
              <VIcon size="14" icon="ri-mail-line" class="me-1 text-primary" />
              <span>{{ item.email }}</span>
            </div>
          </div>
        </template>

        <!-- System User Status -->
        <template #item.user_id="{ item }">
          <VChip
            v-if="item.user_id"
            color="success"
            size="small"
            variant="tonal"
            class="font-weight-bold"
          >
            <VIcon icon="ri-shield-keyhole-line" size="14" class="me-1" />
            Akun Aktif
          </VChip>
          <VChip
            v-else
            color="secondary"
            size="small"
            variant="tonal"
          >
            Tanpa Login
          </VChip>
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <VChip
            :color="(item.status === 'Aktif' || item.status === 'active') ? 'success' : 'error'"
            size="small"
            variant="elevated"
            class="font-weight-bold"
          >
            <VIcon
              :icon="(item.status === 'Aktif' || item.status === 'active') ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill'"
              size="14"
              class="me-1"
            />
            {{ (item.status === 'Aktif' || item.status === 'active') ? 'Aktif' : (item.status || 'Nonaktif') }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex align-center justify-center gap-1">
            <VBtn
              size="small"
              variant="text"
              color="primary"
              icon="ri-edit-box-line"
              title="Edit Data Karyawan"
              @click="editEmployee(item)"
            />
            <VBtn
              size="small"
              variant="text"
              color="error"
              icon="ri-delete-bin-line"
              title="Hapus Karyawan"
              @click="confirmDeleteEmployee(item.id)"
            />
          </div>
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
      title="Hapus Data Karyawan?"
      message="Apakah Anda yakin ingin menghapus data karyawan ini dari sistem kepegawaian?"
      confirm-text="Ya, Hapus"
      cancel-text="Batal"
      @confirm="executeDeleteEmployee"
    />
  </div>
</template>
