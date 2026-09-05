<script setup>
import { ref, watch, computed } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useSnackbarStore } from '@/stores/snackbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  branches: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'close',
  'saved',
])

const snackbar = useSnackbarStore()
const isFormValid = ref(false)
const refForm = ref()
const isSaving = ref(false)
const isLoadingProducts = ref(false)

// Form Fields
const title = ref('')
const effectiveDate = ref(new Date().toISOString().substring(0, 10))
const selectedBranch = ref('all')
const reason = ref('Kenaikan Harga Resmi Supplier')
const notes = ref('')
const applyImmediately = ref(true)

// Bulk Calculator State
const selectedCategory = ref(null)
const bulkType = ref('percent') // 'percent', 'nominal', 'margin_from_cost'
const bulkValue = ref(5)

// Product Selection and Item Rows
const searchProduct = ref('')
const searchResults = ref([])
const items = ref([])

const reasonOptions = [
  'Kenaikan Harga Resmi Supplier / Pabrik',
  'Penurunan Harga / Promo Diskon Khusus Periode',
  'Cuci Gudang / Stok Mendekati Kedaluwarsa (Aging)',
  'Penyesuaian Persaingan Pasar & Kompetitor',
  'Penyesuaian Margin & Operasional Toko',
  'Penyesuaian Inflasi & Biaya Distribusi',
  'Penyesuaian Tarif PPN / Regulasi Pajak',
  'Penurunan HPP / Kebijakan Pabrik',
  'Lainnya (Tulis di Catatan)',
]

const formatRupiahNumber = val => {
  if (val === null || val === undefined || val === '') return ''
  const num = typeof val === 'number' ? val : Number(String(val).replace(/[^0-9.-]+/g, ''))
  if (isNaN(num)) return ''
  return new Intl.NumberFormat('id-ID').format(Math.round(num))
}

const parseRupiahInput = val => {
  if (!val) return 0
  const clean = String(val).replace(/[^0-9]/g, '')
  return clean ? Number(clean) : 0
}

const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0)
}

// Reset form
const resetForm = () => {
  title.value = `Penyesuaian Harga Periode ${new Date().toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}`
  effectiveDate.value = new Date().toISOString().substring(0, 10)
  selectedBranch.value = 'all'
  reason.value = 'Kenaikan Harga Resmi Supplier / Pabrik'
  notes.value = ''
  applyImmediately.value = true
  items.value = []
  searchProduct.value = ''
  searchResults.value = []
}

watch(() => props.isDrawerOpen, val => {
  if (val) {
    resetForm()
  }
})

// Search Products to Add
let searchDebounce = null
const onSearchProductInput = () => {
  clearTimeout(searchDebounce)
  if (!searchProduct.value || searchProduct.value.length < 2) {
    searchResults.value = []
    return
  }

  searchDebounce = setTimeout(async () => {
    isLoadingProducts.value = true
    try {
      const res = await $api('/apps/products', {
        query: {
          search: searchProduct.value,
          itemsPerPage: 10,
        },
      })
      const list = res.data || (Array.isArray(res) ? res : [])
      searchResults.value = list.filter(p => !items.value.some(i => i.product_id === p.id))
    } catch (e) {
      console.error(e)
    } finally {
      isLoadingProducts.value = false
    }
  }, 300)
}

