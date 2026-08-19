<script setup>
import { computed } from 'vue'
import illustrationJohnDark from '@images/cards/illustration-john-dark.png'
import illustrationJohnLight from '@images/cards/illustration-john-light.png'
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'

const props = defineProps({
  dailyIncome: {
    type: Number,
    default: 0,
  },
  monthlyIncome: {
    type: Number,
    default: 0,
  },
})

const johnImage = useGenerateImageVariant(illustrationJohnLight, illustrationJohnDark)

const cookieUserData = useCookie('userData')
const localUserData = typeof localStorage !== 'undefined' ? JSON.parse(localStorage.getItem('userData') || '{}') : {}
const user = computed(() => cookieUserData.value || localUserData || {})

const userName = computed(() => user.value.fullName || user.value.name || user.value.username || 'Administrator')
const userRole = computed(() => {
  if (user.value.role) {
    return Array.isArray(user.value.role) ? user.value.role[0] : user.value.role
  }
  if (user.value.roles && user.value.roles.length > 0) {
    const r = user.value.roles[0]
    return typeof r === 'string' ? r : (r.name || 'Super Admin')
  }
  return 'Super Admin'
})

const formatCurrency = value => {
  if (value === null || value === undefined || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value)
}
</script>

<template>
  <VCard class="overflow-visible h-100">
    <VRow no-gutters class="h-100 align-center">
      <VCol
        cols="12"
        sm="8"
        order="2"
        order-sm="1"
        class="pa-6"
      >
        <div class="d-flex align-center gap-2 mb-2">
          <VChip
            color="primary"
            size="small"
            variant="tonal"
            class="font-weight-bold text-caption"
          >
            {{ userRole }}
          </VChip>
          <span class="text-caption text-medium-emphasis">
            {{ new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
          </span>
        </div>

        <h3 class="text-h4 font-weight-bold mb-2">
          Selamat Datang, <strong>{{ userName }}</strong>! 👋
        </h3>

        <p class="text-body-2 text-medium-emphasis mb-4">
          Total pendapatan kasir tercatat hari ini sebesar 
          <strong class="text-primary font-weight-bold">{{ formatCurrency(dailyIncome) }}</strong>. 
          Sistem siap melayani transaksi dan memantau pergerakan stok.
        </p>

        <div class="d-flex flex-wrap gap-3">
          <VBtn
            color="primary"
            prepend-icon="ri-shopping-cart-2-line"
            to="/pos"
          >
            Buka Kasir POS
          </VBtn>

          <VBtn
            color="secondary"
            variant="tonal"
            prepend-icon="ri-line-chart-line"
            to="/dashboards/penjualan"
          >
            Analisis Penjualan
          </VBtn>
        </div>
      </VCol>

      <VCol
        cols="12"
        sm="4"
        order="1"
        order-sm="2"
        class="text-center position-relative d-none d-sm-flex justify-center align-end h-100"
      >
        <img
          :src="johnImage"
          class="john-illustration"
          :height="$vuetify.display.xs ? '140' : '175'"
          alt="welcome-illustration"
        >
      </VCol>
    </VRow>
  </VCard>
</template>

<style lang="scss" scoped>
.john-illustration {
  inset-block-end: 0;
  inset-inline-end: 1rem;
  position: absolute;
}
</style>
