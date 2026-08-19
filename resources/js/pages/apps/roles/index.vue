<script setup>
import RoleCards from '@/views/apps/roles/RoleCards.vue'
import UserList from '@/views/apps/roles/UserList.vue'

definePage({
  meta: {
    public: true,
  },
})
</script>

<template>
  <Suspense>
    <div class="roles-page">
      <!-- Header Banner -->
      <div class="mb-6">
        <div class="d-flex align-center gap-2 mb-1">
          <VChip color="primary" variant="tonal" size="small" class="font-weight-bold">
            <VIcon icon="ri-shield-keyhole-line" size="14" class="me-1" />
            KEAMANAN & HAK AKSES (RBAC)
          </VChip>
        </div>
        <h1 class="text-h4 font-weight-extrabold text-high-emphasis mb-1">
          Manajemen Peran & Hak Akses
        </h1>
        <p class="text-body-1 text-medium-emphasis mb-0">
          Kelola peran pengguna, hak otorisasi modul, dan pengelompokan akun staf kasir, gudang, admin, dan owner.
        </p>
      </div>

      <!-- Roles Cards Grid -->
      <div class="mb-8">
        <RoleCards />
      </div>

      <!-- User List Section -->
      <div>
        <div class="mb-4">
          <h2 class="text-h5 font-weight-bold mb-1">
            Daftar Pengguna & Penugasan Peran
          </h2>
          <p class="text-body-2 text-medium-emphasis mb-0">
            Daftar seluruh akun karyawan dan administrator yang terhubung dengan peran aktif.
          </p>
        </div>

        <UserList />
      </div>
    </div>
    <template #fallback>
      <div class="d-flex justify-center align-center py-16">
        <VProgressCircular indeterminate color="primary" size="48" />
      </div>
    </template>
  </Suspense>
</template>
