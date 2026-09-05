<script setup>
import { ref, onMounted, watch } from 'vue'
import { paginationMeta } from '@/utils/paginationMeta'
import { useSnackbarStore } from '@/stores/snackbar'
import ReceivableDetailDrawer from './ReceivableDetailDrawer.vue'

const receivables = ref([])
const isLoading = ref(false)
const totalItems = ref(0)
const page = ref(1)
const itemsPerPage = ref(10)

const selectedStatus = ref('')
const selectedBranch = ref(null)
const branches = ref([])
const searchQuery = ref('')
let searchTimeout = null

const summary = ref({
  total_due: 0,
  total_paid: 0,
  total_remaining: 0,
  total_overdue: 0,
  count_unpaid: 0,
  count_paid: 0,
})

const isDetailDrawerVisible = ref(false)
const selectedReceivableId = ref(null)

const isConfirmDeleteVisible = ref(false)
const receivableToDelete = ref(null)
const voidPin = ref('')

const snackbar = useSnackbarStore()

const formatCurrency = value => {
  if (!value || isNaN(value)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

const formatDate = dateString => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const fetchBranches = async () => {
  try {
    const res = await $api('/apps/branches')
    branches.value = res.data || res || []
  } catch (error) {
    console.error('Failed to fetch branches:', error)
  }
}

const fetchReceivables = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
    }

    if (selectedStatus.value) params.status = selectedStatus.value
    if (selectedBranch.value) params.branch_id = selectedBranch.value
    if (searchQuery.value) params.search = searchQuery.value

    const res = await $api('/apps/receivables', { query: params })

    receivables.value = res.data || res || []
    if (res.total !== undefined) {
      totalItems.value = res.total
    }
    if (res.summary) {
      summary.value = res.summary
    }
  } catch (error) {
    console.error('Failed to fetch receivables:', error)
    snackbar.show('Gagal mengambil data piutang', 'error')
  } finally {
    isLoading.value = false
  }
}

watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchReceivables()
  }, 450)
})

watch([selectedStatus, selectedBranch], () => {
  page.value = 1
  fetchReceivables()
})

onMounted(async () => {
  await fetchBranches()
  fetchReceivables()
})

const tableHeaders = [
  { title: 'NO. NOTA', key: 'sale.invoice_number' },
  { title: 'CABANG', key: 'sale.branch.name' },
  { title: 'PELANGGAN', key: 'customer.name' },
  { title: 'TOTAL TAGIHAN', key: 'amount_due', align: 'end' },
  { title: 'SUDAH BAYAR', key: 'amount_paid', align: 'end' },
  { title: 'SISA PIUTANG', key: 'remaining', sortable: false, align: 'end' },
  { title: 'JATUH TEMPO', key: 'due_date' },
  { title: 'STATUS', key: 'status', align: 'center' },
  { title: 'AKSI', key: 'actions', sortable: false, align: 'center' },
]

const isOverdue = item => {
  if (!item || item.status === 'paid' || !item.due_date) return false
  const today = new Date().toISOString().split('T')[0]
  return item.due_date < today
}

const getStatusBadge = item => {
  if (item.status === 'paid') {
    return { text: 'LUNAS', color: 'success', icon: 'ri-check-double-line' }
  }
  if (isOverdue(item)) {
    return { text: 'JATUH TEMPO', color: 'error', icon: 'ri-alarm-warning-line' }
  }
  if (item.status === 'partial') {
    return { text: 'SEBAGIAN', color: 'warning', icon: 'ri-time-line' }
  }
  return { text: 'BELUM LUNAS', color: 'error', icon: 'ri-close-circle-line' }
}

const openDetail = receivable => {
  selectedReceivableId.value = receivable.id
  isDetailDrawerVisible.value = true
}

const handlePaymentSaved = () => {
  fetchReceivables()
}

const confirmDelete = receivable => {
  receivableToDelete.value = receivable
  voidPin.value = ''
  isConfirmDeleteVisible.value = true
}

