<script setup>
import { ref, watch, nextTick } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedData: {
    type: Object,
    default: null,
  },
  masterProducts: {
    type: Array,
    default: () => [],
  },
  branchesList: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'update:is-drawer-open',
  'close',
  'cancel',
  'saveData',
])

const isFormValid = ref(false)
const refForm = ref()
const id = ref(null)
const product_id = ref(null)
const branch_id = ref(null)
const cost_price = ref(0)
const price = ref(0)
const min_nego_price = ref(0)
const tax_percentage = ref(0)
const other_fees = ref(0)

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  emit('update:is-drawer-open', false)
  emit('close')
  emit('cancel')
  nextTick(() => {
    refForm.value?.resetValidation()
  })
}

watch(() => props.selectedData, newVal => {
  if (newVal) {
    id.value = newVal.id
    product_id.value = newVal.product_id
    branch_id.value = newVal.branch_id
    cost_price.value = Math.round(newVal.cost_price || 0)
    price.value = Math.round(newVal.price || 0)
    min_nego_price.value = Math.round(newVal.min_nego_price || 0)
    tax_percentage.value = newVal.tax_percentage || 0
    other_fees.value = Math.round(newVal.other_fees || 0)
  } else {
    id.value = null
    product_id.value = null
    branch_id.value = null
    cost_price.value = 0
    price.value = 0
    min_nego_price.value = 0
    tax_percentage.value = 0
    other_fees.value = 0
  }
}, { immediate: true })

const formatInputRupiah = value => {
  if (value === null || value === undefined || value === '') return ''
  const digits = String(value).replace(/\D/g, '')
  if (!digits) return ''
  
  return new Intl.NumberFormat('id-ID').format(digits)
}

const parseInputRupiah = value => {
  if (value === null || value === undefined || value === '') return 0
  const digits = String(value).replace(/\D/g, '')
  
  return Number(digits) || 0
}

const applyMarkup = percent => {
  const c = Number(cost_price.value) || 0
  if (c > 0) {
    price.value = Math.round(c * (1 + percent / 100))
  }
}

const applyNegoMargin = percent => {
  const c = Number(cost_price.value) || 0
  if (c > 0) {
    min_nego_price.value = Math.round(c * (1 + percent / 100))
  }
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('saveData', {
        id: id.value,
        product_id: product_id.value,
        branch_id: branch_id.value,
        cost_price: cost_price.value,
        price: price.value,
        min_nego_price: min_nego_price.value,
        tax_percentage: tax_percentage.value,
        other_fees: other_fees.value,
      })
      closeNavigationDrawer()
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
  emit('update:is-drawer-open', val)
  if (!val) {
    emit('close')
    emit('cancel')
  }
}
const calculatedProfit = computed(() => {
  const c = Number(cost_price.value) || 0
  const p = Number(price.value) || 0
  return p - c
})

const calculatedMargin = computed(() => {
  const c = Number(cost_price.value) || 0
  const p = Number(price.value) || 0
  if (c <= 0) return 0
  return Math.round(((p - c) / c) * 100 * 10) / 10
})

const calculatedNegoMargin = computed(() => {
  const c = Number(cost_price.value) || 0
  const n = Number(min_nego_price.value) || 0
  if (c <= 0 || n <= 0) return 0
  return Math.round(((n - c) / c) * 100 * 10) / 10
})