const resolveProductPrice = product => {
  const pbs = product.product_branches || product.productBranches || []
  let price = Number(product.price || 0)
  let cost = Number(product.cost_price || product.unit_cost || 0)
  let minNego = Number(product.min_nego_price || 0)

  if (selectedBranch.value && selectedBranch.value !== 'all' && pbs.length > 0) {
    const matchedPb = pbs.find(pb => String(pb.branch_id) === String(selectedBranch.value))
    if (matchedPb) {
      if (Number(matchedPb.price) > 0) price = Number(matchedPb.price)
      if (Number(matchedPb.cost_price) > 0) cost = Number(matchedPb.cost_price)
      if (Number(matchedPb.min_nego_price) > 0) minNego = Number(matchedPb.min_nego_price)
    }
  }

  if (price === 0 && pbs.length > 0) {
    const validPb = pbs.find(pb => Number(pb.price) > 0) || pbs[0]
    if (validPb) {
      price = Number(validPb.price || 0)
      if (Number(validPb.cost_price) > 0) cost = Number(validPb.cost_price)
      if (Number(validPb.min_nego_price) > 0) minNego = Number(validPb.min_nego_price)
    }
  }

  if (minNego === 0 && price > 0) {
    minNego = Math.round(price * 0.95)
  }

  return { price, cost, minNego }
}

const addProductToItems = product => {
  const { price: defaultPrice, cost: defaultCost, minNego: defaultMinNego } = resolveProductPrice(product)

  items.value.push({
    product_id: product.id,
    sku: product.sku || product.code,
    name: product.name,
    category_name: product.category?.name || 'Umum',
    old_cost_price: defaultCost,
    new_cost_price: defaultCost,
    old_price: defaultPrice,
    new_price: defaultPrice,
    new_price_display: formatRupiahNumber(defaultPrice),
    old_min_nego_price: defaultMinNego,
    new_min_nego_price: defaultMinNego,
    new_min_nego_display: formatRupiahNumber(defaultMinNego),
    notes: '',
  })

  searchProduct.value = ''
  searchResults.value = []
}

// Load all products by category
const loadProductsByCategory = async () => {
  if (!selectedCategory.value) {
    snackbar.showSnackbar('Pilih kategori terlebih dahulu', 'warning')
    return
  }

  isLoadingProducts.value = true
  try {
    const res = await $api('/apps/products', {
      query: {
        category_id: selectedCategory.value,
        itemsPerPage: 100,
      },
    })
    const list = res.data || (Array.isArray(res) ? res : [])
    let addedCount = 0

    list.forEach(p => {
      if (!items.value.some(i => i.product_id === p.id)) {
        addProductToItems(p)
        addedCount++
      }
    })

    snackbar.showSnackbar(`Berhasil menambahkan ${addedCount} produk dari kategori terpilih`, 'success')
  } catch (e) {
    console.error(e)
    snackbar.showSnackbar('Gagal memuat produk kategori', 'error')
  } finally {
    isLoadingProducts.value = false
  }
}

const removeItem = index => {
  items.value.splice(index, 1)
}

// Handle price input changes
const onNewPriceInput = (item, event) => {
  const val = event.target.value
  const num = parseRupiahInput(val)
  item.new_price = num
  item.new_price_display = num ? formatRupiahNumber(num) : ''
  // Auto suggest min nego price as 95% of new price if not customized
  if (!item.is_nego_customized) {
    item.new_min_nego_price = Math.round(num * 0.95)
    item.new_min_nego_display = formatRupiahNumber(item.new_min_nego_price)
  }
}

const onNewMinNegoInput = (item, event) => {
  const val = event.target.value
  const num = parseRupiahInput(val)
  item.new_min_nego_price = num
  item.new_min_nego_display = num ? formatRupiahNumber(num) : ''
  item.is_nego_customized = true
}

