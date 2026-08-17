<script setup>
const formatCurrency = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value || 0)

const props = defineProps({
  userData: {
    type: Object,
    required: true,
  },
})

const headers = [
  { title: 'INVOICE', key: 'invoice_number' },
  { title: 'TANGGAL', key: 'date' },
  { title: 'TOTAL BAYAR', key: 'total_amount' },
  { title: 'CABANG', key: 'branch_name' },
  { title: 'STATUS', key: 'status' },
]
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardItem>
          <VCardTitle>Riwayat Transaksi POS (20 Terakhir)</VCardTitle>
        </VCardItem>
        <VCardText>
          <VDataTable
            v-if="props.userData?.recentSales?.length > 0"
            :headers="headers"
            :items="props.userData.recentSales"
            :items-per-page="5"
            class="text-no-wrap"
          >
            <template #item.total_amount="{ item }">
              {{ formatCurrency(item.total_amount) }}
            </template>
            <template #item.status="{ item }">
              <VChip
                :color="item.status === 'paid' ? 'success' : 'error'"
                size="small"
                class="text-capitalize"
              >
                {{ item.status === 'paid' ? 'Lunas' : 'Belum Lunas' }}
              </VChip>
            </template>
          </VDataTable>
          
          <VAlert
            v-else
            color="info"
            variant="tonal"
          >
            Belum ada aktivitas transaksi yang tercatat untuk kasir ini.
          </VAlert>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>