const formatCurrency = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value || 0)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '92vw' : 520)"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- Header -->
    <div class="d-flex align-center justify-space-between px-6 py-5 border-b bg-gradient-header">
      <div class="d-flex align-center gap-3">
        <VAvatar
          size="42"
          color="primary"
          variant="tonal"
          class="rounded-lg"
        >
          <VIcon
            :icon="props.selectedData ? 'ri-price-tag-3-line' : 'ri-store-2-line'"
            size="24"
          />
        </VAvatar>
        <div>
          <h5 class="text-h6 font-weight-bold mb-0">
            {{ props.selectedData ? 'Edit Harga Cabang' : 'Daftarkan Produk ke Cabang' }}
          </h5>
          <span class="text-caption text-medium-emphasis">
            Konfigurasi HPP, margin retail & batas kasir POS
          </span>
        </div>
      </div>
      <VBtn
        icon="ri-close-line"
        variant="tonal"
        color="secondary"
        size="small"
        type="button"
        @click.stop="closeNavigationDrawer"
      />
    </div>

    <PerfectScrollbar :options="{ wheelPropagation: false }" style="height: calc(100vh - 75px);">
      <VCard flat class="pa-6">
        <!-- Form -->
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
        >
          <!-- Section 1: Produk & Cabang -->
          <div class="mb-5">
            <div class="d-flex align-center gap-2 mb-3">
              <VIcon icon="ri-information-line" color="primary" size="18" />
              <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-primary">
                1. Penempatan Produk & Cabang
              </span>
            </div>

            <VRow dense>
              <VCol cols="12">
                <VAutocomplete
                  v-model="product_id"
                  :rules="[v => !!v || 'Master produk wajib dipilih']"
                  :items="props.masterProducts"
                  item-title="name"
                  item-value="id"
                  label="Pilih Master Produk"
                  placeholder="Ketik nama atau cari SKU"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-box-3-line"
                  :disabled="!!props.selectedData"
                />
              </VCol>

              <VCol cols="12">
                <VAutocomplete
                  v-model="branch_id"
                  :rules="[v => !!v || 'Cabang wajib dipilih']"
                  :items="props.branchesList"
                  item-title="name"
                  item-value="id"
                  label="Pilih Cabang Penempatan"
                  placeholder="Pilih cabang outlet"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-store-line"
                  :disabled="!!props.selectedData"
                />
              </VCol>
            </VRow>
          </div>

          <VDivider class="my-5" />

          <!-- Section 2: Struktur Harga & Margin -->
          <div class="mb-5">
            <div class="d-flex align-center gap-2 mb-3">
              <VIcon icon="ri-calculator-line" color="success" size="18" />
              <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-success">
                2. Struktur Harga & Margin Laba
              </span>
            </div>

            <VRow dense>
              <!-- HPP Real -->
              <VCol cols="12">
                <VTextField
                  :model-value="formatInputRupiah(cost_price)"
                  :rules="[v => !!v || 'Harga modal (HPP) wajib diisi']"
                  label="Harga Modal (HPP Real) (Rp)"
                  type="text"
                  placeholder="0"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-archive-line"
                  prefix="Rp"
                  hint="Modal bersih unit (sudah mencakup diskon PO supplier & PPN masukan)"
                  persistent-hint
                  @update:model-value="val => cost_price = parseInputRupiah(val)"
                />
              </VCol>

              <!-- Harga Jual POS -->
              <VCol cols="12" class="mt-2">
                <VTextField
                  :model-value="formatInputRupiah(price)"
                  :rules="[v => !!v || 'Harga jual wajib diisi']"
                  label="Harga Jual Normal (Pricelist POS) (Rp)"
                  type="text"
                  placeholder="0"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-shopping-bag-3-line"
                  prefix="Rp"
                  @update:model-value="val => price = parseInputRupiah(val)"
                />
                <!-- Quick Markup Chips -->
                <div class="d-flex align-center gap-1 mt-2 flex-wrap">
                  <span class="text-caption text-medium-emphasis me-1" style="font-size: 11px;">Markup Cepat:</span>
                  <VChip size="x-small" color="primary" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyMarkup(20)">+20%</VChip>
                  <VChip size="x-small" color="primary" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyMarkup(25)">+25%</VChip>
                  <VChip size="x-small" color="primary" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyMarkup(30)">+30%</VChip>
                  <VChip size="x-small" color="primary" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyMarkup(35)">+35%</VChip>
                  <VChip size="x-small" color="primary" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyMarkup(40)">+40%</VChip>
                </div>
              </VCol>

              <!-- Live ERP Profit Insight Card -->
              <VCol cols="12" v-if="cost_price > 0 && price > 0" class="my-2">
                <div class="pa-3 rounded-xl border" :class="calculatedProfit >= 0 ? 'bg-success-lighten-5 border-success-subtle' : 'bg-error-lighten-5 border-error-subtle'">
                  <div class="d-flex align-center justify-space-between">
                    <div>
                      <div class="text-caption text-medium-emphasis" style="font-size: 11px;">Estimasi Keuntungan Retail:</div>
                      <div class="text-subtitle-1 font-weight-bold" :class="calculatedProfit >= 0 ? 'text-success' : 'text-error'">
                        {{ calculatedProfit >= 0 ? '+' : '' }}{{ formatCurrency(calculatedProfit) }}
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="text-caption text-medium-emphasis" style="font-size: 11px;">Margin Untung:</div>
                      <VChip size="small" :color="calculatedMargin >= 20 ? 'success' : (calculatedMargin > 0 ? 'warning' : 'error')" class="font-weight-bold">
                        {{ calculatedMargin >= 0 ? '+' : '' }}{{ calculatedMargin }}%
                      </VChip>
                    </div>
                  </div>
                </div>
              </VCol>

              <!-- Harga Nego Minimum -->
              <VCol cols="12" class="mt-2">
                <VTextField
                  :model-value="formatInputRupiah(min_nego_price)"
                  label="Harga Nego Minimum (Batas Kasir) (Rp)"
                  type="text"
                  placeholder="0"
                  density="comfortable"
                  variant="outlined"
                  prepend-inner-icon="ri-hand-coin-line"
                  prefix="Rp"
                  persistent-hint
                  hint="Batas terendah saat tawar-menawar. Di bawah harga ini kasir wajib PIN Supervisor."
                  @update:model-value="val => min_nego_price = parseInputRupiah(val)"
                />
                <!-- Quick Nego Chips -->
                <div class="d-flex align-center gap-1 mt-2 flex-wrap">
                  <span class="text-caption text-medium-emphasis me-1" style="font-size: 11px;">Min. Margin:</span>
                  <VChip size="x-small" color="warning" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyNegoMargin(10)">+10% Modal</VChip>
                  <VChip size="x-small" color="warning" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyNegoMargin(15)">+15% Modal</VChip>
                  <VChip size="x-small" color="warning" variant="tonal" class="cursor-pointer font-weight-medium" @click="applyNegoMargin(20)">+20% Modal</VChip>
                </div>
              </VCol>
            </VRow>
          </div>

          <VDivider class="my-5" />

          <!-- Section 3: Pajak & Biaya POS -->
          <div class="mb-6">
            <div class="d-flex align-center gap-2 mb-3">
              <VIcon icon="ri-percent-line" color="info" size="18" />
              <span class="text-subtitle-2 font-weight-bold text-uppercase letter-spacing-1 text-info">
                3. Pajak & Biaya Struk POS
              </span>
            </div>

            <VRow dense>
              <VCol cols="12" sm="6">
                <VTextField
                  v-model="tax_percentage"
                  label="PPN Kasir (%)"
                  type="number"
                  placeholder="0"
                  density="comfortable"
                  variant="outlined"
                  suffix="%"
                  hint="PPN keluaran di struk POS"
                  persistent-hint
                />
              </VCol>

              <VCol cols="12" sm="6">
                <VTextField
                  :model-value="formatInputRupiah(other_fees)"
                  label="Biaya Tambahan (Rp)"
                  type="text"
                  placeholder="0"
                  density="comfortable"
                  variant="outlined"
                  prefix="Rp"
                  hint="Biaya admin / pasang di struk"
                  persistent-hint
                  @update:model-value="val => other_fees = parseInputRupiah(val)"
                />
              </VCol>
            </VRow>
          </div>

          <!-- Sticky Action Bar -->
          <div class="d-flex align-center gap-3 pt-2">
            <VBtn
              type="submit"
              color="primary"
              size="large"
              prepend-icon="ri-save-3-line"
              class="font-weight-bold flex-grow-1 rounded-lg shadow-sm"
            >
              {{ props.selectedData ? 'Simpan Perubahan Harga' : 'Daftarkan Produk' }}
            </VBtn>
            <VBtn
              type="button"
              variant="outlined"
              color="secondary"
              size="large"
              class="rounded-lg px-5"
              @click.stop="closeNavigationDrawer"
            >
              Batal
            </VBtn>
          </div>
        </VForm>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>

<style scoped>
.bg-gradient-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.05) 0%, rgba(var(--v-theme-surface), 1) 100%);
}
.letter-spacing-1 {
  letter-spacing: 0.5px;
}
.bg-success-lighten-5 {
  background-color: rgba(var(--v-theme-success), 0.08) !important;
}
.border-success-subtle {
  border-color: rgba(var(--v-theme-success), 0.3) !important;
}
.bg-error-lighten-5 {
  background-color: rgba(var(--v-theme-error), 0.08) !important;
}
.border-error-subtle {
  border-color: rgba(var(--v-theme-error), 0.3) !important;
}
</style>
