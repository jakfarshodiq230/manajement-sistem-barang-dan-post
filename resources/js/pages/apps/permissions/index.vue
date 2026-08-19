<script setup>
import { ref, computed } from 'vue'
import { paginationMeta } from '@/utils/paginationMeta'
import AddEditPermissionDialog from '@/components/dialogs/AddEditPermissionDialog.vue'

definePage({
  meta: {
    public: true,
  },
})

const headers = [
  {
    title: 'Nama Izin (Permission)',
    key: 'name',
  },
  {
    title: 'Peran yang Diberikan',
    key: 'assignedTo',
    sortable: false,
  },
  {
    title: 'Tanggal Dibuat',
    key: 'createdDate',
    sortable: false,
  },
  {
    title: 'Aksi',
    key: 'actions',
    sortable: false,
  },
]

const search = ref('')

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

const isPermissionDialogVisible = ref(false)
const isAddPermissionDialogVisible = ref(false)
const permissionName = ref('')

const colors = {
  'super admin': { color: 'error', text: 'Super Admin' },
  'owner': { color: 'error', text: 'Owner' },
  'admin pusat': { color: 'primary', text: 'Admin Pusat' },
  'admin cabang': { color: 'info', text: 'Admin Cabang' },
  'kasir': { color: 'success', text: 'Kasir' },
  'admin gudang': { color: 'warning', text: 'Admin Gudang' },
  'manager': { color: 'secondary', text: 'Manager' },
  'auditor': { color: 'primary', text: 'Auditor' },
}

const { data: permissionsData, execute: fetchPermissions } = await useApi(createUrl('/apps/permissions', {
  query: {
    q: search,
    itemsPerPage,
    page,
    sortBy,
    orderBy,
  },
}))

const permissions = computed(() => permissionsData.value?.permissions || [])
const totalPermissions = computed(() => permissionsData.value?.totalPermissions || 0)

const editPermission = name => {
  isPermissionDialogVisible.value = true
  permissionName.value = name
}

const deletePermission = async name => {
  if (confirm(`Apakah Anda yakin ingin menghapus izin "${name}"?`)) {
    try {
      await $api(`/apps/permissions/${name}`, { method: 'DELETE' })
      fetchPermissions()
    } catch (e) {
      console.error(e)
    }
  }
}

const resolveActionColor = name => {
  const n = String(name || '').toLowerCase()
  if (n.includes('create') || n.includes('tambah')) return 'success'
  if (n.includes('write') || n.includes('ubah') || n.includes('edit')) return 'warning'
  if (n.includes('delete') || n.includes('hapus')) return 'error'
  if (n.includes('pin')) return 'error'
  if (n.includes('approve') || n.includes('persetujuan')) return 'secondary'
  if (n.includes('export') || n.includes('import')) return 'info'
  return 'primary'
}
</script>

