<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
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
const isCheckoutDialogVisible = ref(false)
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
const selectedCustomer = ref(null)
const customerSearch = ref('')
const isSearchingCustomer = ref(false)
let customerSearchTimeout = null

const onCustomerSearchInput = val => {
  customerSearch.value = val || ''
  clearTimeout(customerSearchTimeout)
  if (!val) return
  customerSearchTimeout = setTimeout(async () => {
    try {
      isSearchingCustomer.value = true
      const res = await $api('/apps/customers', { params: { search: val, itemsPerPage: 30 } })
      const list = res.data || res || []
      
      const map = new Map()
      customers.value.forEach(c => map.set(c.id, c))
      list.forEach(c => map.set(c.id, c))
      customers.value = Array.from(map.values())
    } catch (e) {
      console.error(e)
    } finally {
      isSearchingCustomer.value = false
    }
  }, 300)
}

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

const setQuickCash = val => {
  if (val === 'exact') {
    paidAmountRaw.value = totalAmount.value
  } else {
    paidAmountRaw.value = val
  }
}

const quickCashSuggestions = computed(() => {
  const tot = totalAmount.value || 0
  const baseNominals = [10000, 20000, 50000, 100000, 200000, 500000]
  const valid = baseNominals.filter(n => n >= tot)
  if (valid.length === 0) {
    valid.push(Math.ceil(tot / 100000) * 100000)
  }
  return valid.slice(0, 4)
})

const isErrorDialogVisible = ref(false)
const errorMessage = ref('')

const snackbar = useSnackbarStore()

// ======================= 1. SHIFT KASIR STATE =======================
const currentShift = ref(null)
const hasActiveShift = ref(true)
const isShiftChecking = ref(true)
const isStartShiftDialogOpen = ref(false)
const isCloseShiftDialogOpen = ref(false)
const startCashInput = ref('')
const actualCashInput = ref('')
const closeShiftNotes = ref('')
const isSubmittingShift = ref(false)
const shiftSummary = ref(null)

// Capital Return during Closing Shift
const isDepositingCapitalReturn = ref(false)
const capitalReturnAmountInput = ref('')
const capitalReturnBankName = ref('')
const capitalReturnProofFile = ref(null)

const checkCurrentShift = async () => {
  try {
    const res = await $api('/apps/cash-shifts/current')
    hasActiveShift.value = res.has_active_shift
    currentShift.value = res.shift
    shiftSummary.value = res.summary || null
    if (!res.has_active_shift) {
      isStartShiftDialogOpen.value = true
    }
  } catch (e) {
    console.error('Failed to check shift:', e)
  } finally {
    isShiftChecking.value = false
  }
}

const openShift = async () => {
  if (!startCashInput.value && startCashInput.value !== 0 && startCashInput.value !== '0') {
    snackbar.show('Silakan masukkan modal kas awal', 'warning')
    return
  }
  isSubmittingShift.value = true
  try {
    const res = await $api('/apps/cash-shifts/open', {
      method: 'POST',
      body: {
        start_cash: parseInputRupiah(startCashInput.value),
      },
    })
    snackbar.show('Shift kasir berhasil dibuka', 'success')
    isStartShiftDialogOpen.value = false
    startCashInput.value = ''
    await checkCurrentShift()
  } catch (e) {
    snackbar.show(e.data?.message || 'Gagal membuka shift', 'error')
  } finally {
    isSubmittingShift.value = false
  }
}

const openCloseShiftDialog = async () => {
  await checkCurrentShift()
  actualCashInput.value = ''
  closeShiftNotes.value = ''
  isDepositingCapitalReturn.value = false
  capitalReturnAmountInput.value = ''
  capitalReturnBankName.value = ''
  capitalReturnProofFile.value = null
  isCloseShiftDialogOpen.value = true
}

const submitCloseShift = async () => {
  if (!actualCashInput.value && actualCashInput.value !== 0 && actualCashInput.value !== '0') {
    snackbar.show('Silakan masukkan jumlah uang fisik di laci', 'warning')
    return
  }
  isSubmittingShift.value = true
  try {
    const formData = new FormData()
    formData.append('actual_cash', parseInputRupiah(actualCashInput.value))
    if (closeShiftNotes.value) formData.append('notes', closeShiftNotes.value)

    if (isDepositingCapitalReturn.value && parseInputRupiah(capitalReturnAmountInput.value) > 0) {
      formData.append('capital_return_amount', parseInputRupiah(capitalReturnAmountInput.value))
      if (capitalReturnBankName.value) formData.append('bank_name', capitalReturnBankName.value)
      if (capitalReturnProofFile.value) formData.append('proof_file', capitalReturnProofFile.value)
    }

    const res = await $api('/apps/cash-shifts/close', {
      method: 'POST',
      body: formData,
    })
    snackbar.show('Shift kasir berhasil ditutup' + (isDepositingCapitalReturn.value ? ' dan pengembalian modal telah diajukan' : ''), 'success')
    isCloseShiftDialogOpen.value = false
    shiftSummary.value = res.summary
    hasActiveShift.value = false
    currentShift.value = null
    isStartShiftDialogOpen.value = true
  } catch (e) {
    snackbar.show(e.data?.message || 'Gagal menutup shift', 'error')
  } finally {
    isSubmittingShift.value = false
  }
}

// ======================= 2. HOLD BILL STATE =======================
const heldBills = ref([])
const isHeldBillsDialogOpen = ref(false)
const isHoldingBill = ref(false)

