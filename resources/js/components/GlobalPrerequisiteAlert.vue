<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const isReady = ref(true)
const missingModules = ref([])
const router = useRouter()
const isLoading = ref(true)

const checkSystemStatus = async () => {
  try {
    const response = await $api('/system/prerequisites')
    isReady.value = response.is_ready
    missingModules.value = response.missing_modules
  } catch (error) {
    console.error('Gagal mengecek status sistem:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  checkSystemStatus()
})

const navigateTo = (path) => {
  window.location.href = path
}
</script>

<template>
  <VAlert
    v-if="!isLoading && !isReady"
    color="error"
    variant="tonal"
    icon="ri-error-warning-fill"
    class="mb-6 mx-4 mt-4 border-error border"
    prominent
  >
    <div class="text-h6 font-weight-bold mb-2">⚠️ TINDAKAN DIPERLUKAN: Setup Sistem Belum Selesai!</div>
    <p class="text-body-2 mb-4">
      Sistem mendeteksi bahwa ada modul wajib yang masih <strong>kosong</strong>. Aplikasi (terutama POS Kasir & Gudang) akan mengalami <i>error</i> / <i>crash</i> jika dibiarkan. Harap segera atur modul berikut:
    </p>

    <div class="d-flex flex-column gap-3 mb-2">
      <div 
        v-for="(mod, index) in missingModules" 
        :key="index"
        class="d-flex align-center justify-space-between pa-3 rounded-lg"
        style="background: rgba(var(--v-theme-error), 0.1); border: 1px solid rgba(var(--v-theme-error), 0.3);"
      >
        <div>
          <strong class="text-error font-weight-bold d-block">{{ mod.module }}</strong>
          <span class="text-caption text-error">{{ mod.description }}</span>
        </div>
        <VBtn
          size="small"
          color="error"
          variant="elevated"
          @click="navigateTo(mod.link)"
          class="font-weight-bold"
        >
          Atur Sekarang
        </VBtn>
      </div>
    </div>
  </VAlert>
</template>