<template>
  <Suspense>
    <div class="permissions-page">
      <!-- Header Banner -->
      <div class="mb-6">
        <div class="d-flex align-center gap-2 mb-1">
          <VChip color="primary" variant="tonal" size="small" class="font-weight-bold">
            <VIcon icon="ri-key-2-line" size="14" class="me-1" />
            KEAMANAN & RBAC
          </VChip>
        </div>
        <h1 class="text-h4 font-weight-extrabold text-high-emphasis mb-1">
          Daftar Izin Sistem (Permissions)
        </h1>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Daftar seluruh hak izin tindakan (Read, Create, Write, Delete, Approve, Export, PIN) yang terikat pada peran pengguna.
        </p>
      </div>

      <!-- KPI Summary Cards -->
      <VRow class="mb-6">
        <VCol cols="12" sm="4">
          <VCard class="pa-5 rounded-xl border elevation-1">
            <div class="d-flex align-center gap-4">
              <VAvatar color="primary" variant="tonal" size="48" rounded="lg">
                <VIcon icon="ri-key-2-line" size="26" />
              </VAvatar>
              <div>
                <h4 class="text-h5 font-weight-bold mb-0">{{ totalPermissions }}</h4>
                <span class="text-caption text-medium-emphasis">Total Izin Terdaftar</span>
              </div>
            </div>
          </VCard>
        </VCol>

        <VCol cols="12" sm="4">
          <VCard class="pa-5 rounded-xl border elevation-1">
            <div class="d-flex align-center gap-4">
              <VAvatar color="success" variant="tonal" size="48" rounded="lg">
                <VIcon icon="ri-shield-check-line" size="26" />
              </VAvatar>
              <div>
                <h4 class="text-h5 font-weight-bold mb-0">RBAC Standar</h4>
                <span class="text-caption text-medium-emphasis">Tersinkronisasi Spatie</span>
              </div>
            </div>
          </VCard>
        </VCol>

        <VCol cols="12" sm="4">
          <VCard class="pa-5 rounded-xl border elevation-1">
            <div class="d-flex align-center gap-4">
              <VAvatar color="warning" variant="tonal" size="48" rounded="lg">
                <VIcon icon="ri-lock-password-line" size="26" />
              </VAvatar>
              <div>
                <h4 class="text-h5 font-weight-bold mb-0">PIN & Approval</h4>
                <span class="text-caption text-medium-emphasis">Otorisasi Supervisor</span>
              </div>
            </div>
          </VCard>
        </VCol>
      </VRow>

      <!-- Main Data Table Card -->
      <VCard class="rounded-xl border elevation-1">
        <VCardText class="pa-5 d-flex align-center justify-space-between gap-4 flex-wrap">
          <VTextField
            v-model="search"
            density="compact"
            variant="outlined"
            rounded="lg"
            placeholder="Cari nama izin atau peran..."
            prepend-inner-icon="ri-search-line"
            clearable
            hide-details
            style="max-inline-size: 20rem; min-inline-size: 14rem;"
          />

          <VBtn
            color="primary"
            class="font-weight-bold text-none"
            prepend-icon="ri-add-line"
            @click="isAddPermissionDialogVisible = true"
          >
            Tambah Izin Baru
          </VBtn>
        </VCardText>

        <VDivider />

        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          :items-length="totalPermissions"
          :items-per-page-options="[
            { value: 10, title: '10' },
            { value: 20, title: '20' },
            { value: 50, title: '50' },
          ]"
          :headers="headers"
          :items="permissions"
          item-value="name"
          class="text-no-wrap"
          @update:options="updateOptions"
        >
          <!-- Name -->
          <template #item.name="{ item }">
            <div class="d-flex align-center gap-2 py-2">
              <VChip
                size="small"
                :color="resolveActionColor(item.name)"
                variant="tonal"
                class="font-weight-bold"
              >
                <VIcon icon="ri-key-line" size="13" class="me-1" />
                {{ item.name }}
              </VChip>
            </div>
          </template>

          <!-- Assigned To -->
          <template #item.assignedTo="{ item }">
            <div class="d-flex flex-wrap gap-1 py-1">
              <VChip
                v-for="text in item.assignedTo"
                :key="text"
                :color="(colors[text.toLowerCase()] && colors[text.toLowerCase()].color) ? colors[text.toLowerCase()].color : 'primary'"
                size="x-small"
                variant="elevated"
                class="font-weight-medium"
              >
                {{ (colors[text.toLowerCase()] && colors[text.toLowerCase()].text) ? colors[text.toLowerCase()].text : text }}
              </VChip>
              <span v-if="!item.assignedTo || item.assignedTo.length === 0" class="text-caption text-medium-emphasis">
                - Belum Ditugaskan -
              </span>
            </div>
          </template>

          <!-- Created Date -->
          <template #item.createdDate="{ item }">
            <span class="text-body-2 text-medium-emphasis">{{ item.createdDate || '-' }}</span>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex align-center gap-1">
              <IconBtn
                size="small"
                color="primary"
                variant="tonal"
                title="Edit Izin"
                @click="editPermission(item.name)"
              >
                <VIcon icon="ri-edit-line" size="18" />
              </IconBtn>

              <IconBtn
                size="small"
                color="error"
                variant="tonal"
                title="Hapus Izin"
                @click="deletePermission(item.name)"
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
                  hide-details
                  :items="[10, 20, 50, 100]"
                />
              </div>

              <p class="d-flex align-center text-body-2 text-high-emphasis me-2 mb-0">
                {{ paginationMeta({ page, itemsPerPage }, totalPermissions) }}
              </p>

              <div class="d-flex gap-x-1 align-center">
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
                  :disabled="page >= Math.ceil(totalPermissions / itemsPerPage)"
                  @click="page >= Math.ceil(totalPermissions / itemsPerPage) ? page = Math.ceil(totalPermissions / itemsPerPage) : page++"
                />
              </div>
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </div>

    <template #fallback>
      <div class="d-flex justify-center align-center py-16">
        <VProgressCircular indeterminate color="primary" size="48" />
      </div>
    </template>
  </Suspense>

  <AddEditPermissionDialog
    v-model:is-dialog-visible="isPermissionDialogVisible"
    v-model:permission-name="permissionName"
    @update:permission-name="fetchPermissions"
  />
  <AddEditPermissionDialog
    v-model:is-dialog-visible="isAddPermissionDialogVisible"
    @update:permission-name="fetchPermissions"
  />
</template>
