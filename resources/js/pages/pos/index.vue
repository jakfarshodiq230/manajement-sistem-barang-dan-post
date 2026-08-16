<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'
import ApprovalDialog from './ApprovalDialog.vue'
import ReceiptPrinter from './ReceiptPrinter.vue'
import AddNewCustomerDrawer from '../customers/AddNewCustomerDrawer.vue'

const router = useRouter()
const ability = useAbility()

const logout = async () => {
  useCookie('accessToken').value = null
  useCookie('userData').value = null
  await router.push('/login')
  localStorage.removeItem('userAbilityRules')
  ability.update([])
}

const products = ref([])
const categories = ref([])
const branches = ref([]) // For selecting branch context if needed, usually tied to active user role
const customers = ref([]) // List of customers for POS
const receiptSettings = ref([]) // Dynamic receipt printer settings
const activeBranchId = ref(null) // Mocking active branch

const search = ref('')
const selectedCategory = ref(null)
const isLoading = ref(false)

const page = ref(1)
const totalPages = ref(1)
const userData = useCookie('userData')

const cart = ref([])
const discount = ref(0)
const isApprovalDialogVisible = ref(false)
const isConfirmDialogVisible = ref(false)
const pendingCheckoutData = ref(null)

const isSuccessDialogVisible = ref(false)
const completedSaleData = ref(null)

const activeReceiptSetting = computed(() => {
  if (receiptSettings.value.length === 0) return null
  return receiptSettings.value.find(s => s.is_default) || receiptSettings.value[0]
})

const transactionType = ref('lunas') // 'lunas', 'utang'
const paymentMethod = ref('cash') // 'cash', 'transfer', 'qris'
const customerId = ref(null)
const dueDate = ref(null)
const bankName = ref('')
const bankAccountNumber = ref('')
const bankAccountName = ref('')
const transferPhoneNumber = ref('')
const paymentProof = ref(null)

const isAddCustomerDrawerVisible = ref(false)

const paidAmountRaw = ref(0)
const dpAmountRaw = ref(0)

const paidAmountDisplay = computed({
  get: () => {
    return paidAmountRaw.value ? new Intl.NumberFormat('id-ID').format(paidAmountRaw.value) : ''
  },
  set: val => {
    const numericStr = String(val).replace(/\D/g, '')

    paidAmountRaw.value = numericStr ? parseInt(numericStr, 10) : 0
  },
})

const dpAmountDisplay = computed({
  get: () => {
    return dpAmountRaw.value ? new Intl.NumberFormat('id-ID').format(dpAmountRaw.value) : ''
  },
  set: val => {
    const numericStr = String(val).replace(/\D/g, '')

    dpAmountRaw.value = numericStr ? parseInt(numericStr, 10) : 0
  },
})

const changeAmount = computed(() => {
  const diff = paidAmountRaw.value - totalAmount.value
  
  return diff > 0 ? diff : 0
})

const isErrorDialogVisible = ref(false)
const errorMessage = ref('')

const snackbar = useSnackbarStore()

const isQrDialogVisible = ref(false)

const catalogUrl = computed(() => {
  // Encode id to obscure it (e.g. store-9 -> base64)
  const encodedId = btoa('store-' + activeBranchId.value).replace(/=/g, '')
  
  return window.location.origin + '/katalog/' + encodedId
})

const qrCodeUrl = computed(() => {
  return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(catalogUrl.value)}`
})

const showCatalogQR = () => {
  if (!activeBranchId.value) {
    snackbar.show('Pilih cabang terlebih dahulu', 'warning')
    
    return
  }
  isQrDialogVisible.value = true
}

const copyCatalogUrl = () => {
  navigator.clipboard.writeText(catalogUrl.value)
  snackbar.show('Tautan katalog berhasil disalin', 'success')
}

// Format currency (display)
const formatRupiah = value => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

// Format currency (input)
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

const fetchData = async () => {
  isLoading.value = true
  try {
    const [branchData, categoryData, customerData, receiptSettingsData] = await Promise.all([
      $api('/apps/branches'),
      $api('/apps/categories'),
      $api('/apps/customers', { params: { all: true, itemsPerPage: 1000 } }),
      $api('/apps/receipt-settings').catch(() => [])
    ])

    branches.value = branchData
    categories.value = categoryData
    customers.value = customerData.data || customerData
    receiptSettings.value = receiptSettingsData
    
    // For demo purposes, auto select the first branch
    if (branches.value.length > 0 && !activeBranchId.value) {
      activeBranchId.value = branches.value[0].id
    }
    
    if (activeBranchId.value) {
      fetchProducts(activeBranchId.value)
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data', 'error')
    isLoading.value = false
  }
}

const saveCustomer = async customerData => {
  try {
    const res = await $api('/apps/customers', {
      method: 'POST',
      body: customerData,
    })

    snackbar.show('Pelanggan berhasil ditambahkan', 'success')


    // Refresh customers list
    const customerList = await $api('/apps/customers', { params: { all: true, itemsPerPage: 1000 } })

    customers.value = customerList.data || customerList

    // Auto-select the newly added customer
    if (res.customer && res.customer.id) {
      customerId.value = res.customer.id
    } else {
      // Find the last added if id is not returned directly
      const newCust = Array.isArray(customers.value) ? customers.value.find(c => c.name === customerData.name) : null
      if (newCust) customerId.value = newCust.id
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menyimpan data pelanggan', 'error')
  }
}

const fetchProducts = async (branchId, resetPage = false) => {
  if (resetPage) page.value = 1
  isLoading.value = true
  try {
    let url = `/apps/product-branches?branch_id=${branchId}&paginate=true&has_stock=true&page=${page.value}`
    if (search.value) url += `&search=${encodeURIComponent(search.value)}`
    if (selectedCategory.value) url += `&category_id=${selectedCategory.value}`
    
    const data = await $api(url)

    products.value = data.data || []
    totalPages.value = data.last_page || 1
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data produk', 'error')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchData()
})

let searchTimeout
watch(search, async newVal => {
  if (newVal && newVal.toUpperCase().startsWith('BATCH-')) {
    const batchId = newVal.substring(6)
    try {
      const { batch, product_branch } = await $api(`/apps/pos/scan-batch/${batchId}`)

      addToCart(product_branch, batch)
      search.value = '' // Reset search
      snackbar.show(`Batch #${batch.id} ditambahkan`, 'success')
    } catch(e) {
      snackbar.show(e.data?.message || 'Batch tidak ditemukan atau kosong', 'error')
    }
    
    return
  }

  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    if (activeBranchId.value) fetchProducts(activeBranchId.value, true)
  }, 500)
})