const fetchHeldBills = async () => {
  try {
    const res = await $api('/apps/pos-held-bills', {
      params: { branch_id: activeBranchId.value || undefined },
    })
    heldBills.value = res.data || []
  } catch (e) {
    console.error('Failed to fetch held bills:', e)
  }
}

const holdCurrentBill = async () => {
  if (cart.value.length === 0) {
    snackbar.show('Keranjang masih kosong untuk ditahan', 'warning')
    return
  }
  isHoldingBill.value = true
  try {
    const custObj = customers.value.find(c => c.id === customerId.value)
    const custName = custObj ? custObj.name : (customerSearch.value || 'Pelanggan Walk-In')

    await $api('/apps/pos-held-bills', {
      method: 'POST',
      body: {
        branch_id: activeBranchId.value,
        items: cart.value,
        subtotal: subtotal.value,
        discount: discount.value,
        total: totalAmount.value,
        customer_id: customerId.value,
        customer_name: custName,
      },
    })

    snackbar.show('Transaksi berhasil disimpan sementara (Hold Bill)', 'success')
    cart.value = []
    discount.value = 0
    customerId.value = null
    selectedCustomer.value = null
    customerSearch.value = ''
    await fetchHeldBills()
  } catch (e) {
    snackbar.show(e.data?.message || 'Gagal menyimpan transaksi sementara', 'error')
  } finally {
    isHoldingBill.value = false
  }
}

const resumeHeldBill = async bill => {
  cart.value = bill.items_json || []
  discount.value = bill.discount || 0
  if (bill.customer_id) {
    customerId.value = bill.customer_id
    selectedCustomer.value = bill.customer_id
    customerSearch.value = bill.customer_name || ''
  }
  try {
    await $api(`/apps/pos-held-bills/${bill.id}`, { method: 'DELETE' })
    await fetchHeldBills()
    isHeldBillsDialogOpen.value = false
    snackbar.show('Transaksi ditahan berhasil dimuat kembali ke keranjang', 'success')
  } catch (e) {
    console.error('Failed to delete held bill:', e)
  }
}

const deleteHeldBill = async id => {
  try {
    await $api(`/apps/pos-held-bills/${id}`, { method: 'DELETE' })
    await fetchHeldBills()
    snackbar.show('Transaksi ditahan berhasil dihapus', 'info')
  } catch (e) {
    console.error('Failed to delete held bill:', e)
  }
}

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

    branches.value = branchData.data || branchData || []
    categories.value = categoryData.data || categoryData || []
    customers.value = customerData.data || customerData || []
    receiptSettings.value = receiptSettingsData.data || receiptSettingsData || []
    
    await Promise.all([
      checkCurrentShift(),
      fetchHeldBills(),
    ])

    // Select active branch: Priority: Shift branch -> User branch -> Kantor Pusat (ID 1) -> First branch
    if (!activeBranchId.value) {
      const preferred = currentShift.value?.branch_id
        || userData.value?.branch_id
        || (Array.isArray(branches.value) ? branches.value.find(b => b.name?.toLowerCase().includes('pusat') || b.id === 1)?.id : null)
        || (Array.isArray(branches.value) && branches.value.length > 0 ? branches.value[0].id : 1)

      activeBranchId.value = preferred
    }

    // Always fetch products explicitly for active branch
    if (activeBranchId.value) {
      await fetchProducts(activeBranchId.value, true)
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal mengambil data', 'error')
  } finally {
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
      selectedCustomer.value = res.customer.id
    } else {
      // Find the last added if id is not returned directly
      const newCust = Array.isArray(customers.value) ? customers.value.find(c => c.name === customerData.name) : null
      if (newCust) {
        customerId.value = newCust.id
        selectedCustomer.value = newCust.id
      }
    }
  } catch (error) {
    console.error(error)
    snackbar.show('Gagal menyimpan data pelanggan', 'error')
  }
}

const fetchProducts = async (branchId, resetPage = false) => {
  if (!branchId) return
  if (resetPage) page.value = 1
  isLoading.value = true
  try {
    let url = `/apps/product-branches?branch_id=${branchId}&paginate=true&has_stock=true&per_page=9&page=${page.value}`
    if (search.value) url += `&search=${encodeURIComponent(search.value)}`
    if (selectedCategory.value) url += `&category_id=${selectedCategory.value}`
    
    const data = await $api(url)
    const list = data.data || (Array.isArray(data) ? data : [])

    products.value = Array.isArray(list) ? list : []
    totalPages.value = data.last_page || 1
  } catch (error) {
    console.error('Error fetching POS products:', error)
    snackbar.show('Gagal mengambil data produk', 'error')
  } finally {
    isLoading.value = false
  }
}

const searchInputRef = ref(null)

