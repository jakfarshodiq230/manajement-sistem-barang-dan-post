<script setup>
import { nextTick } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  sale: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:isDrawerOpen', 'void-sale'])

// Format currency
const formatRupiah = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '90vw' : 500)"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      title="Detail Transaksi"
      @cancel="closeNavigationDrawer"
    />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <div class="d-flex justify-space-between align-center mb-6">
            <div>
              <h6 class="text-h6 font-weight-bold">
                {{ props.sale.invoice_number }}
              </h6>
              <div class="text-caption text-disabled">
                {{ props.sale.date }}
              </div>
            </div>
            <VChip
              :color="props.sale.status === 'completed' ? 'success' : (props.sale.status === 'returned' ? 'warning' : 'error')"
              size="small"
            >
              {{ props.sale.status.toUpperCase() }}
            </VChip>
          </div>

          <div class="bg-var-theme-background pa-4 rounded mb-6 text-sm">
            <VRow no-gutters>
              <VCol
                cols="4"
                class="text-disabled mb-1"
              >
                Kasir:
              </VCol>
              <VCol
                cols="8"
                class="font-weight-medium mb-1"
              >
                {{ props.sale.user?.name }}
              </VCol>
              
              <VCol
                cols="4"
                class="text-disabled mb-1"
              >
                Cabang:
              </VCol>
              <VCol
                cols="8"
                class="font-weight-medium mb-1"
              >
                {{ props.sale.branch?.name }}
              </VCol>

              <VCol
                v-if="props.sale.approved_by"
                cols="4"
                class="text-disabled"
              >
                Diotorisasi Oleh:
              </VCol>
              <VCol
                v-if="props.sale.approved_by"
                cols="8"
                class="font-weight-medium text-error"
              >
                {{ props.sale.approver?.name }}
              </VCol>
            </VRow>
          </div>

          <h6 class="text-subtitle-1 font-weight-bold mb-4">
            Barang Dibeli
          </h6>
          
          <VList
            lines="two"
            class="mb-4"
          >
            <template
              v-for="(item, index) in props.sale.items"
              :key="index"
            >
              <VListItem class="px-0">
                <div class="d-flex justify-space-between w-100 mb-1">
                  <div class="font-weight-medium text-truncate pe-2">
                    {{ item.product_branch?.product?.name }}
                  </div>
                  <div class="font-weight-bold">
                    {{ formatRupiah(item.subtotal) }}
                  </div>
                </div>
                <div class="text-caption text-disabled d-flex justify-space-between mb-1">
                  <span>{{ item.qty }} x {{ formatRupiah(item.price) }}</span>
                  <span
                    v-if="Number(item.price) < Number(item.cost_price)"
                    class="text-error"
                  >
                    <VIcon
                      icon="ri-error-warning-line"
                      size="x-small"
                    /> Harga Nego
                  </span>
                </div>
                
                <!-- Detail Harga Modal, Asli, Nego, Pajak -->
                <div class="bg-primary-lighten-5 pa-2 rounded text-caption">
                  <VRow no-gutters>
                    <VCol cols="6">
                      Hrg Modal: {{ formatRupiah(item.cost_price) }}
                    </VCol>
                    <VCol cols="6">
                      Hrg Jual (Asli): {{ formatRupiah(item.original_price) }}
                    </VCol>
                    <VCol cols="6">
                      Hrg Jual (Nego): <span :class="{'text-error font-weight-bold': Number(item.price) < Number(item.cost_price)}">{{ formatRupiah(item.price) }}</span>
                    </VCol>
                    <VCol cols="6">
                      Pajak ({{ Number(item.tax_percentage) }}%): {{ formatRupiah(item.tax_amount) }}
                    </VCol>
                  </VRow>
                </div>
              </VListItem>
              <VDivider
                v-if="index < props.sale.items.length - 1"
                class="my-2"
              />
            </template>
          </VList>

          <VDivider class="mb-4" />

          <!-- Metode Pembayaran -->
          <h6 class="text-subtitle-1 font-weight-bold mb-4">
            Metode Pembayaran
          </h6>
          <div class="bg-var-theme-background pa-4 rounded mb-6 text-sm">
            <VRow no-gutters>
              <VCol
                cols="4"
                class="text-disabled mb-1"
              >
                Metode:
              </VCol>
              <VCol
                cols="8"
                class="font-weight-medium mb-1 text-capitalize"
                :class="props.sale.payment_method === 'tempo' ? 'text-error' : ''"
              >
                {{ props.sale.payment_method === 'transfer' ? 'Transfer Bank' : (props.sale.payment_method === 'qris' ? 'QRIS' : (props.sale.payment_method === 'tempo' ? 'Tempo (Utang)' : 'Cash (Tunai)')) }}
              </VCol>
              
              <template v-if="props.sale.payment_method === 'transfer'">
                <VCol
                  cols="4"
                  class="text-disabled mb-1"
                >
                  Bank:
                </VCol>
                <VCol
                  cols="8"
                  class="font-weight-medium mb-1"
                >
                  {{ props.sale.bank_name || '-' }}
                </VCol>
                
                <VCol
                  cols="4"
                  class="text-disabled mb-1"
                >
                  No. Rekening:
                </VCol>
                <VCol
                  cols="8"
                  class="font-weight-medium mb-1"
                >
                  {{ props.sale.bank_account_number || '-' }}
                </VCol>

                <VCol
                  cols="4"
                  class="text-disabled mb-1"
                >
                  Atas Nama:
                </VCol>
                <VCol
                  cols="8"
                  class="font-weight-medium mb-1"
                >
                  {{ props.sale.bank_account_name || '-' }}
                </VCol>

                <VCol
                  cols="4"
                  class="text-disabled mb-2"
                >
                  No. HP Pelanggan:
                </VCol>
                <VCol
                  cols="8"
                  class="font-weight-medium mb-2"
                >
                  {{ props.sale.transfer_phone_number || '-' }}
                </VCol>

                <VCol
                  v-if="props.sale.payment_proof"
                  cols="12"
                >
                  <div class="text-disabled mb-2">
                    Bukti Transfer:
                  </div>
                  <VImg 
                    :src="`/storage/${props.sale.payment_proof}`" 
                    max-width="100%" 
                    max-height="300"
                    class="rounded border"
                    cover
                  />
                  <VBtn 
                    variant="text" 
                    size="small" 
                    color="primary" 
                    class="mt-2 px-0"
                    :href="`/storage/${props.sale.payment_proof}`"
                    target="_blank"
                    prepend-icon="ri-external-link-line"
                  >
                    Buka Gambar
                  </VBtn>
                </VCol>
              </template>
            </VRow>
          </div>

          <VDivider class="mb-4" />

          <div class="d-flex justify-space-between text-body-2 mb-2">
            <span>Subtotal Barang</span>
            <span>{{ formatRupiah(props.sale.subtotal) }}</span>
          </div>
          <div class="d-flex justify-space-between text-body-2 mb-2 text-warning">
            <span>Total Pajak</span>
            <span>+ {{ formatRupiah(props.sale.total_tax) }}</span>
          </div>
          <div class="d-flex justify-space-between text-body-2 mb-4 text-error">
            <span>Diskon (Potongan)</span>
            <span>- {{ formatRupiah(props.sale.discount) }}</span>
          </div>
          <div class="d-flex justify-space-between align-center mb-6">
            <span class="text-h6 font-weight-bold">TOTAL BAYAR</span>
            <span class="text-h5 font-weight-bold text-primary">{{ formatRupiah(props.sale.total_amount) }}</span>
          </div>

          <!-- Info Piutang & Cicilan -->
          <template v-if="props.sale.receivable">
            <VDivider class="mb-4" />
            <div class="d-flex justify-space-between align-center mb-4">
              <h6 class="text-subtitle-1 font-weight-bold mb-0">
                Status Piutang / Cicilan
              </h6>
              <VChip
                :color="props.sale.receivable.status === 'paid' ? 'success' : (props.sale.receivable.status === 'partial' ? 'warning' : 'error')"
                size="small"
              >
                {{ props.sale.receivable.status === 'paid' ? 'LUNAS' : (props.sale.receivable.status === 'partial' ? 'SEBAGIAN' : 'BELUM LUNAS') }}
              </VChip>
            </div>
            
            <div class="bg-var-theme-background pa-4 rounded mb-6 text-sm">
              <VRow no-gutters>
                <VCol
                  cols="6"
                  class="text-disabled mb-1"
                >
                  Total Utang:
                </VCol>
                <VCol
                  cols="6"
                  class="font-weight-medium text-right mb-1"
                >
                  {{ formatRupiah(props.sale.receivable.amount_due) }}
                </VCol>
                
                <VCol
                  cols="6"
                  class="text-disabled mb-1"
                >
                  Sudah Dibayar:
                </VCol>
                <VCol
                  cols="6"
                  class="font-weight-medium text-success text-right mb-1"
                >
                  {{ formatRupiah(props.sale.receivable.amount_paid) }}
                </VCol>
                
                <VCol
                  cols="6"
                  class="text-disabled font-weight-bold mt-2"
                >
                  Sisa Utang:
                </VCol>
                <VCol
                  cols="6"
                  class="font-weight-bold text-error text-right mt-2"
                >
                  {{ formatRupiah(props.sale.receivable.amount_due - props.sale.receivable.amount_paid) }}
                </VCol>
              </VRow>
              
              <template v-if="props.sale.receivable.payments && props.sale.receivable.payments.length > 0">
                <VDivider class="my-3" />
                <div class="text-caption font-weight-bold mb-2">
                  Riwayat Pembayaran:
                </div>
                <VTable
                  density="compact"
                  class="bg-transparent text-caption"
                >
                  <tbody>
                    <tr
                      v-for="(payment, idx) in props.sale.receivable.payments"
                      :key="payment.id"
                    >
                      <td
                        class="px-0 py-1"
                        style="width: 20px;"
                      >
                        {{ idx + 1 }}.
                      </td>
                      <td class="px-0 py-1">
                        {{ new Date(payment.payment_date).toLocaleDateString('id-ID') }}
                      </td>
                      <td class="px-0 py-1 text-right text-success">
                        +{{ formatRupiah(payment.amount) }}
                      </td>
                    </tr>
                  </tbody>
                </VTable>
              </template>
            </div>
          </template>

          <!-- Void Button -->
          <VBtn 
            v-if="props.sale.status === 'completed' && $can('delete', 'Kasir (POS)')"
            block 
            color="error" 
            variant="tonal"
            prepend-icon="ri-close-circle-line"
            class="mt-4"
            @click="$emit('void-sale', props.sale)"
          >
            Batalkan Transaksi (Void)
          </VBtn>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