watch(selectedCategory, () => {
  if (activeBranchId.value) fetchProducts(activeBranchId.value, true)
})

watch(page, () => {
  if (activeBranchId.value) fetchProducts(activeBranchId.value, false)
})

watch(activeBranchId, newVal => {
  if (newVal) {
    cart.value = [] // Clear cart if branch changes
    fetchProducts(newVal, true)
  }
})

const isBatchDialogVisible = ref(false)
const selectedProductForBatch = ref(null)
const availableBatches = ref([])

const handleProductClick = async item => {
  isLoading.value = true
  selectedProductForBatch.value = item
  
  try {
    const res = await $api(`/apps/product-branches/${item.id}`)
    const batches = res.product_batches?.filter(b => b.qty > 0) || []
    
    if (batches.length > 1) {
      availableBatches.value = batches
      isBatchDialogVisible.value = true
    } else {
      addToCart(item, batches.length === 1 ? batches[0] : null)
    }
  } catch(e) {
    console.error(e)
    addToCart(item)
  } finally {
    isLoading.value = false
  }
}

const selectBatch = batch => {
  addToCart(selectedProductForBatch.value, batch)
  isBatchDialogVisible.value = false
}

const addToCart = (productBranch, batch = null) => {
  const existingItem = cart.value.find(item => 
    item.product_branch_id === productBranch.id && 
    item.batch_id === (batch ? batch.id : null),
  )

  if (existingItem) {
    const maxQty = batch ? batch.qty : productBranch.stock
    if (existingItem.qty < maxQty) {
      existingItem.qty++
    } else {
      snackbar.show(`Stok ${batch ? 'batch' : 'cabang'} tidak mencukupi`, 'warning')
    }
  } else {
    const minNego = batch ? batch.min_nego_price : (productBranch.active_batch ? productBranch.active_batch.min_nego_price : productBranch.min_nego_price)
    const sellingPrice = batch ? batch.price : (productBranch.active_batch ? productBranch.active_batch.price : productBranch.price)
    
    cart.value.push({
      product_branch_id: productBranch.id,
      batch_id: batch ? batch.id : null,
      name: productBranch.product.name + (batch ? ` (Batch #${batch.id})` : ''),
      qty: 1,
      max_stock: batch ? batch.qty : productBranch.stock,
      cost_price: Math.round(productBranch.cost_price),
      min_nego_price: Math.round(minNego || 0),
      original_price: Math.round(sellingPrice || 0),
      price: Math.round(sellingPrice || 0),
      tax_percentage: productBranch.tax_percentage,
      tax_type: productBranch.product?.tax_type || 'Exclude PPN',
    })
  }
}

const removeFromCart = index => {
  cart.value.splice(index, 1)
}

const subtotal = computed(() => {
  return cart.value.reduce((sum, item) => sum + (item.qty * item.price), 0)
})

const totalTaxExclude = computed(() => {
  return cart.value.reduce((sum, item) => {
    if (item.tax_type === 'Exclude PPN') {
      const itemSubtotal = item.qty * item.price
      return sum + ((itemSubtotal * (item.tax_percentage || 0)) / 100)
    }
    return sum
  }, 0)
})

const totalTaxInclude = computed(() => {
  return cart.value.reduce((sum, item) => {
    if (item.tax_type === 'Include PPN') {
      const itemSubtotal = item.qty * item.price
      // Reversed calculation for Include PPN
      return sum + (itemSubtotal - (itemSubtotal / (1 + (item.tax_percentage || 0) / 100)))
    }
    return sum
  }, 0)
})