// Apply Bulk Adjustment to all selected items
const applyBulkCalculation = () => {
  if (items.value.length === 0) {
    snackbar.showSnackbar('Belum ada produk yang dipilih dalam daftar.', 'warning')
    return
  }

  const val = Math.abs(Number(bulkValue.value) || 0)

  items.value.forEach(item => {
    let calculated = item.old_price

    if (bulkType.value === 'percent' || bulkType.value === 'percent_up') {
      // Naikkan +X% dari harga lama (dibulatkan ke atas kelipatan 500)
      calculated = Math.ceil((item.old_price * (1 + (val / 100))) / 500) * 500
    } else if (bulkType.value === 'percent_down') {
      // Turunkan -X% dari harga lama / Diskon (dibulatkan ke bawah kelipatan 500)
      calculated = Math.max(0, Math.floor((item.old_price * (1 - (val / 100))) / 500) * 500)
    } else if (bulkType.value === 'nominal' || bulkType.value === 'nominal_up') {
      // Naikkan +Rp X dari harga lama
      calculated = Math.max(0, item.old_price + val)
    } else if (bulkType.value === 'nominal_down') {
      // Turunkan -Rp X dari harga lama
      calculated = Math.max(0, item.old_price - val)
    } else if (bulkType.value === 'margin_from_cost') {
      // Margin +X% dari HPP
      const cost = item.new_cost_price || item.old_cost_price || 0
      calculated = Math.ceil((cost * (1 + (val / 100))) / 500) * 500
    }

    item.new_price = calculated
    item.new_price_display = formatRupiahNumber(calculated)
    item.new_min_nego_price = Math.round(calculated * 0.95)
    item.new_min_nego_display = formatRupiahNumber(item.new_min_nego_price)
  })

  snackbar.showSnackbar(`Kalkulasi massal berhasil diterapkan ke ${items.value.length} produk!`, 'success')
}

// Close Drawer
const handleClose = () => {
  emit('update:isDrawerOpen', false)
  emit('close')
}