const handleKeyDown = event => {
  // F1 or F2: Search / Scan Product
  if (event.key === 'F1' || event.key === 'F2') {
    event.preventDefault()
    if (searchInputRef.value) {
      const inputEl = searchInputRef.value.$el?.querySelector('input') || searchInputRef.value
      if (inputEl && inputEl.focus) {
        inputEl.focus()
        inputEl.select?.()
      }
    }
  } 
  // F3 or F4: Pilih / Tambah Pelanggan
  else if (event.key === 'F3' || event.key === 'F4') {
    event.preventDefault()
    isAddCustomerDrawerVisible.value = !isAddCustomerDrawerVisible.value
  } 
  // F6: Hold / Tahan Transaksi Saat Ini
  else if (event.key === 'F6') {
    event.preventDefault()
    if (cart.value.length > 0 && !isCheckoutDialogVisible.value && !isHoldingBill.value) {
      holdCurrentBill()
    }
  }
  // F7: Buka / Lihat Daftar Transaksi Ditahan (Held Bills)
  else if (event.key === 'F7') {
    event.preventDefault()
    isHeldBillsDialogOpen.value = !isHeldBillsDialogOpen.value
    if (isHeldBillsDialogOpen.value) {
      fetchHeldBills()
    }
  }
  // F8: Checkout / Bayar
  else if (event.key === 'F8') {
    event.preventDefault()
    if (cart.value.length > 0 && !isCheckoutDialogVisible.value) {
      handleCheckoutClick()
    }
  } 
  // F9: Uang Pas
  else if (event.key === 'F9') {
    event.preventDefault()
    if (isCheckoutDialogVisible.value) {
      setQuickCash('exact')
    }
  } 
  // Escape: Batal / Tutup Dialog
  else if (event.key === 'Escape') {
    if (isAddCustomerDrawerVisible.value) {
      isAddCustomerDrawerVisible.value = false
    } else if (isBatchDialogVisible.value) {
      isBatchDialogVisible.value = false
    } else if (isShiftSummaryVisible.value) {
      isShiftSummaryVisible.value = false
    } else if (isHeldBillsDialogOpen.value) {
      isHeldBillsDialogOpen.value = false
    } else if (isConfirmDialogVisible.value) {
      isConfirmDialogVisible.value = false
    } else if (isErrorDialogVisible.value) {
      isErrorDialogVisible.value = false
    } else if (isCheckoutDialogVisible.value) {
      isCheckoutDialogVisible.value = false
    }
  } 
  // Enter: Konfirmasi Bayar saat modal checkout terbuka
  else if (event.key === 'Enter') {
    if (isConfirmDialogVisible.value) {
      event.preventDefault()
      confirmAndSubmitCheckout()
    } else if (isCheckoutDialogVisible.value && !isProcessing.value) {
      const isCredit = transactionType.value === 'utang'
      const isDueValid = isCredit ? !!dueDate.value : true
      const isCashValid = !isCredit ? (paidAmountRaw.value >= totalAmount.value) : true
      const isCustomerValid = !!selectedCustomer.value || !!customerSearch.value

      if (isCustomerValid && isDueValid && isCashValid) {
        event.preventDefault()
        handleCheckoutSubmit()
      }
    }
  }
}

