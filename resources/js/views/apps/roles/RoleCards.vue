<script setup>
import { ref, onMounted } from 'vue'

const roles = ref([])
const isLoading = ref(false)

const fetchRoles = async () => {
  isLoading.value = true
  try {
    const res = await $api('/apps/roles')
    roles.value = res.data || res
  } catch (err) {
    console.error(err)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchRoles()
})

const isRoleDialogVisible = ref(false)
const roleDetail = ref()
const isAddRoleDialogVisible = ref(false)

const editPermission = value => {
  isRoleDialogVisible.value = true
  roleDetail.value = value
}

const deleteRole = async id => {
  if (confirm('Apakah Anda yakin ingin menghapus peran ini?')) {
    try {
      await $api(`/apps/roles/${id}`, { method: 'DELETE' })
      fetchRoles()
    } catch (err) {
      console.error(err)
    }
  }
}

const getRoleConfig = roleName => {
  const name = String(roleName || '').toLowerCase()
  if (name.includes('super') || name.includes('owner')) {
    return {
      color: 'error',
      icon: 'ri-vip-crown-line',
      desc: 'Memiliki otoritas tertinggi untuk seluruh modul, pengaturan master, persetujuan opname, dan laporan keuangan.',
      badge: 'Akses Penuh (Full Control)',
    }
  }
  if (name.includes('pusat') || name.includes('master')) {
    return {
      color: 'primary',
      icon: 'ri-building-line',
      desc: 'Mengelola pengadaan barang PO, katalog produk pusat, penerimaan supplier, dan distribusi mutasi.',
      badge: 'Manajemen Logistik Pusat',
    }
  }
  if (name.includes('cabang')) {
    return {
      color: 'info',
      icon: 'ri-store-2-line',
      desc: 'Mengelola persediaan cabang toko, verifikasi mutasi masuk, dan pemantauan transaksi toko.',
      badge: 'Operasional Cabang Toko',
    }
  }
  if (name.includes('kasir')) {
    return {
      color: 'success',
      icon: 'ri-shopping-cart-2-line',
      desc: 'Mengoperasikan layar kasir POS, scan barcode, cetak struk kwitansi, dan closing kas harian.',
      badge: 'Kasir & Point of Sale',
    }
  }
  if (name.includes('gudang')) {
    return {
      color: 'warning',
      icon: 'ri-archive-stack-line',
      desc: 'Mencatat penerimaan nomor batch, kadaluarsa FEFO/FIFO, dan pelaksanaan audit stock opname.',
      badge: 'Gudang & Batch Tracking',
    }
  }
  if (name.includes('manager')) {
    return {
      color: 'secondary',
      icon: 'ri-user-star-line',
      desc: 'Otorisasi PIN supervisor, persetujuan retur, analisis usia stok, dan rekapitulasi performa toko.',
      badge: 'Supervisor & Approval',
    }
  }
  return {
    color: 'primary',
    icon: 'ri-shield-user-line',
    desc: 'Peran khusus dengan hak akses modul yang telah disesuaikan dengan kebutuhan operasional.',
    badge: 'Peran Kustom',
  }
}
</script>

<template>
  <div>
    <!-- Loading State -->
    <div v-if="isLoading && roles.length === 0" class="d-flex justify-center py-12">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>

    <!-- Roles Grid -->
    <VRow v-else>
      <VCol
        v-for="item in roles"
        :key="item.role"
        cols="12"
        sm="6"
        lg="4"
      >
        <VCard class="h-100 role-card rounded-xl border elevation-1 d-flex flex-column justify-space-between" hover>
          <VCardText class="pa-5">
            <!-- Header Card -->
            <div class="d-flex align-center justify-space-between mb-4">
              <div class="d-flex align-center gap-3">
                <VAvatar
                  :color="getRoleConfig(item.role).color"
                  variant="tonal"
                  size="48"
                  rounded="lg"
                >
                  <VIcon :icon="getRoleConfig(item.role).icon" size="26" />
                </VAvatar>
                <div>
                  <h3 class="text-h6 font-weight-bold mb-0">
                    {{ item.role }}
                  </h3>
                  <span class="text-caption text-medium-emphasis">
                    {{ item.totalUsers ?? (item.users?.length || 0) }} Pengguna Terdaftar
                  </span>
                </div>
              </div>

              <!-- Delete Action Button (if not default Super Admin) -->
              <IconBtn
                v-if="item.role !== 'Super Admin'"
                color="error"
                size="small"
                variant="text"
                title="Hapus Peran"
                @click="deleteRole(item.id)"
              >
                <VIcon icon="ri-delete-bin-line" size="18" />
              </IconBtn>
            </div>

            <!-- Role Badge & Scope Description -->
            <div class="mb-3">
              <VChip
                size="x-small"
                :color="getRoleConfig(item.role).color"
                variant="tonal"
                class="font-weight-bold mb-2"
              >
                {{ getRoleConfig(item.role).badge }}
              </VChip>
              <p class="text-caption text-medium-emphasis mb-0" style="line-height: 1.5; min-height: 3em;">
                {{ getRoleConfig(item.role).desc }}
              </p>
            </div>
          </VCardText>

          <VDivider />

          <!-- Card Footer Actions -->
          <VCardText class="pa-4 bg-var-theme-background d-flex align-center justify-space-between">
            <div class="d-flex align-center gap-1 text-caption text-medium-emphasis">
              <VIcon icon="ri-key-2-line" size="15" />
              <span>{{ item.details?.permissions?.length || 0 }} Izin Modul</span>
            </div>

            <VBtn
              size="small"
              variant="tonal"
              :color="getRoleConfig(item.role).color"
              class="font-weight-bold text-none"
              prepend-icon="ri-edit-line"
              @click="editPermission(item.details)"
            >
              Edit Hak Akses
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Add New Role Card -->
      <VCol
        cols="12"
        sm="6"
        lg="4"
      >
        <VCard class="h-100 add-role-card rounded-xl border elevation-1 pa-5 d-flex flex-column justify-center align-center text-center" hover>
          <VAvatar color="primary" variant="tonal" size="56" rounded="xl" class="mb-3">
            <VIcon icon="ri-user-add-line" size="30" />
          </VAvatar>

          <h3 class="text-h6 font-weight-bold mb-1">
            Tambah Peran Baru
          </h3>
          <p class="text-caption text-medium-emphasis mb-4 max-w-260">
            Buat grup peran baru dengan izin modul kustom sesuai kebutuhan staf toko Anda.
          </p>

          <VBtn
            color="primary"
            class="font-weight-bold text-none"
            prepend-icon="ri-add-line"
            @click="isAddRoleDialogVisible = true"
          >
            Buat Peran Baru
          </VBtn>
        </VCard>
      </VCol>
    </VRow>

    <!-- Dialogs -->
    <AddEditRoleDialog
      v-model:is-dialog-visible="isAddRoleDialogVisible"
      @update:role-permissions="fetchRoles"
    />

    <AddEditRoleDialog
      v-model:is-dialog-visible="isRoleDialogVisible"
      v-model:role-permissions="roleDetail"
      @update:role-permissions="fetchRoles"
    />
  </div>
</template>

<style scoped>
.role-card {
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.role-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 20px -4px rgba(0, 0, 0, 0.08) !important;
}

.add-role-card {
  border-style: dashed !important;
  border-width: 2px !important;
  background-color: rgba(var(--v-theme-primary), 0.02) !important;
}

.max-w-260 {
  max-width: 260px;
}
</style>