const totalTax = computed(() => {
  return totalTaxExclude.value + totalTaxInclude.value
})

const totalAmount = computed(() => {
  return subtotal.value + totalTaxExclude.value - discount.value
})

const handleCheckoutClick = () => {
  if (cart.value.length === 0) {
    snackbar.show('Keranjang belanja kosong!', 'warning')
    
    return
  }
  
  // Reset payment fields
  transactionType.value = 'lunas'
  paymentMethod.value = 'cash'
  paidAmountRaw.value = 0
  dpAmountRaw.value = 0
  customerId.value = null
  dueDate.value = null
  bankName.value = ''
  bankAccountNumber.value = ''
  bankAccountName.value = ''
  transferPhoneNumber.value = ''
  paymentProof.value = null
  
  isCheckoutDialogVisible.value = true
}

const submitCheckout = () => {
  if (!customerId.value) {
    snackbar.show('Mohon pilih pelanggan! (Wajib untuk semua transaksi)', 'warning')
    
    return
  }

  if (transactionType.value === 'utang') {
    if (!dueDate.value) {
      snackbar.show('Mohon isi tanggal jatuh tempo!', 'warning')
      
      return
    }
    if (dpAmountRaw.value > 0 && paymentMethod.value === 'transfer') {
      if (!bankName.value || !bankAccountNumber.value || !bankAccountName.value) {
        snackbar.show('Mohon lengkapi detail bank (Nama Bank, No. Rekening, Atas Nama) untuk DP', 'warning')
        
        return
      }
    }
  } else {
    // Lunas
    if (paymentMethod.value === 'cash') {
      if (!paidAmountRaw.value || paidAmountRaw.value < totalAmount.value) {
        snackbar.show('Uang bayar tidak boleh kurang dari total tagihan!', 'warning')
        
        return
      }
    } else if (paymentMethod.value === 'transfer') {
      if (!bankName.value || !bankAccountNumber.value || !bankAccountName.value) {
        snackbar.show('Mohon lengkapi detail bank (Nama Bank, No. Rekening, Atas Nama)', 'warning')
        
        return
      }
    }
  }

  // Check if any price is below min_nego_price
  const needsApproval = cart.value.some(item => Number(item.price) < Number(item.min_nego_price > 0 ? item.min_nego_price : item.original_price))
  
  isConfirmDialogVisible.value = true
}

const confirmAndSubmitCheckout = () => {
  const needsApproval = cart.value.some(item => Number(item.price) < Number(item.min_nego_price > 0 ? item.min_nego_price : item.original_price))

  const formData = new FormData()

  formData.append('branch_id', activeBranchId.value)
  formData.append('date', new Date().toISOString().substr(0, 10))
  formData.append('discount', discount.value)
  
  // Backend expects 'tempo' as payment_method if transactionType is utang
  formData.append('payment_method', transactionType.value === 'utang' ? 'tempo' : paymentMethod.value)

  if (customerId.value) {
    formData.append('customer_id', customerId.value)
  }

  if (transactionType.value === 'utang') {
    formData.append('due_date', dueDate.value)
    if (dpAmountRaw.value > 0) {
      formData.append('dp_amount', dpAmountRaw.value)
      formData.append('dp_payment_method', paymentMethod.value)
      
      if (paymentMethod.value === 'transfer') {
        formData.append('bank_name', bankName.value)
        formData.append('bank_account_number', bankAccountNumber.value)
        formData.append('bank_account_name', bankAccountName.value)
        if (transferPhoneNumber.value) formData.append('transfer_phone_number', transferPhoneNumber.value)
        if (paymentProof.value && paymentProof.value.length > 0) {
          formData.append('payment_proof', paymentProof.value[0])
        }
      }
    } else {
      formData.append('paid_amount', 0)
    }
  } else {
    // Lunas
    if (paymentMethod.value === 'cash') {
      formData.append('paid_amount', paidAmountRaw.value)
      formData.append('change_amount', changeAmount.value)
    } else if (paymentMethod.value === 'transfer') {
      formData.append('bank_name', bankName.value)
      formData.append('bank_account_number', bankAccountNumber.value)
      formData.append('bank_account_name', bankAccountName.value)
      if (transferPhoneNumber.value) formData.append('transfer_phone_number', transferPhoneNumber.value)
      if (paymentProof.value && paymentProof.value.length > 0) {
        formData.append('payment_proof', paymentProof.value[0])
      }
      formData.append('paid_amount', totalAmount.value)
    } else if (paymentMethod.value === 'qris') {
      formData.append('paid_amount', totalAmount.value)
    }
  }

  cart.value.forEach((item, index) => {
    formData.append(`items[${index}][product_branch_id]`, item.product_branch_id)
    formData.append(`items[${index}][qty]`, item.qty)
    formData.append(`items[${index}][price]`, item.price)
    if (item.batch_id) {
      formData.append(`items[${index}][batch_id]`, item.batch_id)
    }
  })

  isCheckoutDialogVisible.value = false
  isConfirmDialogVisible.value = false

  if (needsApproval) {
    pendingCheckoutData.value = formData
    isApprovalDialogVisible.value = true
  } else {
    processCheckout(formData)
  }
}