// Submit Form
const onSubmit = async () => {
  const isValid = await refForm.value?.validate()
  if (!isValid?.valid) return

  if (items.value.length === 0) {
    snackbar.showSnackbar('Harap pilih minimal 1 produk untuk disesuaikan harganya.', 'error')
    return
  }

  // Validate price > 0
  const invalidItem = items.value.find(i => !i.new_price || i.new_price <= 0)
  if (invalidItem) {
    snackbar.showSnackbar(`Harga baru untuk produk "${invalidItem.name}" harus lebih dari Rp 0.`, 'error')
    return
  }

  isSaving.value = true
  try {
    const payload = {
      title: title.value,
      effective_date: effectiveDate.value,
      branch_id: selectedBranch.value !== 'all' ? selectedBranch.value : null,
      reason: reason.value,
      notes: notes.value,
      apply_immediately: applyImmediately.value,
      items: items.value.map(i => ({
        product_id: i.product_id,
        old_cost_price: i.old_cost_price,
        new_cost_price: i.new_cost_price,
        old_price: i.old_price,
        new_price: i.new_price,
        old_min_nego_price: i.old_min_nego_price,
        new_min_nego_price: i.new_min_nego_price,
        notes: i.notes,
      })),
    }

    const res = await $api('/apps/price-adjustments', {
      method: 'POST',
      body: payload,
    })

    if (res.success) {
      snackbar.showSnackbar(
        applyImmediately.value
          ? 'Penyesuaian harga berhasil disimpan dan disahkan ke kasir!'
          : 'Draft penyesuaian harga berhasil disimpan.',
        'success'
      )
      emit('saved')
      handleClose()
    }
  } catch (err) {
    console.error('Error creating price adjustment:', err)
    snackbar.showSnackbar(err?.response?._data?.message || 'Gagal menyimpan penyesuaian harga.', 'error')
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <VNavigationDrawer
    :model-value="props.isDrawerOpen"
    temporary
    location="end"
    :width="$vuetify.display.mdAndDown ? 850 : 1050"
    style="max-inline-size: 96vw;"
    @update:model-value="val => emit('update:isDrawerOpen', val)"
  >
    <!-- Header -->
    <div class="d-flex align-center justify-space-between pa-5 border-b bg-var-theme-surface">
      <div class="d-flex align-center gap-3">
        <VAvatar color="primary" variant="tonal" rounded="lg" size="44">
          <VIcon icon="ri-price-tag-3-line" size="26" />
        </VAvatar>
        <div>
          <h6 class="text-h6 font-weight-bold text-high-emphasis">
            Buat Penyesuaian Harga Periode
          </h6>
          <p class="text-caption text-medium-emphasis mb-0">
            Tetapkan harga jual resmi & batas nego baru untuk periode tertentu dengan rekam jejak audit.
          </p>
        </div>
      </div>

      <VBtn icon="ri-close-line" variant="text" color="default" @click="handleClose" />
    </div>

    <PerfectScrollbar :options="{ wheelPropagation: false }" style="max-block-size: calc(100vh - 140px);">
      <VCardText class="pa-5">
        <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
          <VRow>
            <!-- Judul & Tanggal -->
            <VCol cols="12" md="8">
              <VTextField
                v-model="title"
                label="Judul Dokumen / Periode Penyesuaian *"
                placeholder="Contoh: Penyesuaian Harga Periode Q4 2026 / Kenaikan Pabrik"
                density="compact"
                variant="outlined"
                :rules="[v => !!v || 'Judul wajib diisi']"
              />
            </VCol>

            <VCol cols="12" md="4">
              <VTextField
                v-model="effectiveDate"
                type="date"
                label="Tanggal Berlaku Efektif *"
                density="compact"
                variant="outlined"
                :rules="[v => !!v || 'Tanggal berlaku wajib diisi']"
              />
            </VCol>

            <!-- Cabang & Alasan -->
            <VCol cols="12" md="6">
              <VSelect
                v-model="selectedBranch"
                :items="[{ id: 'all', name: 'Semua Cabang Toko (Pusat & Cabang)' }, ...branches]"
                item-title="name"
                item-value="id"
                label="Target Cabang Toko *"
                density="compact"
                variant="outlined"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VCombobox
                v-model="reason"
                :items="reasonOptions"
                label="Alasan Perubahan Harga *"
                density="compact"
                variant="outlined"
                :rules="[v => !!v || 'Alasan wajib diisi']"
              />
            </VCol>

            <!-- Catatan Memo -->
            <VCol cols="12">
              <VTextField
                v-model="notes"
                label="Catatan Tambahan (Opsional)"
                placeholder="Nomor SK Direksi, surat edaran distributor, atau memo internal..."
                density="compact"
                variant="outlined"
              />
            </VCol>

            <!-- Section: Bulk Calculator -->
            <VCol cols="12">
              <VCard elevation="0" class="border rounded-lg pa-4 bg-var-theme-surface">
                <div class="d-flex align-center justify-space-between mb-3">
                  <div class="d-flex align-center gap-2">
                    <VIcon icon="ri-calculator-line" color="primary" size="20" />
                    <span class="font-weight-bold text-subtitle-2">Kalkulator Penyesuaian Cepat (Massal)</span>
                  </div>
                </div>

                <VRow align="center" dense>
                  <VCol cols="12" sm="4">
                    <VSelect
                      v-model="selectedCategory"
                      :items="categories"
                      item-title="name"
                      item-value="id"
                      label="Pilih Kategori Produk"
                      placeholder="Semua Kategori"
                      density="compact"
                      variant="outlined"
                      clearable
                      hide-details
                    />
                  </VCol>

                  <VCol cols="12" sm="3">
                    <VBtn
                      variant="tonal"
                      color="primary"
                      prepend-icon="ri-download-cloud-2-line"
                      block
                      :loading="isLoadingProducts"
                      @click="loadProductsByCategory"
                    >
                      Muat Kategori
                    </VBtn>
                  </VCol>

                  <VCol cols="12" sm="3">
                    <VSelect
                      v-model="bulkType"
                      :items="[
                        { title: 'Naikkan Persentase (+%)', value: 'percent_up' },
                        { title: 'Turunkan Persentase / Diskon (-%)', value: 'percent_down' },
                        { title: 'Naikkan Nominal (+Rp)', value: 'nominal_up' },
                        { title: 'Turunkan Nominal (-Rp)', value: 'nominal_down' },
                        { title: 'Target Markup Dari HPP (+%)', value: 'margin_from_cost' },
                      ]"
                      density="compact"
                      variant="outlined"
                      hide-details
                    />
                  </VCol>

                  <VCol cols="12" sm="2">
                    <div class="d-flex gap-1">
                      <VTextField
                        v-model.number="bulkValue"
                        type="number"
                        density="compact"
                        variant="outlined"
                        hide-details
                      />
                      <VBtn color="primary" @click="applyBulkCalculation">
                        Terapkan
                      </VBtn>
                    </div>
                  </VCol>
                </VRow>
              </VCard>
            </VCol>

            <!-- Search & Add Individual Products -->
            <VCol cols="12">
              <VTextField
                v-model="searchProduct"
                label="Cari & Tambah Produk Satuan (Ketik Nama / SKU Barcode)"
                prepend-inner-icon="ri-search-line"
                placeholder="Ketik minimal 2 karakter..."
                density="compact"
                variant="outlined"
                clearable
                :loading="isLoadingProducts"
                @input="onSearchProductInput"
              />

              <!-- Search Results Dropdown -->
              <VCard
                v-if="searchResults.length > 0"
                elevation="3"
                class="mt-1 border rounded-lg"
                style="max-block-size: 220px; overflow-y: auto;"
              >
                <VList density="compact">
                  <VListItem
                    v-for="prod in searchResults"
                    :key="prod.id"
                    link
                    @click="addProductToItems(prod)"
                  >
                    <template #prepend>
                      <VAvatar color="primary" variant="tonal" size="32" class="me-2">
                        <VIcon icon="ri-box-3-line" size="18" />
                      </VAvatar>
                    </template>
                    <VListItemTitle class="font-weight-bold text-body-2">
                      {{ prod.name }}
                    </VListItemTitle>
                    <VListItemSubtitle class="font-mono text-caption text-medium-emphasis">
                      {{ prod.sku }} • {{ prod.category?.name || 'Umum' }} • Harga Lama: {{ formatCurrency(prod.price) }}
                    </VListItemSubtitle>
                  </VListItem>
                </VList>
              </VCard>
            </VCol>

            <!-- Product Items Table -->
            <VCol cols="12">
              <div class="d-flex align-center justify-space-between mb-2">
                <span class="font-weight-bold text-subtitle-2">
                  Daftar Produk Disesuaikan ({{ items.length }} SKU)
                </span>
                <VBtn
                  v-if="items.length > 0"
                  variant="text"
                  color="error"
                  size="small"
                  prepend-icon="ri-delete-bin-line"
                  @click="items = []"
                >
                  Kosongkan Daftar
                </VBtn>
              </div>

              <div class="border rounded-lg overflow-hidden shadow-xs">
                <VTable density="comfortable" class="price-adjust-table text-no-wrap" hover>
                  <thead class="bg-var-theme-surface">
                    <tr>
                      <th class="text-caption font-weight-bold" style="inline-size: 40px;">NO</th>
                      <th class="text-caption font-weight-bold" style="min-inline-size: 200px;">PRODUK & SKU</th>
                      <th class="text-caption font-weight-bold text-end" style="min-inline-size: 110px;">HARGA LAMA</th>
                      <th class="text-caption font-weight-bold text-end" style="min-inline-size: 170px;">HARGA BARU (JUAL) *</th>
                      <th class="text-caption font-weight-bold text-end" style="min-inline-size: 110px;">SELISIH</th>
                      <th class="text-caption font-weight-bold text-end" style="min-inline-size: 160px;">MIN. NEGO</th>
                      <th class="text-caption font-weight-bold text-center" style="inline-size: 48px;">AKSI</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="items.length === 0">
                      <td colspan="7" class="text-center py-8 text-medium-emphasis">
                        <VIcon icon="ri-price-tag-3-line" size="32" class="mb-2 text-disabled d-block mx-auto" />
                        Belum ada produk yang dimasukkan ke dokumen penyesuaian harga ini.
                      </td>
                    </tr>
                    <tr v-for="(item, idx) in items" :key="item.product_id">
                      <td class="text-center font-mono text-caption text-medium-emphasis">{{ idx + 1 }}</td>
                      <td class="py-2">
                        <div class="font-weight-bold text-body-2 text-truncate" style="max-inline-size: 240px;" :title="item.name">
                          {{ item.name }}
                        </div>
                        <div class="text-caption font-mono text-medium-emphasis">
                          {{ item.sku }} • {{ item.category_name }}
                        </div>
                      </td>
                      <td class="text-end font-mono text-body-2 text-medium-emphasis">
                        {{ formatCurrency(item.old_price) }}
                      </td>
                      <td class="text-end py-2" style="inline-size: 170px;">
                        <VTextField
                          :model-value="item.new_price_display"
                          density="compact"
                          variant="outlined"
                          prefix="Rp"
                          class="font-mono font-weight-bold price-input"
                          style="min-inline-size: 155px;"
                          hide-details
                          @input="e => onNewPriceInput(item, e)"
                        />
                      </td>
                      <td class="text-end font-mono">
                        <VChip
                          size="x-small"
                          class="font-weight-bold"
                          :color="item.new_price > item.old_price ? 'success' : (item.new_price < item.old_price ? 'error' : 'secondary')"
                        >
                          {{ item.new_price > item.old_price ? '+' : '' }}{{ formatCurrency(item.new_price - item.old_price) }}
                        </VChip>
                      </td>
                      <td class="text-end py-2" style="inline-size: 160px;">
                        <VTextField
                          :model-value="item.new_min_nego_display"
                          density="compact"
                          variant="outlined"
                          prefix="Rp"
                          class="font-mono price-input"
                          style="min-inline-size: 145px;"
                          hide-details
                          @input="e => onNewMinNegoInput(item, e)"
                        />
                      </td>
                      <td class="text-center">
                        <VBtn
                          icon="ri-delete-bin-line"
                          size="small"
                          variant="text"
                          color="error"
                          title="Hapus produk"
                          @click="removeItem(idx)"
                        />
                      </td>
                    </tr>
                  </tbody>
                </VTable>
              </div>
            </VCol>

            <!-- Option Direct Approval -->
            <VCol cols="12" class="mt-2">
              <VCard elevation="0" class="border rounded-lg pa-3 bg-var-theme-surface">
                <VCheckbox
                  v-model="applyImmediately"
                  label="Langsung sahkan & terapkan harga baru ini serentak ke kasir POS"
                  density="compact"
                  hide-details
                >
                  <template #label>
                    <div>
                      <div class="text-body-2 font-weight-bold text-high-emphasis">
                        Langsung Sahkan & Terapkan Harga ke Kasir
                      </div>
                      <div class="text-caption text-medium-emphasis">
                        Jika dicentang, dokumen akan otomatis berstatus <strong>APPROVED</strong> dan harga di seluruh cabang/kasir langsung berubah. Jika tidak, akan disimpan sebagai <strong>DRAFT</strong> usulan.
                      </div>
                    </div>
                  </template>
                </VCheckbox>
              </VCard>
            </VCol>
          </VRow>

          <!-- Footer Actions -->
          <div class="d-flex align-center justify-end gap-3 mt-6 pt-4 border-t">
            <VBtn variant="tonal" color="secondary" @click="handleClose">
              Batal
            </VBtn>

            <VBtn type="submit" color="primary" :loading="isSaving">
              {{ applyImmediately ? 'Simpan & Terapkan Harga' : 'Simpan Draft Dokumen' }}
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>

<style scoped>
.price-adjust-table :deep(.v-field__input) {
  padding-inline-start: 4px;
  font-family: monospace;
}
.price-adjust-table :deep(.v-field__prefix) {
  font-size: 12px;
  opacity: 0.7;
  font-family: monospace;
  padding-inline-end: 2px;
}
</style>