onMounted(() => {
  fetchData()
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
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
    fetchHeldBills()
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

  const availableStock = batch ? batch.qty : productBranch.stock

  if (availableStock <= 0) {
    snackbar.show('Stok produk habis!', 'error')
    return
  }

  if (existingItem) {
    const maxQty = availableStock
    if (existingItem.qty < maxQty) {
      existingItem.qty++
      if (maxQty - existingItem.qty <= 3 && maxQty - existingItem.qty > 0) {
        snackbar.show(`Peringatan: Sisa stok ${productBranch.product?.name || ''} tersisa ${maxQty - existingItem.qty}`, 'warning')
      }
    } else {
      snackbar.show(`Stok ${batch ? 'batch' : 'cabang'} tidak mencukupi (Maksimal: ${maxQty})`, 'warning')
    }
  } else {
    const minNego = batch ? batch.min_nego_price : (productBranch.active_batch ? productBranch.active_batch.min_nego_price : productBranch.min_nego_price)
    const sellingPrice = batch ? batch.price : (productBranch.active_batch ? productBranch.active_batch.price : productBranch.price)
    
    if (availableStock <= 3) {
      snackbar.show(`Peringatan: Stok ${productBranch.product?.name || 'produk'} menipis (Sisa: ${availableStock})`, 'warning')
    }

    cart.value.push({
      product_branch_id: productBranch.id,
      batch_id: batch ? batch.id : null,
      name: productBranch.product.name + (batch ? ` (Batch #${batch.id})` : ''),
      qty: 1,
      max_stock: availableStock,
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
  return Math.max(0, subtotal.value + totalTaxExclude.value - (Number(discount.value) || 0))
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
  selectedCustomer.value = null
  customerSearch.value = ''
  dueDate.value = null
  bankName.value = ''
  bankAccountNumber.value = ''
  bankAccountName.value = ''
  transferPhoneNumber.value = ''
  paymentProof.value = null
  
  isCheckoutDialogVisible.value = true
}

const submitCheckout = async () => {
  let targetCustomerId = null
  let targetCustomerName = ''

  if (selectedCustomer.value) {
    if (typeof selectedCustomer.value === 'object' && selectedCustomer.value.id) {
      targetCustomerId = selectedCustomer.value.id
      targetCustomerName = selectedCustomer.value.name
    } else if (typeof selectedCustomer.value === 'number') {
      targetCustomerId = selectedCustomer.value
      const found = customers.value.find(c => c.id === targetCustomerId)
      if (found) targetCustomerName = found.name
    } else if (typeof selectedCustomer.value === 'string' && selectedCustomer.value.trim()) {
      targetCustomerName = selectedCustomer.value.trim()
    }
  } else if (customerSearch.value && customerSearch.value.trim()) {
    targetCustomerName = customerSearch.value.trim()
  }

  // Wajib jika transaksi Utang (Tempo/Piutang)
  if (transactionType.value === 'utang' && !targetCustomerId && !targetCustomerName) {
    snackbar.show('Mohon pilih atau ketik nama pelanggan! (Wajib diisi untuk transaksi utang/tempo)', 'warning')
    
    return
  }

  // If customer name is typed but ID is not resolved, check if already in list or auto-create in DB
  if (!targetCustomerId && targetCustomerName) {
    const existing = customers.value.find(c => c.name && c.name.toLowerCase() === targetCustomerName.toLowerCase())
    if (existing) {
      targetCustomerId = existing.id
      customerId.value = existing.id
      selectedCustomer.value = existing.id
    } else {
      try {
        const resCust = await $api('/apps/customers', {
          method: 'POST',
          body: {
            name: targetCustomerName,
            is_active: true,
          },
        })
        if (resCust.customer && resCust.customer.id) {
          targetCustomerId = resCust.customer.id
          customers.value.unshift(resCust.customer)
          selectedCustomer.value = resCust.customer.id
          customerId.value = resCust.customer.id
          snackbar.show(`Pelanggan "${targetCustomerName}" berhasil tersimpan ke database`, 'success')
        }
      } catch (e) {
        console.warn('Auto-create customer error:', e)
      }
    }
  } else if (targetCustomerId) {
    customerId.value = targetCustomerId
  } else {
    customerId.value = null
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
  if (customerSearch.value || (typeof selectedCustomer.value === 'string' && selectedCustomer.value)) {
    const nameToSend = customerSearch.value || selectedCustomer.value
    formData.append('customer_name', typeof nameToSend === 'string' ? nameToSend : (nameToSend.name || ''))
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

const selectedPrintFormat = ref('continuous_form')
const receiptPrinterRef = ref(null)

const printReceipt = () => {
  if (receiptPrinterRef.value?.print) {
    receiptPrinterRef.value.print()
  } else {
    setTimeout(() => {
      window.print()
    }, 100)
  }
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
      
      <div class="d-flex align-center gap-3">
        <!-- Held Bills Button -->
        <VBtn
          variant="tonal"
          :color="heldBills.length > 0 ? 'warning' : 'secondary'"
          density="compact"
          prepend-icon="ri-pause-circle-line"
          class="font-weight-bold shadow-sm"
          @click="isHeldBillsDialogOpen = true"
        >
          Ditahan ({{ heldBills.length }})
        </VBtn>

        <!-- Cash Shift Button -->
        <VBtn
          v-if="hasActiveShift"
          variant="tonal"
          color="success"
          density="compact"
          prepend-icon="ri-time-line"
          class="font-weight-bold"
          @click="openCloseShiftDialog"
        >
          Shift Kasir: Aktif
        </VBtn>
        <VBtn
          v-else
          variant="elevated"
          color="error"
          density="compact"
          prepend-icon="ri-lock-unlock-line"
          class="font-weight-bold"
          @click="isStartShiftDialogOpen = true"
        >
          Buka Shift Kasir
        </VBtn>

        <!-- Branch Selector -->
        <VAutocomplete
          v-model="activeBranchId"
          :items="branches"
          item-title="name"
          item-value="id"
          density="compact"
          hide-details
          style="width: 220px;"
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
          <VCardText class="pb-3">
            <VRow>
              <VCol
                cols="12"
                sm="6"
              >
                <VTextField
                  ref="searchInputRef"
                  v-model="search"
                  placeholder="Cari nama produk, SKU, barcode (Tekan F1)..."
                  density="compact"
                  prepend-inner-icon="ri-search-line"
                  hide-details
                  clearable
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
                  placeholder="Pilih Kategori Produk"
                  density="compact"
                  clearable
                  hide-details
                />
              </VCol>
            </VRow>

            <!-- Quick Category Filter with Left/Right Arrows (No Scrollbar) -->
            <VSlideGroup
              show-arrows
              class="pt-2"
            >
              <VSlideGroupItem>
                <VChip
                  size="small"
                  :color="!selectedCategory ? 'primary' : 'secondary'"
                  :variant="!selectedCategory ? 'elevated' : 'tonal'"
                  class="ma-1 cursor-pointer font-weight-bold"
                  @click="selectedCategory = null"
                >
                  <VIcon icon="ri-apps-line" size="14" class="me-1" />
                  Semua Kategori
                </VChip>
              </VSlideGroupItem>
              <VSlideGroupItem
                v-for="cat in categories"
                :key="cat.id"
              >
                <VChip
                  size="small"
                  :color="selectedCategory === cat.id ? 'primary' : 'secondary'"
                  :variant="selectedCategory === cat.id ? 'elevated' : 'tonal'"
                  class="ma-1 cursor-pointer font-weight-medium"
                  @click="selectedCategory = selectedCategory === cat.id ? null : cat.id"
                >
                  {{ cat.name }}
                </VChip>
              </VSlideGroupItem>
            </VSlideGroup>
          </VCardText>
          
          <VDivider />

          <VCardText class="flex-grow-1 bg-var-theme-background pa-3 d-flex flex-column justify-space-between">
            <div
              v-if="isLoading"
              class="d-flex justify-center align-center h-100 py-12"
            >
              <VProgressCircular
                indeterminate
                color="primary"
                size="48"
              />
            </div>
            
            <div v-else-if="products.length > 0" class="d-flex flex-column flex-grow-1">
              <!-- Scrollable Product Grid (6 Items per page) -->
              <div class="product-scroll-area overflow-y-auto pe-1 flex-grow-1" style="max-height: calc(100vh - 320px); min-height: 400px;">
                <VRow class="g-3">
                  <VCol
                    v-for="item in products"
                    :key="item.id"
                    cols="12"
                    sm="6"
                    md="4"
                  >
                    <VCard 
                      class="h-100 cursor-pointer product-card transition-all rounded-lg overflow-hidden position-relative"
                      :class="{'opacity-50 pointer-events-none': item.stock <= 0}"
                      elevation="2"
                      hover
                      @click="item.stock > 0 ? handleProductClick(item) : null"
                    >
                      <!-- Stock Method Badge Top Left -->
                      <div class="position-absolute top-0 start-0 pa-2 z-index-1">
                        <VChip
                          v-if="item.product?.stock_method"
                          size="x-small"
                          :color="item.product.stock_method === 'FEFO' ? 'error' : (item.product.stock_method === 'LIFO' ? 'info' : 'primary')"
                          variant="elevated"
                          class="font-weight-bold shadow-sm"
                        >
                          {{ item.product.stock_method }}
                        </VChip>
                      </div>

                      <!-- Product Image or Modern Placeholder -->
                      <div
                        class="d-flex justify-center align-center bg-primary-lighten-5 position-relative"
                        style="height: 125px; overflow: hidden;"
                      >
                        <VImg
                          v-if="item.product?.image"
                          :src="`/storage/${item.product.image}`"
                          cover
                          height="125"
                        />
                        <div v-else class="text-center">
                          <VAvatar color="primary" variant="tonal" size="52" rounded="lg">
                            <VIcon
                              icon="ri-box-3-line"
                              size="30"
                              color="primary"
                            />
                          </VAvatar>
                        </div>
                      </div>

                      <VCardText class="pa-3 text-center">
                        <h6
                          class="text-subtitle-2 font-weight-bold mb-1 text-truncate"
                          :title="item.product?.name"
                        >
                          {{ item.product?.name }}
                        </h6>
                        
                        <div class="d-flex justify-center align-center gap-1 text-caption text-disabled mb-2">
                          <span>{{ item.product?.sku || 'NO-SKU' }}</span>
                          <span v-if="item.product?.unit">• {{ item.product.unit }}</span>
                        </div>

                        <div class="text-primary font-weight-bold text-subtitle-1 mb-1">
                          {{ formatRupiah(item.price) }}
                        </div>

                        <div
                          v-if="item.min_nego_price > 0 && item.min_nego_price < item.price"
                          class="text-caption text-warning mb-2"
                        >
                          Min. Nego: {{ formatRupiah(item.min_nego_price) }}
                        </div>

                        <VChip
                          size="x-small"
                          :color="item.stock > 3 ? 'success' : (item.stock > 0 ? 'warning' : 'error')"
                          :variant="item.stock > 3 ? 'tonal' : 'elevated'"
                          class="font-weight-bold"
                        >
                          <VIcon
                            v-if="item.stock <= 3 && item.stock > 0"
                            icon="ri-alert-line"
                            size="12"
                            class="me-1"
                          />
                          {{ item.stock > 3 ? `Stok: ${item.stock} ${item.product?.unit || ''}` : (item.stock > 0 ? `Sisa ${item.stock}` : 'Habis (0)') }}
                        </VChip>
                      </VCardText>
                    </VCard>
                  </VCol>
                </VRow>
              </div>

              <!-- Pinned Bottom Pagination -->
              <div class="d-flex justify-center pt-3 border-t mt-2">
                <VPagination
                  v-model="page"
                  :length="totalPages"
                  :total-visible="5"
                  rounded="circle"
                />
              </div>
            </div>
            
            <div
              v-else
              class="d-flex flex-column justify-center align-center h-100 text-disabled py-12"
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

      <!-- Kanan: Keranjang (Cart) Sticky & Compact -->
      <VCol
        cols="12"
        md="4"
        class="d-flex flex-column"
        style="position: sticky; top: 16px; z-index: 10;"
      >
        <VCard class="d-flex flex-column rounded-lg overflow-hidden border elevation-2">
          <VCardItem class="bg-primary text-white py-2 px-4">
            <VCardTitle class="text-white d-flex align-center justify-space-between text-subtitle-1">
              <div class="d-flex align-center font-weight-bold">
                <VIcon
                  icon="ri-shopping-cart-2-line"
                  class="me-2"
                />
                Keranjang Belanja
              </div>
              <div class="d-flex align-center gap-2">
                <VChip
                  v-if="heldBills.length > 0"
                  color="warning"
                  size="x-small"
                  variant="elevated"
                  class="font-weight-bold cursor-pointer"
                  title="Tekan F7 untuk membuka daftar antrean ditahan"
                  @click="isHeldBillsDialogOpen = true"
                >
                  <VIcon icon="ri-pause-line" size="12" class="me-1" />
                  {{ heldBills.length }} Ditahan (F7)
                </VChip>
                <VChip v-if="cart.length > 0" color="white" size="x-small" variant="elevated" class="text-primary font-weight-bold">
                  {{ cart.length }} Item
                </VChip>
              </div>
            </VCardTitle>
          </VCardItem>

          <!-- List Belanja Scrollable (Internal Scroll - Tidak Memanjang ke Bawah) -->
          <div
            class="overflow-y-auto pa-0 bg-surface"
            style="max-height: 230px; min-height: 120px;"
          >
            <VList
              v-if="cart.length > 0"
              lines="two"
              class="pa-0"
            >
              <template
                v-for="(item, index) in cart"
                :key="index"
              >
                <VListItem class="py-2 px-3">
                  <div class="d-flex justify-space-between w-100 mb-1">
                    <div
                      class="font-weight-bold text-truncate pe-2 text-body-2"
                      style="max-width: 75%;"
                    >
                      {{ item.name }}
                    </div>
                    <IconBtn
                      size="x-small"
                      color="error"
                      @click="removeFromCart(index)"
                    >
                      <VIcon icon="ri-delete-bin-line" size="18" />
                    </IconBtn>
                  </div>
                  
                  <div class="d-flex align-center justify-space-between gap-2">
                    <!-- Qty Control -->
                    <div
                      class="d-flex align-center border rounded pa-0"
                      style="width: 88px; height: 32px;"
                    >
                      <VBtn
                        size="x-small"
                        variant="text"
                        icon="ri-subtract-line"
                        @click="item.qty > 1 ? item.qty-- : null"
                      />
                      <div class="text-center flex-grow-1 font-weight-bold text-caption">
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
                    <div class="flex-grow-1 ms-2">
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
                    style="font-size: 11px;"
                  >
                    <VIcon
                      icon="ri-error-warning-line"
                      size="12"
                      class="me-1"
                    />
                    Di bawah batas nego ({{ formatRupiah(item.min_nego_price > 0 ? item.min_nego_price : item.original_price) }})!
                  </div>
                </VListItem>
                <VDivider v-if="index < cart.length - 1" />
              </template>
            </VList>
            
            <div
              v-else
              class="d-flex flex-column justify-center align-center h-100 text-disabled pa-6"
              style="min-height: 130px;"
            >
              <VIcon
                icon="ri-shopping-bag-3-line"
                size="40"
                class="mb-2"
                opacity="0.3"
              />
              <p class="text-caption mb-0">Keranjang belanja kosong</p>
            </div>
          </div>

          <VDivider />

          <!-- Checkout Summary -->
          <VCardText class="bg-var-theme-background pa-3">
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
              <div class="d-flex flex-column">
                <span class="text-body-1 font-weight-medium">Diskon Total</span>
                <span class="text-caption text-medium-emphasis" style="font-size: 11px;">Potongan faktur bon</span>
              </div>
              <div
                class="flex-grow-1 ms-8"
                style="max-width: 220px;"
              >
                <VTextField
                  :model-value="formatInputRupiah(discount)"
                  type="text"
                  density="compact"
                  hide-details
                  prefix="Rp"
                  placeholder="0"
                  class="text-right"
                  clearable
                  @update:model-value="val => discount = parseInputRupiah(val)"
                  @click:clear="discount = 0"
                />
              </div>
            </div>
            <VDivider class="mb-4" />
            <div class="d-flex justify-space-between align-center mb-6">
              <span class="text-h6 font-weight-bold">TOTAL</span>
              <span class="text-h5 font-weight-bold text-primary">{{ formatRupiah(totalAmount) }}</span>
            </div>
            
            <div class="d-flex gap-2">
              <VBtn
                variant="tonal"
                color="warning"
                size="large"
                style="width: 40%;"
                prepend-icon="ri-pause-line"
                :disabled="cart.length === 0"
                :loading="isHoldingBill"
                class="font-weight-bold"
                @click="holdCurrentBill"
              >
                Hold (F6)
              </VBtn>
              <VBtn
                color="primary"
                size="large"
                class="flex-grow-1 font-weight-bold"
                prepend-icon="ri-bank-card-line"
                :disabled="cart.length === 0"
                @click="handleCheckoutClick"
              >
                Bayar (F8)
              </VBtn>
            </div>
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

          <!-- Customer Selection (Optional for regular, Mandatory for Utang) -->
          <div class="mt-4">
            <div class="d-flex align-center justify-space-between mb-1">
              <span class="text-caption font-weight-medium">
                Pelanggan
                <span :class="transactionType === 'utang' ? 'text-error font-weight-bold' : 'text-medium-emphasis'">
                  ({{ transactionType === 'utang' ? 'Wajib untuk Pembelian Utang' : 'Opsional' }})
                </span>
              </span>
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
            <VCombobox
              v-model="selectedCustomer"
              v-model:search="customerSearch"
              :items="customers"
              item-title="name"
              item-value="id"
              :placeholder="transactionType === 'utang' ? 'Pilih atau ketik nama pelanggan (Wajib)...' : 'Pilih atau ketik nama pelanggan (Opsional)...'"
              density="compact"
              clearable
              :loading="isSearchingCustomer"
              :error-messages="(transactionType === 'utang' && !selectedCustomer && !customerSearch) ? ['Pelanggan wajib dipilih / diketik untuk transaksi utang'] : []"
              class="mb-4"
              @update:search="onCustomerSearchInput"
            >
              <template #no-data>
                <div class="pa-2 text-caption text-medium-emphasis">
                  <span v-if="customerSearch">
                    Pelanggan <b>"{{ customerSearch }}"</b> belum ada di daftar. Transaksi akan otomatis menyimpannya ke database.
                  </span>
                  <span v-else>
                    Ketik nama pelanggan untuk mencari atau menambah baru...
                  </span>
                </div>
              </template>
            </VCombobox>
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
              <!-- Quick Cash Buttons -->
              <div class="mb-3">
                <div class="text-caption text-medium-emphasis mb-2">Pilihan Nominal Cepat:</div>
                <div class="d-flex flex-wrap gap-2">
                  <VBtn
                    size="small"
                    variant="tonal"
                    color="primary"
                    prepend-icon="ri-check-double-line"
                    @click="setQuickCash('exact')"
                  >
                    Uang Pas ({{ formatRupiah(totalAmount) }})
                  </VBtn>
                  <VBtn
                    v-for="nom in quickCashSuggestions"
                    :key="nom"
                    size="small"
                    variant="outlined"
                    color="secondary"
                    @click="setQuickCash(nom)"
                  >
                    {{ formatRupiah(nom) }}
                  </VBtn>
                </div>
              </div>

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
          
          <div class="mb-4 text-start">
            <label class="text-caption font-weight-bold mb-1 d-block">Pilihan Format Printer:</label>
            <VSelect
              v-model="selectedPrintFormat"
              :items="[
                { title: 'Continuous Form (Dot Matrix - 9.5 x 5.5 Inch)', value: 'continuous_form' },
                { title: 'Struk Kasir Thermal (58mm / 80mm)', value: 'thermal' },
                { title: 'Kwitansi Formal (A4 / Folio)', value: 'kwitansi' }
              ]"
              density="compact"
              prepend-inner-icon="ri-printer-line"
              hide-details
            />
          </div>
          
          <VBtn
            color="primary"
            block
            size="large"
            class="mb-3 font-weight-bold"
            prepend-icon="ri-printer-line"
            @click="printReceipt"
          >
            Cetak Faktur / Struk
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
      ref="receiptPrinterRef"
      :sale="completedSaleData"
      :branch="branches.find(b => b.id === activeBranchId)" 
      :cashier-name="userData?.fullName || userData?.name || userData?.username"
      :print-format="selectedPrintFormat"
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

    <!-- ================= 1. DIALOG BUKA SHIFT KASIR ================= -->
    <VDialog
      v-model="isStartShiftDialogOpen"
      max-width="450"
      :persistent="!hasActiveShift"
    >
      <VCard>
        <VCardTitle class="bg-primary text-white pa-4 d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-time-line" />
            <span class="font-weight-bold">Buka Shift Kasir Baru</span>
          </div>
          <VBtn
            v-if="hasActiveShift"
            icon="ri-close-line"
            variant="text"
            size="small"
            @click="isStartShiftDialogOpen = false"
          />
        </VCardTitle>

        <VCardText class="pa-5">
          <VAlert
            type="info"
            variant="tonal"
            class="mb-4 text-caption"
          >
            Silakan masukkan <strong>Kas Awal (Uang Modal Kembalian di Laci)</strong> untuk memulai operasional kasir hari ini.
          </VAlert>

          <VTextField
            :model-value="startCashInput"
            label="Modal Kas Awal (Rp) *"
            prefix="Rp"
            placeholder="0"
            variant="outlined"
            density="compact"
            class="mb-2"
            autofocus
            @update:model-value="val => startCashInput = formatInputRupiah(val)"
          />
        </VCardText>

        <VCardActions class="pa-4 pt-0 justify-end">
          <VBtn
            color="primary"
            class="font-weight-bold"
            block
            size="large"
            :loading="isSubmittingShift"
            @click="openShift"
          >
            Buka Shift & Mulai Transaksi
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ================= 2. DIALOG TUTUP SHIFT KASIR ================= -->
    <VDialog
      v-model="isCloseShiftDialogOpen"
      max-width="500"
    >
      <VCard>
        <VCardTitle class="bg-warning-darken-1 text-white pa-4 d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-door-lock-line" />
            <span class="font-weight-bold">Tutup Shift Kasir (Reconciliation)</span>
          </div>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="isCloseShiftDialogOpen = false" />
        </VCardTitle>

        <VCardText class="pa-5">
          <div v-if="shiftSummary" class="mb-4 pa-3 rounded border bg-surface">
            <div class="d-flex justify-space-between py-1 text-body-2">
              <span class="text-medium-emphasis">Modal Kas Awal:</span>
              <span class="font-weight-bold">{{ formatRupiah(shiftSummary.start_cash) }}</span>
            </div>
            <div class="d-flex justify-space-between py-1 text-body-2">
              <span class="text-medium-emphasis">Total Penjualan Tunai:</span>
              <span class="font-weight-bold text-success">+ {{ formatRupiah(shiftSummary.total_cash_sales) }}</span>
            </div>
            <div class="d-flex justify-space-between py-1 text-body-2">
              <span class="text-medium-emphasis">Pengeluaran Kas Kecil:</span>
              <span class="font-weight-bold text-error">- {{ formatRupiah(shiftSummary.total_expenses) }}</span>
            </div>
            <VDivider class="my-2" />
            <div class="d-flex justify-space-between py-1 text-body-1">
              <span class="font-weight-bold">Ekspektasi Kas di Laci:</span>
              <span class="font-weight-bold text-primary">{{ formatRupiah(shiftSummary.expected_cash) }}</span>
            </div>
          </div>

          <VTextField
            :model-value="actualCashInput"
            label="Uang Fisik Kasir di Laci (Rp) *"
            prefix="Rp"
            placeholder="0"
            variant="outlined"
            density="compact"
            class="mb-3"
            @update:model-value="val => actualCashInput = formatInputRupiah(val)"
          />

          <div
            v-if="actualCashInput && shiftSummary"
            class="pa-3 mb-3 rounded border text-caption"
            :class="parseInputRupiah(actualCashInput) - shiftSummary.expected_cash === 0 ? 'bg-success-lighten-5 text-success' : 'bg-warning-lighten-5 text-warning-darken-2'"
          >
            <div class="font-weight-bold">
              Selisih Fisik vs Sistem: {{ formatRupiah(parseInputRupiah(actualCashInput) - shiftSummary.expected_cash) }}
              <span v-if="parseInputRupiah(actualCashInput) - shiftSummary.expected_cash === 0">(KAS COCOK / BALANCE)</span>
              <span v-else-if="parseInputRupiah(actualCashInput) - shiftSummary.expected_cash > 0">(SURPLUS KAS)</span>
              <span v-else>(DEFISIT / KAS KURANG)</span>
            </div>
          </div>

          <VTextarea
            v-model="closeShiftNotes"
            label="Catatan Tutup Shift (Opsional)"
            placeholder="Contoh: Kas fisik cocok, sudah diserahterimakan ke bendahara."
            rows="2"
            variant="outlined"
            density="compact"
          />

          <!-- Opsi Setor Pengembalian Modal ke Owner -->
          <VDivider class="my-3" />
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption font-weight-bold text-uppercase text-primary">
              <VIcon icon="ri-arrow-go-back-line" size="14" class="me-1" />
              Setor Pengembalian Modal ke Owner
            </span>
            <VSwitch
              v-model="isDepositingCapitalReturn"
              density="compact"
              color="primary"
              hide-details
            />
          </div>

          <div v-if="isDepositingCapitalReturn" class="pa-3 mb-3 rounded border bg-var-theme-background">
            <VTextField
              :model-value="capitalReturnAmountInput"
              label="Nominal Setor ke Owner (Rp) *"
              prefix="Rp"
              placeholder="0"
              variant="outlined"
              density="compact"
              class="mb-2"
              @update:model-value="val => capitalReturnAmountInput = formatInputRupiah(val)"
            />
            <VTextField
              v-model="capitalReturnBankName"
              label="Nama Bank Rekening Owner"
              placeholder="BCA / Mandiri"
              variant="outlined"
              density="compact"
              class="mb-2"
            />
            <VFileInput
              v-model="capitalReturnProofFile"
              label="Lampirkan Struk / Bukti Transfer"
              variant="outlined"
              density="compact"
              prepend-icon=""
              prepend-inner-icon="ri-attachment-line"
              accept="image/*,application/pdf"
            />
          </div>
        </VCardText>

        <VCardActions class="pa-4 pt-0 justify-end gap-2">
          <VBtn variant="tonal" color="secondary" @click="isCloseShiftDialogOpen = false">
            Batal
          </VBtn>
          <VBtn
            color="warning"
            class="font-weight-bold"
            :loading="isSubmittingShift"
            @click="submitCloseShift"
          >
            Konfirmasi & Tutup Shift
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- ================= 3. DIALOG TRANSAKSI DITAHAN (HELD BILLS) ================= -->
    <VDialog
      v-model="isHeldBillsDialogOpen"
      max-width="650"
    >
      <VCard>
        <VCardTitle class="bg-warning text-white pa-4 d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-2">
            <VIcon icon="ri-pause-circle-line" />
            <span class="font-weight-bold">Daftar Transaksi Ditahan (Pending Bills)</span>
          </div>
          <VBtn icon="ri-close-line" variant="text" size="small" @click="isHeldBillsDialogOpen = false" />
        </VCardTitle>

        <VCardText class="pa-4">
          <p class="text-caption text-medium-emphasis mb-3">
            Pilih transaksi yang ingin dimuat kembali (resume) ke keranjang belanja:
          </p>

          <VList lines="two" border rounded>
            <template v-for="(bill, index) in heldBills" :key="bill.id">
              <VListItem class="py-2">
                <template #prepend>
                  <VAvatar color="warning" variant="tonal" class="me-3">
                    <VIcon icon="ri-shopping-basket-line" />
                  </VAvatar>
                </template>

                <VListItemTitle class="font-weight-bold">
                  {{ bill.customer_name || 'Pelanggan Walk-In' }}
                  <span class="text-caption text-disabled ms-2">({{ bill.items_json?.length || 0 }} Item)</span>
                </VListItemTitle>
                <VListItemSubtitle class="mt-1">
                  Total: <strong class="text-primary">{{ formatRupiah(bill.total) }}</strong> • Ditahan: {{ bill.created_at }}
                </VListItemSubtitle>

                <template #append>
                  <div class="d-flex gap-2">
                    <VBtn
                      size="small"
                      color="primary"
                      prepend-icon="ri-arrow-right-line"
                      class="font-weight-bold"
                      @click="resumeHeldBill(bill)"
                    >
                      Ambil
                    </VBtn>
                    <IconBtn
                      size="small"
                      color="error"
                      @click="deleteHeldBill(bill.id)"
                    >
                      <VIcon icon="ri-delete-bin-line" size="18" />
                    </IconBtn>
                  </div>
                </template>
              </VListItem>
              <VDivider v-if="index < heldBills.length - 1" />
            </template>
          </VList>

          <div v-if="heldBills.length === 0" class="text-center py-6 text-disabled">
            <VIcon icon="ri-inbox-line" size="36" class="mb-2" />
            <p class="mb-0">Tidak ada transaksi yang sedang ditahan.</p>
          </div>
        </VCardText>

        <VCardActions class="pa-4 pt-0 justify-end">
          <VBtn variant="tonal" color="secondary" @click="isHeldBillsDialogOpen = false">
            Tutup
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