const executeDelete = async () => {
  if (!receivableToDelete.value) return
  if (!voidPin.value) {
    snackbar.show('Silakan masukkan PIN Supervisor/Kepala Cabang', 'error')
    return
  }

  isLoading.value = true
  try {
    const res = await $api(`/apps/receivables/${receivableToDelete.value.id}`, {
      method: 'DELETE',
      body: { pin: voidPin.value },
    })

    snackbar.show(res.message || 'Transaksi piutang berhasil dibatalkan dan stok dikembalikan.', 'success')
    isConfirmDeleteVisible.value = false
    receivableToDelete.value = null
    fetchReceivables()
  } catch (error) {
    console.error(error)
    const err = error.response?._data?.message || 'Gagal membatalkan transaksi piutang.'
    snackbar.show(err, 'error')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="pa-4">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h4 font-weight-bold mb-1">
          Buku Piutang Usaha (Receivables)
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Kelola penagihan penjualan tempo/kredit, cicilan pembayaran pelanggan, dan pemantauan jatuh tempo.
        </p>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <VRow class="mb-4">
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">TOTAL PIUTANG AKTIF</div>
              <div class="text-h5 font-weight-bold text-primary mt-1">{{ formatCurrency(summary.total_due) }}</div>
            </div>
            <VAvatar color="primary" variant="tonal" size="42">
              <VIcon icon="ri-file-list-3-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">{{ summary.count_unpaid }} Tagihan Belum Lunas</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">SISA BELUM DIBAYAR</div>
              <div class="text-h5 font-weight-bold text-warning mt-1">{{ formatCurrency(summary.total_remaining) }}</div>
            </div>
            <VAvatar color="warning" variant="tonal" size="42">
              <VIcon icon="ri-hand-coin-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">Menunggu Pelunasan</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-success">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-medium-emphasis font-weight-medium">TOTAL SUDAH TERTAGIH</div>
              <div class="text-h5 font-weight-bold text-success mt-1">{{ formatCurrency(summary.total_paid) }}</div>
            </div>
            <VAvatar color="success" variant="tonal" size="42">
              <VIcon icon="ri-money-dollar-circle-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-medium-emphasis mt-2">{{ summary.count_paid }} Tagihan Selesai / Lunas</div>
        </VCard>
      </VCol>

      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-error">
          <div class="d-flex align-center justify-space-between">
            <div>
              <div class="text-caption text-error font-weight-bold">PIUTANG JATUH TEMPO</div>
              <div class="text-h5 font-weight-bold text-error mt-1">{{ formatCurrency(summary.total_overdue) }}</div>
            </div>
            <VAvatar color="error" variant="tonal" size="42">
              <VIcon icon="ri-alarm-warning-line" size="22" />
            </VAvatar>
          </div>
          <div class="text-caption text-error font-weight-medium mt-2">Perlu Follow-up Penagihan Segera</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Filter & Table Card -->
    <VCard elevation="2">
      <!-- Filter Bar -->
      <VCardText class="d-flex flex-wrap align-center py-4 gap-4">
        <VTextField
          v-model="searchQuery"
          placeholder="Cari No Nota / Pelanggan / HP..."
          prepend-inner-icon="ri-search-line"
          density="compact"
          hide-details
          style="max-width: 280px;"
          clearable
        />

        <VSelect
          v-model="selectedStatus"
          :items="[
            { title: 'Semua Status', value: '' },
            { title: 'Belum Lunas (Unpaid)', value: 'unpaid' },
            { title: 'Lunas Sebagian (Partial)', value: 'partial' },
            { title: 'Lunas (Paid)', value: 'paid' },
          ]"
          item-title="title"
          item-value="value"
          label="Status Pembayaran"
          density="compact"
          style="max-width: 200px"
          hide-details
        />

        <VAutocomplete
          v-model="selectedBranch"
          :items="branches"
          item-title="name"
          item-value="id"
          placeholder="Semua Cabang"
          label="Cabang"
          density="compact"
          style="max-width: 200px"
          hide-details
          clearable
        />

        <VSpacer />

        <VBtn
          variant="tonal"
          color="secondary"
          size="small"
          prepend-icon="ri-refresh-line"
          :loading="isLoading"
          @click="fetchReceivables"
        >
          Muat Ulang
        </VBtn>
      </VCardText>

      <VDivider />

      <!-- Data Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="tableHeaders"
        :items="receivables"
        :items-length="totalItems"
        :loading="isLoading"
        class="text-no-wrap"
        hover
        @update:options="fetchReceivables"
      >
        <!-- Invoice Number -->
        <template #item.sale.invoice_number="{ item }">
          <a
            href="#"
            class="font-weight-bold text-primary text-decoration-none"
            @click.prevent="openDetail(item)"
          >
            {{ item.sale?.invoice_number || '-' }}
          </a>
          <div class="text-caption text-disabled">{{ item.sale?.date }}</div>
        </template>

        <!-- Branch -->
        <template #item.sale.branch.name="{ item }">
          <span>{{ item.sale?.branch?.name || '-' }}</span>
        </template>

        <!-- Customer -->
        <template #item.customer.name="{ item }">
          <div class="font-weight-medium">{{ item.customer?.name || '-' }}</div>
          <div class="text-caption text-disabled">{{ item.customer?.phone || '-' }}</div>
        </template>

        <!-- Total Tagihan -->
        <template #item.amount_due="{ item }">
          <span class="font-weight-medium">{{ formatCurrency(item.amount_due) }}</span>
        </template>

        <!-- Sudah Bayar -->
        <template #item.amount_paid="{ item }">
          <span class="text-success font-weight-medium">{{ formatCurrency(item.amount_paid) }}</span>
        </template>

        <!-- Sisa Piutang -->
        <template #item.remaining="{ item }">
          <span :class="item.status === 'paid' ? 'text-disabled' : 'text-error font-weight-bold'">
            {{ formatCurrency(Number(item.amount_due) - Number(item.amount_paid)) }}
          </span>
        </template>

        <!-- Due Date -->
        <template #item.due_date="{ item }">
          <div :class="isOverdue(item) ? 'text-error font-weight-bold' : ''">
            {{ formatDate(item.due_date) }}
            <VChip
              v-if="isOverdue(item)"
              color="error"
              size="x-small"
              class="ms-1"
              variant="flat"
            >
              Lewat
            </VChip>
          </div>
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <VChip
            :color="getStatusBadge(item).color"
            size="small"
            variant="elevated"
            class="font-weight-medium"
          >
            <VIcon
              :icon="getStatusBadge(item).icon"
              size="14"
              class="me-1"
            />
            {{ getStatusBadge(item).text }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-center align-center">
            <VBtn
              size="small"
              color="primary"
              variant="elevated"
              prepend-icon="ri-hand-coin-line"
              @click="openDetail(item)"
            >
              Bayar / Detail
            </VBtn>

            <VBtn
              v-if="item.status !== 'paid'"
              icon
              size="small"
              color="error"
              variant="text"
              @click="confirmDelete(item)"
            >
              <VIcon icon="ri-delete-bin-line" />
              <VTooltip activator="parent" location="top">Batalkan Transaksi Piutang</VTooltip>
            </VBtn>
          </div>
        </template>

        <template #no-data>
          <div class="pa-4 text-center text-medium-emphasis">
            Tidak ada catatan piutang usaha yang cocok dengan filter.
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
                :items="[10, 20, 25, 50, 100]"
                hide-details
              />
            </div>

            <p class="d-flex align-center text-body-2 text-high-emphasis me-2 mb-0">
              {{ paginationMeta({ page, itemsPerPage }, totalItems) }}
            </p>

            <div class="d-flex gap-x-2 align-center me-2">
              <VBtn
                class="flip-in-rtl"
                icon="ri-arrow-left-s-line"
                variant="text"
                density="comfortable"
                color="high-emphasis"
                :disabled="page <= 1"
                @click="page <= 1 ? page = 1 : page--"
              />

              <VBtn
                class="flip-in-rtl"
                icon="ri-arrow-right-s-line"
                density="comfortable"
                variant="text"
                color="high-emphasis"
                :disabled="page >= Math.ceil(totalItems / itemsPerPage)"
                @click="page >= Math.ceil(totalItems / itemsPerPage) ? page = Math.ceil(totalItems / itemsPerPage) : page++"
              />
            </div>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Detail & Payment Drawer -->
    <ReceivableDetailDrawer
      v-model:is-drawer-open="isDetailDrawerVisible"
      :receivable-id="selectedReceivableId"
      @payment-saved="handlePaymentSaved"
    />

    <!-- Void / Cancel Confirmation Dialog -->
    <VDialog
      v-model="isConfirmDeleteVisible"
      max-width="450"
    >
      <VCard>
        <VCardTitle class="px-6 pt-6 pb-2 text-error d-flex align-center gap-2">
          <VIcon icon="ri-alert-line" />
          <span>Batalkan Transaksi Piutang</span>
        </VCardTitle>
        <VCardText class="px-6 py-2">
          <p class="text-body-2 mb-3">
            Membatalkan piutang untuk nota <strong>{{ receivableToDelete?.sale?.invoice_number }}</strong> akan menghapus seluruh data cicilan dan <strong>mengembalikan stok produk ke sistem</strong>.
          </p>
          <VTextField
            v-model="voidPin"
            type="password"
            label="PIN Otorisasi Supervisor / Kasir"
            placeholder="Masukkan 6 digit PIN"
            density="compact"
            autofocus
          />
        </VCardText>
        <VCardActions class="px-6 pb-4 justify-end gap-2">
          <VBtn
            variant="outlined"
            color="secondary"
            @click="isConfirmDeleteVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="error"
            variant="elevated"
            :loading="isLoading"
            @click="executeDelete"
          >
            Konfirmasi Batalkan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style lang="scss">
.per-page-select {
  inline-size: 5.5rem;
}
</style>

<route lang="yaml">
meta:
  action: read
  subject: Data Piutang
</route>