const processCheckout = async (formData, approverId = null) => {
  try {
    if (approverId) {
      formData.append('approved_by', approverId)
    }
    
    await $api('/apps/sales', {
      method: 'POST',
      body: formData,
    }).then(res => {
      snackbar.show('Transaksi Penjualan Berhasil!', 'success')
      completedSaleData.value = res.sale
      isSuccessDialogVisible.value = true
      fetchProducts(activeBranchId.value) // Refresh stock
    })
  } catch (error) {
    console.error('Checkout error:', error)
    
    // ofetch wraps response body in error.data or error.response._data
    let errorMsg = 'Gagal memproses transaksi'
    if (error.response && error.response._data && error.response._data.message) {
      errorMsg = error.response._data.message
    } else if (error.data && error.data.message) {
      errorMsg = error.data.message
    } else if (error.message) {
      errorMsg = error.message
    }
    
    errorMessage.value = errorMsg
    isErrorDialogVisible.value = true
  }
}

const handleApprovalSuccess = approverId => {
  isApprovalDialogVisible.value = false
  if (pendingCheckoutData.value) {
    processCheckout(pendingCheckoutData.value, approverId)
    pendingCheckoutData.value = null
  }
}

const handleApprovalCancel = () => {
  isApprovalDialogVisible.value = false
  pendingCheckoutData.value = null
}

const printReceipt = () => {
  setTimeout(() => {
    window.print()
  }, 100)
}

const startNewTransaction = () => {
  cart.value = []
  discount.value = 0
  isSuccessDialogVisible.value = false
  completedSaleData.value = null
}
</script>

<template>
  <div class="d-flex flex-column pa-4 bg-background">
    <div class="d-flex justify-space-between align-center mb-4">
      <div class="d-flex align-center">
        <VAvatar
          color="primary"
          variant="tonal"
          rounded
          class="me-3"
        >
          <VIcon icon="ri-store-3-line" />
        </VAvatar>
        <p class="text-2xl mb-0 font-weight-bold">
          Kasir (Point of Sales) <span
            v-if="userData?.fullName || userData?.name || userData?.username"
            class="text-primary"
          >- {{ userData?.fullName || userData?.name || userData?.username }}</span>
        </p>
      </div>
      
      <div class="d-flex align-center gap-4">
        <!-- Branch Selector Mock -->
        <VAutocomplete
          v-model="activeBranchId"
          :items="branches"
          item-title="name"
          item-value="id"
          density="compact"
          hide-details
          style="width: 250px;"
          prepend-inner-icon="ri-store-2-line"
          bg-color="surface"
        />


        <VMenu>
          <template #activator="{ props }">
            <VBtn
              icon="ri-more-2-fill"
              variant="text"
              v-bind="props"
            />
          </template>
          <VList>
            <VListItem
              prepend-icon="ri-qr-code-line"
              @click="showCatalogQR"
            >
              <VListItemTitle>QR & Link Katalog</VListItemTitle>
            </VListItem>
            <VDivider class="my-1" />
            <VListItem
              prepend-icon="ri-dashboard-line"
              :to="{ name: 'dashboards-analytics' }"
            >
              <VListItemTitle>Kembali ke Dashboard</VListItemTitle>
            </VListItem>
            <VDivider class="my-1" />
            <VListItem
              prepend-icon="ri-logout-box-r-line"
              color="error"
              @click="logout"
            >
              <VListItemTitle class="text-error">
                Keluar (Logout)
              </VListItemTitle>
            </VListItem>
          </VList>
        </VMenu>
      </div>
    </div>

    <VRow class="flex-grow-1 match-height m-0">
      <!-- Kiri: Katalog Produk -->
      <VCol
        cols="12"
        md="8"
        class="d-flex flex-column"
      >
        <VCard class="flex-grow-1 d-flex flex-column">
          <VCardText class="pb-2">
            <VRow>
              <VCol
                cols="12"
                sm="6"
              >
                <VTextField
                  v-model="search"
                  placeholder="Cari produk..."
                  density="compact"
                  prepend-inner-icon="ri-search-line"
                  hide-details
                />
              </VCol>
              <VCol
                cols="12"
                sm="6"
              >
                <VAutocomplete
                  v-model="selectedCategory"
                  :items="categories"
                  item-title="name"
                  item-value="id"
                  placeholder="Semua Kategori"
                  density="compact"
                  clearable
                  hide-details
                />
              </VCol>
            </VRow>
          </VCardText>
          
          <VDivider />

          <VCardText class="flex-grow-1 bg-var-theme-background pa-4">
            <div
              v-if="isLoading"
              class="d-flex justify-center align-center h-100"
            >
              <VProgressCircular
                indeterminate
                color="primary"
              />
            </div>
            
            <div v-else-if="products.length > 0">
              <VRow>
                <VCol
                  v-for="item in products"
                  :key="item.id"
                  cols="12"
                  sm="6"
                  md="4"
                  lg="3"
                >
                  <VCard 
                    class="h-100 cursor-pointer product-card transition-all"
                    :class="{'opacity-50 pointer-events-none': item.stock <= 3}"
                    elevation="2"
                    @click="item.stock > 3 ? handleProductClick(item) : null"
                  >
                    <div
                      class="bg-primary-lighten-4 pa-4 d-flex justify-center align-center"
                      style="height: 120px;"
                    >
                      <VIcon
                        icon="ri-box-3-line"
                        size="48"
                        color="primary"
                      />
                    </div>
                    <VCardText class="pa-3 text-center">
                      <h6
                        class="text-subtitle-2 font-weight-bold mb-1 text-truncate"
                        :title="item.product?.name"
                      >
                        {{ item.product?.name }}
                      </h6>
                      <div class="text-caption text-disabled mb-2">
                        {{ item.product?.sku }}
                      </div>
                      <div class="text-primary font-weight-bold mb-1">
                        {{ formatRupiah(item.price) }}
                      </div>
                      <div
                        v-if="item.min_nego_price > 0 && item.min_nego_price < item.price"
                        class="text-caption text-warning mb-2"
                      >
                        Batas Nego: {{ formatRupiah(item.min_nego_price) }}
                      </div>
                      <VChip
                        size="x-small"
                        :color="item.stock > 3 ? 'success' : 'error'"
                      >
                        {{ item.stock > 3 ? `Stok: ${item.stock}` : `Sisa Stok: ${item.stock} (Tidak bisa dijual)` }}
                      </VChip>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>
              <div class="d-flex justify-center mt-6">
                <VPagination
                  v-model="page"
                  :length="totalPages"
                  :total-visible="5"
                />
              </div>
            </div>
            
            <div
              v-else
              class="d-flex flex-column justify-center align-center h-100 text-disabled"
            >
              <VIcon
                icon="ri-inbox-line"
                size="48"
                class="mb-2"
              />
              <p>Tidak ada produk tersedia di cabang ini.</p>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Kanan: Keranjang (Cart) -->
      <VCol
        cols="12"
        md="4"
        class="d-flex flex-column"
      >
        <VCard class="flex-grow-1 d-flex flex-column">
          <VCardItem class="bg-primary text-white py-3">
            <VCardTitle class="text-white d-flex align-center">
              <VIcon
                icon="ri-shopping-cart-2-line"
                class="me-2"
              />
              Keranjang Belanja
            </VCardTitle>
          </VCardItem>

          <VCardText class="flex-grow-1 pa-0">
            <VList
              v-if="cart.length > 0"
              lines="two"
            >
              <template
                v-for="(item, index) in cart"
                :key="index"
              >
                <VListItem class="py-3">
                  <div class="d-flex justify-space-between w-100 mb-2">
                    <div
                      class="font-weight-bold text-truncate pe-2"
                      style="max-width: 70%;"
                    >
                      {{ item.name }}
                    </div>
                    <IconBtn
                      v-if="$can('delete', 'Kasir (POS)')"
                      size="x-small"
                      color="error"
                      @click="removeFromCart(index)"
                    >
                      <VIcon icon="ri-delete-bin-line" />
                    </IconBtn>
                  </div>
                  
                  <div class="d-flex align-center justify-space-between gap-2">
                    <!-- Qty Control -->
                    <div
                      class="d-flex align-center border rounded pa-1"
                      style="width: 100px;"
                    >
                      <VBtn
                        size="x-small"
                        variant="text"
                        icon="ri-subtract-line"
                        @click="item.qty > 1 ? item.qty-- : null"
                      />
                      <div class="text-center flex-grow-1 font-weight-medium">
                        {{ item.qty }}
                      </div>
                      <VBtn
                        size="x-small"
                        variant="text"
                        icon="ri-add-line"
                        @click="item.qty < item.max_stock ? item.qty++ : null"
                      />
                    </div>
                    <!-- Price Input (Nego) -->
                    <div class="flex-grow-1 ms-4">
                      <VTextField
                        :model-value="formatInputRupiah(item.price)"
                        type="text"
                        density="compact"
                        hide-details
                        prefix="Rp"
                        class="text-right"
                        @update:model-value="val => item.price = parseInputRupiah(val)"
                      />
                    </div>
                  </div>
                  
                  <!-- Warning if below nego limit -->
                  <div
                    v-if="Number(item.price) < Number(item.min_nego_price > 0 ? item.min_nego_price : item.original_price)"
                    class="text-caption text-error mt-1 d-flex align-center"
                  >
                    <VIcon
                      icon="ri-error-warning-line"
                      size="small"
                      class="me-1"
                    />
                    Harga di bawah batas nego ({{ formatRupiah(item.min_nego_price > 0 ? item.min_nego_price : item.original_price) }})!
                  </div>
                </VListItem>
                <VDivider v-if="index < cart.length - 1" />
              </template>
            </VList>
            
            <div
              v-else
              class="d-flex flex-column justify-center align-center h-100 text-disabled pa-6"
            >
              <VIcon
                icon="ri-shopping-bag-3-line"
                size="48"
                class="mb-2"
                opacity="0.3"
              />
              <p>Keranjang kosong</p>
            </div>
          </VCardText>

          <VDivider />

          <!-- Checkout Summary -->
          <VCardText class="bg-var-theme-background">
            <div class="d-flex justify-space-between mb-2">
              <span class="text-body-1">Subtotal Barang</span>
              <span class="font-weight-medium">{{ formatRupiah(subtotal) }}</span>
            </div>
            <div v-if="totalTaxExclude > 0" class="d-flex justify-space-between align-center mb-2 text-warning">
              <span class="text-body-1">Pajak Tambahan (Exclude)</span>
              <span class="font-weight-medium">+ {{ formatRupiah(totalTaxExclude) }}</span>
            </div>
            <div v-if="totalTaxInclude > 0" class="d-flex justify-space-between align-center mb-2 text-info">
              <span class="text-caption"><i>(Pajak di dalam harga: {{ formatRupiah(totalTaxInclude) }})</i></span>
            </div>
            <div class="d-flex justify-space-between align-center mb-4">
              <span class="text-body-1">Diskon Total</span>
              <div
                class="flex-grow-1 ms-8"
                style="max-width: 200px;"
              >
                <VTextField
                  :model-value="formatInputRupiah(discount)"
                  type="text"
                  density="compact"
                  hide-details
                  prefix="Rp"
                  class="text-right"
                  @update:model-value="val => discount = parseInputRupiah(val)"
                />
              </div>
            </div>
            <VDivider class="mb-4" />
            <div class="d-flex justify-space-between align-center mb-6">
              <span class="text-h6 font-weight-bold">TOTAL</span>
              <span class="text-h5 font-weight-bold text-primary">{{ formatRupiah(totalAmount) }}</span>
            </div>
            
            <VBtn
              block
              color="primary"
              size="large"
              prepend-icon="ri-bank-card-line"
              :disabled="cart.length === 0"
              @click="handleCheckoutClick"
            >
              Bayar Sekarang
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Checkout Dialog -->
    <VDialog
      v-model="isCheckoutDialogVisible"
      max-width="550"
    >
      <VCard>
        <VCardTitle class="bg-primary text-white pa-4 d-flex align-center justify-space-between">
          <span>Checkout & Pembayaran</span>
          <VBtn
            icon="ri-close-line"
            variant="text"
            size="small"
            @click="isCheckoutDialogVisible = false"
          />
        </VCardTitle>
        <VCardText class="pa-5">
          <div class="text-h6 mb-5 d-flex justify-space-between align-center">
            Total Tagihan: 
            <span class="font-weight-bold text-primary">{{ formatRupiah(totalAmount) }}</span>
          </div>
          
          <VRadioGroup
            v-model="transactionType"
            label="Jenis Transaksi"
            inline
          >
            <VRadio
              label="Lunas (Langsung Bayar)"
              value="lunas"
              color="success"
            />
            <VRadio
              label="Utang (Tempo/Piutang)"
              value="utang"
              color="error"
            />
          </VRadioGroup>

          <!-- Customer Selection (Always visible & Required) -->
          <div class="mt-4">
            <div class="d-flex align-center justify-space-between mb-1">
              <span class="text-caption">Pelanggan (Wajib)</span>
              <VBtn
                variant="text"
                size="small"
                color="primary"
                prepend-icon="ri-add-line"
                @click="isAddCustomerDrawerVisible = true"
              >
                Pelanggan Baru
              </VBtn>
            </div>
            <VAutocomplete
              v-model="customerId"
              :items="customers"
              item-title="name"
              item-value="id"
              placeholder="Pilih Pelanggan"
              density="compact"
              clearable
              :error-messages="!customerId ? ['Pelanggan wajib dipilih'] : []"
              class="mb-4"
            />
          </div>

          <VExpandTransition>
            <div
              v-if="transactionType === 'utang'"
              class="mt-4 pa-4 bg-error-lighten-4 rounded border"
            >
              <p class="font-weight-bold text-error mb-4">
                <VIcon
                  icon="ri-error-warning-line"
                  class="me-2"
                /> Detail Piutang
              </p>
              
              <VRow>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VTextField
                    v-model="dueDate"
                    label="Tanggal Jatuh Tempo"
                    type="date"
                    density="compact"
                    :error-messages="!dueDate ? ['Pilih tanggal jatuh tempo'] : []"
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VTextField
                    v-model="dpAmountDisplay"
                    label="Nominal Uang Muka / DP (Opsional)"
                    density="compact"
                    prefix="Rp"
                  />
                </VCol>
              </VRow>
            </div>
          </VExpandTransition>

          <VExpandTransition>
            <div
              v-if="transactionType === 'lunas' || (transactionType === 'utang' && dpAmountRaw > 0)"
              class="mt-6"
            >
              <VDivider class="mb-4" />
              <VRadioGroup
                v-model="paymentMethod"
                :label="transactionType === 'lunas' ? 'Metode Pembayaran Pelunasan' : 'Metode Pembayaran DP'"
                inline
              >
                <VRadio
                  label="Cash (Tunai)"
                  value="cash"
                  color="primary"
                />
                <VRadio
                  label="Transfer Bank"
                  value="transfer"
                  color="primary"
                />
                <VRadio
                  label="QRIS"
                  value="qris"
                  color="primary"
                />
              </VRadioGroup>
            </div>
          </VExpandTransition>

          <VExpandTransition>
            <div
              v-if="(transactionType === 'lunas' || dpAmountRaw > 0) && paymentMethod === 'cash'"
              class="mt-4"
            >
              <VRow>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    v-model="paidAmountDisplay"
                    label="Uang Bayar (Rp)"
                    density="compact"
                    variant="outlined"
                    prefix="Rp"
                    color="primary"
                    :error-messages="paidAmountRaw < totalAmount && paidAmountRaw > 0 ? ['Uang kurang'] : []"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                  class="d-flex align-center"
                >
                  <div
                    class="text-h6"
                    :class="changeAmount > 0 ? 'text-success' : 'text-medium-emphasis'"
                  >
                    Kembalian: {{ formatRupiah(changeAmount) }}
                  </div>
                </VCol>
              </VRow>
            </div>
          </VExpandTransition>

          <VExpandTransition>
            <div
              v-if="(transactionType === 'lunas' || dpAmountRaw > 0) && paymentMethod === 'transfer'"
              class="mt-4"
            >
              <VAlert
                type="info"
                variant="tonal"
                class="mb-4 text-caption py-2"
              >
                Pilih pembayaran transfer jika pelanggan membayar menggunakan m-Banking, EDC, atau transfer rekening.
              </VAlert>
              
              <VRow>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VTextField
                    v-model="bankName"
                    label="Nama Bank (misal: BCA/Mandiri)"
                    placeholder="BCA / Mandiri"
                    density="compact"
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VTextField
                    v-model="bankAccountNumber"
                    label="Nomor Rekening"
                    density="compact"
                  />
                </VCol>
                <VCol cols="12">
                  <VTextField
                    v-model="bankAccountName"
                    label="Atas Nama Rekening"
                    density="compact"
                  />
                </VCol>
                <VCol cols="12">
                  <VTextField
                    v-model="transferPhoneNumber"
                    label="Nomor HP Pelanggan (Opsional)"
                    density="compact"
                  />
                </VCol>
                <VCol cols="12">
                  <VFileInput
                    v-model="paymentProof"
                    label="Bukti Transfer (Opsional)"
                    accept="image/*"
                    prepend-icon=""
                    prepend-inner-icon="ri-image-add-line"
                    show-size
                    density="compact"
                  />
                </VCol>
              </VRow>
            </div>
          </VExpandTransition>

          <VExpandTransition>
            <div
              v-if="(transactionType === 'lunas' || dpAmountRaw > 0) && paymentMethod === 'qris'"
              class="mt-4 text-center"
            >
              <template v-if="branches.find(b => b.id === activeBranchId)?.owner?.qris_image">
                <VAlert
                  type="info"
                  variant="tonal"
                  class="mb-4 text-caption py-2 text-start"
                >
                  Minta pelanggan memindai kode QRIS di bawah ini. Pastikan pembayaran telah berhasil diterima sebelum menekan tombol Proses.
                </VAlert>
                <div class="pa-4 bg-white rounded-lg d-inline-block border">
                  <img
                    :src="`/storage/${branches.find(b => b.id === activeBranchId).owner.qris_image}`"
                    alt="QRIS"
                    style="max-width: 250px; height: auto;"
                    class="rounded"
                  >
                  <div class="mt-2 font-weight-bold text-h6 text-primary">
                    QRIS Pembayaran
                  </div>
                  <div class="text-caption">
                    a.n. {{ branches.find(b => b.id === activeBranchId)?.owner?.name || 'Owner' }}
                  </div>
                </div>
              </template>
              <template v-else>
                <VAlert
                  type="warning"
                  variant="tonal"
                  class="mb-4 text-start"
                >
                  Kode QRIS belum diunggah oleh Owner Cabang ini. Silakan hubungi Owner untuk mengatur QRIS di menu pengaturan Owner.
                </VAlert>
              </template>
            </div>
          </VExpandTransition>
        </VCardText>
        <VCardActions class="pa-5 pt-0 justify-end">
          <VBtn
            color="secondary"
            variant="outlined"
            @click="isCheckoutDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            @click="submitCheckout"
          >
            Proses Pembayaran
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <ApprovalDialog
      v-model:is-dialog-visible="isApprovalDialogVisible"
      @success="handleApprovalSuccess"
      @cancel="handleApprovalCancel"
    />

    <!-- Confirm Dialog -->
    <VDialog
      v-model="isConfirmDialogVisible"
      max-width="400"
    >
      <VCard title="Konfirmasi Pembayaran">
        <VCardText>
          Apakah Anda yakin ingin memproses pembayaran ini sejumlah <strong>{{ formatRupiah(totalAmount) }}</strong>?<br><br>
          <span class="text-error font-weight-bold">Perhatian:</span> Transaksi yang sudah diproses tidak dapat diubah atau dibatalkan.
        </VCardText>
        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            @click="confirmAndSubmitCheckout"
          >
            Ya, Proses Sekarang
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Success Dialog -->
    <VDialog
      v-model="isSuccessDialogVisible"
      max-width="400"
      persistent
    >
      <VCard class="text-center pb-4">
        <VCardText class="pt-8">
          <VAvatar
            color="success"
            variant="tonal"
            size="80"
            class="mb-4"
          >
            <VIcon
              icon="ri-check-line"
              size="50"
            />
          </VAvatar>
          <h4 class="text-h4 mb-2">
            Transaksi Sukses!
          </h4>
          <p class="text-medium-emphasis mb-6">
            Pembayaran telah diterima dan dicatat dalam sistem.
          </p>
          
          <VBtn
            color="primary"
            block
            size="large"
            class="mb-3"
            prepend-icon="ri-printer-line"
            @click="printReceipt"
          >
            Cetak Struk (Print)
          </VBtn>
          <VBtn
            color="secondary"
            variant="tonal"
            block
            size="large"
            @click="startNewTransaction"
          >
            Transaksi Baru
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <ReceiptPrinter 
      v-if="completedSaleData"
      :sale="completedSaleData"
      :branch="branches.find(b => b.id === activeBranchId)" 
      :cashier-name="userData?.fullName || userData?.name || userData?.username"
    />

    <!-- QR Catalog Dialog -->
    <VDialog
      v-model="isQrDialogVisible"
      max-width="400"
    >
      <VCard>
        <VCardTitle class="bg-primary text-white pa-4 d-flex justify-space-between align-center">
          <span>Katalog Cabang</span>
          <VBtn
            icon="ri-close-line"
            variant="text"
            size="small"
            @click="isQrDialogVisible = false"
          />
        </VCardTitle>
        <VCardText class="pa-5 text-center">
          <p class="mb-4 text-body-1">
            Scan QR Code berikut atau salin tautan untuk membagikan katalog ke pelanggan.
          </p>
          <div class="mb-4 d-flex justify-center bg-white pa-2 rounded border">
            <VImg
              :src="qrCodeUrl"
              width="200"
              height="200"
            />
          </div>
          <VTextField
            v-model="catalogUrl"
            readonly
            variant="outlined"
            density="compact"
            hide-details
            class="mb-4"
          >
            <template #append-inner>
              <VBtn
                icon="ri-file-copy-line"
                variant="text"
                size="small"
                @click="copyCatalogUrl"
              />
            </template>
          </VTextField>
          <VBtn
            color="primary"
            block
            @click="isQrDialogVisible = false"
          >
            Tutup
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Error Dialog -->
    <VDialog
      v-model="isErrorDialogVisible"
      max-width="500"
    >
      <VCard title="Transaksi Ditolak">
        <VCardText>
          <VAlert
            color="error"
            variant="tonal"
            class="mb-4"
          >
            {{ errorMessage }}
          </VAlert>
        </VCardText>
        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn
            color="primary"
            @click="isErrorDialogVisible = false"
          >
            Tutup
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Select Batch Dialog -->
    <VDialog
      v-model="isBatchDialogVisible"
      max-width="600"
    >
      <VCard title="Pilih Batch Barang">
        <VCardText>
          <p class="mb-4 text-body-2">
            Produk <strong>{{ selectedProductForBatch?.product?.name }}</strong> memiliki beberapa batch dengan stok tersedia. Pilih batch mana yang akan dijual:
          </p>
          <VList
            lines="two"
            border
            rounded
          >
            <template
              v-for="(batch, i) in availableBatches"
              :key="batch.id"
            >
              <VListItem
                class="cursor-pointer"
                @click="selectBatch(batch)"
              >
                <VListItemTitle class="font-weight-bold">
                  Batch #{{ batch.id }}
                </VListItemTitle>
                <VListItemSubtitle class="mt-1">
                  Stok Tersedia: <strong>{{ batch.qty }}</strong> | 
                  Harga Jual: <strong class="text-success">{{ formatRupiah(batch.price) }}</strong> | 
                  Batas Nego: <strong>{{ formatRupiah(batch.min_nego_price) }}</strong> | 
                  Kadaluarsa: <strong>{{ batch.expiration_date || '-' }}</strong>
                </VListItemSubtitle>
                <template #append>
                  <VIcon
                    icon="ri-add-circle-line"
                    color="primary"
                  />
                </template>
              </VListItem>
              <VDivider v-if="i < availableBatches.length - 1" />
            </template>
          </VList>
        </VCardText>
        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isBatchDialogVisible = false"
          >
            Batal
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <AddNewCustomerDrawer
      v-model:is-drawer-open="isAddCustomerDrawerVisible"
      :selected-customer="null"
      @save-data="saveCustomer"
    />
  </div>
</template>

<style scoped>
.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
  border-color: rgba(var(--v-theme-primary), 0.5);
}
.transition-all {
  transition: all 0.3s ease;
}
</style>

<route lang="yaml">
meta:
  action: read
  subject: Kasir (POS)
  layout: blank
</route>
