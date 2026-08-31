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
const isMobileCartDrawerVisible = ref(false)
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
const bankAccounts = ref([])
const selectedBankAccountId = ref(null)

const selectedBankAccount = computed(() => {
  return bankAccounts.value.find(b => b.id === selectedBankAccountId.value) || null
})

const transferBankAccounts = computed(() => {
  return bankAccounts.value.filter(b => b.type === 'bank_transfer' || b.type === 'edc_debit' || b.type === 'edc_credit')
})

const qrisBankAccounts = computed(() => {
  return bankAccounts.value.filter(b => b.type === 'qris')
})

const fetchBankAccounts = async () => {
  try {
    const res = await $api('/apps/bank-accounts', {
      params: {
        is_active: true,
        branch_id: activeBranchId.value || undefined,
      },
    })
    bankAccounts.value = res.data || []
    if (bankAccounts.value.length > 0) {
      if (!selectedBankAccountId.value) {
        const defaultBank = bankAccounts.value.find(b => b.is_default) || bankAccounts.value[0]
        if (defaultBank) {
          selectBankAccount(defaultBank)
        }
      }
    }
  } catch (e) {
    console.error('Failed to fetch bank accounts in POS:', e)
  }
}

const selectBankAccount = bank => {
  if (!bank) return
  selectedBankAccountId.value = bank.id
  bankName.value = bank.bank_name
  bankAccountNumber.value = bank.account_number || ''
  bankAccountName.value = bank.account_name || ''
}

const copyToClipboard = (text, label) => {
  if (!text) return
  navigator.clipboard.writeText(text)
  snackbar.show(`${label || 'Nomor rekening'} berhasil disalin!`, 'success')
}

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
    
    // Auto-select format based on active default receipt setting from pengaturan-struk
    if (activeReceiptSetting.value) {
      const w = String(activeReceiptSetting.value.width || '').toLowerCase()
      const n = String(activeReceiptSetting.value.name || '').toLowerCase()
      if (w.includes('58') || w.includes('80') || n.includes('thermal')) {
        selectedPrintFormat.value = 'thermal'
      } else if (w.includes('148') || n.includes('kwitansi') || n.includes('kuitansi') || n.includes('a5')) {
        selectedPrintFormat.value = 'kwitansi'
      } else {
        selectedPrintFormat.value = 'continuous_form'
      }
    }
    
    await Promise.all([
      checkCurrentShift(),
      fetchHeldBills(),
      fetchBankAccounts(),
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

const totalProducts = ref(0)
const perPage = ref(6)
const perPageOptions = [
  { title: '6 / halaman', value: 6 },
  { title: '12 / halaman', value: 12 },
  { title: '24 / halaman', value: 24 },
  { title: '48 / halaman', value: 48 },
]
const favoriteProductIds = ref(new Set())
const showOnlyFavorites = ref(false)

const toggleFavorite = (productId, event) => {
  if (event) event.stopPropagation()
  if (favoriteProductIds.value.has(productId)) {
    favoriteProductIds.value.delete(productId)
  } else {
    favoriteProductIds.value.add(productId)
  }
}

const clearCart = () => {
  cart.value = []
  discount.value = 0
  snackbar.show('Keranjang berhasil dikosongkan', 'info')
}

const fetchProducts = async (branchId, resetPage = false) => {
  if (!branchId) return
  if (resetPage) page.value = 1
  isLoading.value = true
  try {
    let url = `/apps/product-branches?branch_id=${branchId}&paginate=true&has_stock=true&per_page=${perPage.value}&page=${page.value}`
    if (search.value) url += `&search=${encodeURIComponent(search.value)}`
    if (selectedCategory.value) url += `&category_id=${selectedCategory.value}`
    
    const data = await $api(url)
    const list = data.data || (Array.isArray(data) ? data : [])

    products.value = Array.isArray(list) ? list : []
    totalProducts.value = Number(data.total) || (Array.isArray(list) ? list.length : 0)
    totalPages.value = Number(data.last_page) || 1
  } catch (error) {
    console.error('Error fetching POS products:', error)
    snackbar.show('Gagal mengambil data produk', 'error')
  } finally {
    isLoading.value = false
  }
}

watch(perPage, () => {
  fetchProducts(activeBranchId.value, true)
})

const searchInputRef = ref(null)

const focusSearchInput = () => {
  if (searchInputRef.value) {
    const inputEl = searchInputRef.value.$el ? searchInputRef.value.$el.querySelector('input') : searchInputRef.value
    if (inputEl && inputEl.focus) {
      inputEl.focus()
      inputEl.select?.()
    }
  }
}

const handleSearchEnter = async () => {
  const query = (search.value || '').trim()
  if (!query) return

  // 1. If currently loaded products has an exact match by SKU, barcode, or name
  if (products.value.length > 0) {
    const exactMatch = products.value.find(p => 
      (p.product?.sku && p.product.sku.toLowerCase() === query.toLowerCase()) ||
      (p.product?.barcode && p.product.barcode.toLowerCase() === query.toLowerCase()) ||
      (p.product?.name && p.product.name.toLowerCase() === query.toLowerCase())
    ) || (products.value.length === 1 ? products.value[0] : null)

    if (exactMatch) {
      if (exactMatch.stock > 0) {
        await handleProductClick(exactMatch)
        search.value = ''
        snackbar.show(`"${exactMatch.product?.name}" ditambahkan ke keranjang`, 'success')
      } else {
        snackbar.show(`Stok produk "${exactMatch.product?.name}" habis`, 'warning')
      }
      return
    }
  }

  // 2. Query backend directly for barcode / SKU
  try {
    isLoading.value = true
    const res = await $api(`/apps/product-branches?branch_id=${activeBranchId.value}&search=${encodeURIComponent(query)}&has_stock=true`)
    const list = res.data || (Array.isArray(res) ? res : [])
    if (list.length > 0) {
      const match = list.find(p => 
        (p.product?.sku && p.product.sku.toLowerCase() === query.toLowerCase()) ||
        (p.product?.barcode && p.product.barcode.toLowerCase() === query.toLowerCase())
      ) || list[0]

      if (match && match.stock > 0) {
        await handleProductClick(match)
        search.value = ''
        snackbar.show(`"${match.product?.name}" ditambahkan ke keranjang`, 'success')
      } else {
        snackbar.show('Stok produk tidak tersedia', 'warning')
      }
    } else {
      snackbar.show('Produk tidak ditemukan', 'warning')
    }
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

const handleKeyDown = event => {
  const isFunctionKey = ['F1', 'F2', 'F3', 'F4', 'F6', 'F7', 'F8', 'F9', 'F10', 'F11', 'Escape'].includes(event.key)
  
  if (isFunctionKey) {
    event.preventDefault()
    event.stopPropagation()
  }

  // F11 / F10: Toggle Layar Penuh (Fullscreen)
  if (event.key === 'F11' || event.key === 'F10') {
    toggleFullscreen()
  }
  // F1 / F2: Cari Produk
  else if (event.key === 'F1' || event.key === 'F2') {
    focusSearchInput()
  } 
  // F4: Scan Barcode
  else if (event.key === 'F4') {
    search.value = ''
    focusSearchInput()
    snackbar.show('Silakan scan barcode atau masukkan SKU', 'info')
  }
  // F3: Pilih / Tambah Pelanggan
  else if (event.key === 'F3') {
    isAddCustomerDrawerVisible.value = !isAddCustomerDrawerVisible.value
  } 
  // F6: Hold / Tahan Transaksi Saat Ini
  else if (event.key === 'F6') {
    if (cart.value.length > 0) {
      if (!isCheckoutDialogVisible.value && !isHoldingBill.value) {
        holdCurrentBill()
      }
    } else {
      snackbar.show('Keranjang masih kosong untuk ditahan', 'warning')
    }
  }
  // F7: Buka / Lihat Daftar Transaksi Ditahan (Held Bills)
  else if (event.key === 'F7') {
    isHeldBillsDialogOpen.value = !isHeldBillsDialogOpen.value
    if (isHeldBillsDialogOpen.value) {
      fetchHeldBills()
    }
  }
  // F8: Checkout / Bayar
  else if (event.key === 'F8') {
    if (cart.value.length > 0) {
      if (!isCheckoutDialogVisible.value) {
        handleCheckoutClick()
      }
    } else {
      snackbar.show('Keranjang belanja masih kosong', 'warning')
    }
  } 
  // F9: Uang Pas
  else if (event.key === 'F9') {
    if (isCheckoutDialogVisible.value) {
      setQuickCash('exact')
    }
  } 
  // Escape: Batal / Tutup Dialog / Keluar
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
    } else if (cart.value.length > 0) {
      clearCart()
    } else {
      router.push({ name: 'dashboards-analytics' })
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
  document.addEventListener('fullscreenchange', onFullscreenChange)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
  document.removeEventListener('fullscreenchange', onFullscreenChange)
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
    // Robust pricing fallback: Priority batch price (>0) -> active batch price (>0) -> branch price -> product price
    const branchSellingPrice = Number(productBranch.price) || Number(productBranch.selling_price) || Number(productBranch.product?.price) || 0
    const batchSellingPrice = (batch && Number(batch.price) > 0) ? Number(batch.price) : 0
    const activeBatchSellingPrice = (productBranch.active_batch && Number(productBranch.active_batch.price) > 0) ? Number(productBranch.active_batch.price) : 0
    const sellingPrice = batchSellingPrice > 0 ? batchSellingPrice : (activeBatchSellingPrice > 0 ? activeBatchSellingPrice : branchSellingPrice)

    const branchMinNego = Number(productBranch.min_nego_price) || 0
    const batchMinNego = (batch && Number(batch.min_nego_price) > 0) ? Number(batch.min_nego_price) : 0
    const activeBatchMinNego = (productBranch.active_batch && Number(productBranch.active_batch.min_nego_price) > 0) ? Number(productBranch.active_batch.min_nego_price) : 0
    const minNego = batchMinNego > 0 ? batchMinNego : (activeBatchMinNego > 0 ? activeBatchMinNego : branchMinNego)

    const costPrice = (batch && Number(batch.cost_price) > 0) 
      ? Number(batch.cost_price) 
      : (Number(productBranch.cost_price) || 0)

    if (availableStock <= 3) {
      snackbar.show(`Peringatan: Stok ${productBranch.product?.name || 'produk'} menipis (Sisa: ${availableStock})`, 'warning')
    }

    cart.value.push({
      product_branch_id: productBranch.id,
      batch_id: batch ? batch.id : null,
      name: (productBranch.product?.name || productBranch.name || 'Produk') + (batch ? ` (Batch #${batch.id || batch.batch_number})` : ''),
      sku: productBranch.product?.sku || productBranch.sku || '',
      unit: productBranch.product?.unit || productBranch.unit || 'PCS',
      qty: 1,
      max_stock: availableStock,
      cost_price: Math.round(costPrice),
      min_nego_price: Math.round(minNego),
      original_price: Math.round(sellingPrice),
      price: Math.round(sellingPrice),
      tax_percentage: Number(productBranch.tax_percentage) || 0,
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

const discountType = ref('rupiah') // 'rupiah' | 'percent'
const discountPercentInput = ref(0)
const discountRupiahInput = ref(0)

const discountNominal = computed(() => {
  if (discountType.value === 'percent') {
    const p = Math.min(100, Math.max(0, Number(discountPercentInput.value) || 0))
    return Math.round((subtotal.value * p) / 100)
  }
  return Math.min(subtotal.value, Number(discountRupiahInput.value) || 0)
})

watch(discountNominal, val => {
  discount.value = val
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
  return Math.max(0, subtotal.value + totalTaxExclude.value - discountNominal.value)
})

// Fullscreen API Handling
const isFullscreen = ref(false)

const toggleFullscreen = () => {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(err => console.warn(err))
    isFullscreen.value = true
  } else {
    if (document.exitFullscreen) document.exitFullscreen()
    isFullscreen.value = false
  }
}

const onFullscreenChange = () => {
  isFullscreen.value = !!document.fullscreenElement
}

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
  } else {
    // Lunas
    if (paymentMethod.value === 'cash') {
      if (!paidAmountRaw.value || paidAmountRaw.value < totalAmount.value) {
        snackbar.show('Uang bayar tidak boleh kurang dari total tagihan!', 'warning')
        
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
    } else if (paymentMethod.value === 'transfer' || paymentMethod.value === 'qris') {
      if (selectedBankAccountId.value) {
        formData.append('bank_account_id', selectedBankAccountId.value)
      }
      formData.append('bank_name', bankName.value)
      formData.append('bank_account_number', bankAccountNumber.value)
      formData.append('bank_account_name', bankAccountName.value)
      if (transferPhoneNumber.value) formData.append('transfer_phone_number', transferPhoneNumber.value)
      if (paymentProof.value && paymentProof.value.length > 0) {
        formData.append('payment_proof', paymentProof.value[0])
      }
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
  <div class="pos-root-container">
    <!-- Top Header Bar -->
    <header class="pos-header-bar">
      <div class="d-flex align-center">
        <VAvatar
          color="primary"
          rounded="lg"
          size="36"
          class="me-2 shadow-xs"
        >
          <VIcon icon="ri-store-3-line" size="20" color="white" />
        </VAvatar>
        <div class="text-subtitle-1 font-weight-bold text-slate-800">
          Kasir (POS) <span class="text-primary font-weight-medium">- {{ userData?.fullName || userData?.name || userData?.username || 'Developer' }}</span>
        </div>
      </div>
      
      <div class="d-flex align-center gap-2">
        <!-- Status Shift Pill -->
        <button
          v-if="hasActiveShift"
          type="button"
          class="pos-top-pill pos-pill-success"
          @click="openCloseShiftDialog"
        >
          <span class="status-dot-green"></span>
          Shift: Aktif
        </button>
        <button
          v-else
          type="button"
          class="pos-top-pill pos-pill-error"
          @click="isStartShiftDialogOpen = true"
        >
          <VIcon icon="ri-lock-unlock-line" size="14" class="me-1" />
          Buka Shift
        </button>

        <!-- Held Bills Pill -->
        <button
          type="button"
          class="pos-top-pill"
          @click="isHeldBillsDialogOpen = true"
        >
          <VIcon icon="ri-time-line" size="15" class="me-1 text-slate-500" />
          Ditahan ({{ heldBills.length }})
        </button>

        <!-- Fullscreen Button -->
        <button
          type="button"
          class="pos-top-pill"
          :title="isFullscreen ? 'Keluar Layar Penuh (F11)' : 'Layar Penuh (F11)'"
          @click="toggleFullscreen"
        >
          <VIcon :icon="isFullscreen ? 'ri-fullscreen-exit-line' : 'ri-fullscreen-line'" size="16" class="text-primary" />
          <span class="d-none d-md-inline">{{ isFullscreen ? 'Keluar Fullscreen' : 'Fullscreen' }}</span>
        </button>

        <!-- Branch Selector -->
        <VAutocomplete
          v-model="activeBranchId"
          :items="branches"
          item-title="name"
          item-value="id"
          density="compact"
          variant="outlined"
          hide-details
          style="width: 190px;"
          prepend-inner-icon="ri-store-2-line"
          bg-color="surface"
          class="pos-branch-select"
        />

        <!-- Menu Action -->
        <VMenu>
          <template #activator="{ props }">
            <VBtn
              icon="ri-more-2-fill"
              variant="text"
              size="small"
              v-bind="props"
            />
          </template>
          <VList density="compact">
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
    </header>

    <!-- Main Content Area -->
    <main class="pos-main-content">
      <!-- Left: Product Catalog -->
      <section class="pos-catalog-section">
        <div class="pos-catalog-box">
          <!-- Search & Scan Bar -->
          <div class="pos-catalog-header">
            <div class="d-flex align-center gap-3 mb-2">
              <div class="pos-search-wrapper flex-grow-1">
                <VIcon icon="ri-search-line" size="18" class="pos-search-icon" />
                <input
                  ref="searchInputRef"
                  v-model="search"
                  type="text"
                  placeholder="Cari produk, SKU, barcode..."
                  class="pos-search-input"
                  @keydown.enter="handleSearchEnter"
                />
                <span class="pos-kbd-badge">F1</span>
              </div>

              <button
                type="button"
                class="pos-scan-btn"
                @click="focusSearchInput"
              >
                <VIcon icon="ri-qr-scan-2-line" size="18" class="me-1 text-primary" />
                Scan Barcode
                <span class="pos-kbd-badge ms-2">F4</span>
              </button>
            </div>

            <!-- Category Pills Row -->
            <div class="pos-category-row">
              <button
                type="button"
                class="pos-cat-pill"
                :class="{'pos-cat-pill-active': !selectedCategory}"
                @click="selectedCategory = null"
              >
                <VIcon icon="ri-apps-line" size="14" class="me-1" />
                Semua
              </button>

              <button
                v-for="cat in categories"
                :key="cat.id"
                type="button"
                class="pos-cat-pill"
                :class="{'pos-cat-pill-active': selectedCategory === cat.id}"
                @click="selectedCategory = selectedCategory === cat.id ? null : cat.id"
              >
                {{ cat.name }}
              </button>
            </div>
          </div>

          <!-- Product Catalog Grid -->
          <div class="pos-catalog-body">
            <div
              v-if="isLoading"
              class="d-flex justify-center align-center h-100 py-12"
            >
              <VProgressCircular
                indeterminate
                color="primary"
                size="40"
              />
            </div>
            
            <div
              v-else-if="products.length > 0"
              class="pos-product-grid"
            >
              <div
                v-for="item in products"
                :key="item.id"
                class="pos-product-card"
                :class="{'pos-card-disabled': item.stock <= 0}"
                @click="item.stock > 0 ? handleProductClick(item) : null"
              >
                <!-- Card Top: Soft Image/Icon + Stock Method Badge (FIFO / LIFO / FEFO) -->
                <div class="d-flex align-center justify-space-between w-100 mb-2">
                  <div class="pos-card-icon-box">
                    <img
                      v-if="item.product?.image"
                      :src="`/storage/${item.product.image}`"
                      alt="Product"
                      class="pos-card-img"
                    />
                    <VIcon
                      v-else
                      icon="ri-box-3-line"
                      size="22"
                      color="primary"
                    />
                  </div>

                  <!-- Stock Method Badge from Database -->
                  <span
                    v-if="item.product?.stock_method"
                    class="pos-method-badge"
                    :class="(item.product.stock_method || '').toLowerCase()"
                  >
                    {{ item.product.stock_method }}
                  </span>
                  <span
                    v-else
                    class="pos-method-badge fifo"
                  >
                    FIFO
                  </span>
                </div>

                <!-- Product Name & SKU -->
                <div class="mb-2">
                  <h4 class="pos-card-title text-truncate" :title="item.product?.name">
                    {{ item.product?.name }}
                  </h4>
                  <div class="pos-card-sku text-truncate">
                    {{ item.product?.sku || 'NO-SKU' }} <span v-if="item.product?.unit">• {{ item.product.unit }}</span>
                  </div>
                </div>

                <!-- Price & Stock Badge -->
                <div>
                  <div class="pos-card-price mb-1">
                    {{ formatRupiah(item.price) }}
                  </div>

                  <div class="pos-stock-pill" :class="item.stock > 3 ? 'in-stock' : (item.stock > 0 ? 'low-stock' : 'out-of-stock')">
                    {{ item.stock > 3 ? `Stok: ${item.stock}` : (item.stock > 0 ? `Sisa ${item.stock}` : 'Habis') }}
                  </div>
                </div>
              </div>
            </div>
            
            <div
              v-else
              class="d-flex flex-column justify-center align-center h-100 text-disabled py-8"
            >
              <VIcon
                icon="ri-inbox-line"
                size="40"
                class="mb-2 text-slate-300"
              />
              <p class="mb-0 text-caption text-slate-500">Tidak ada produk tersedia di cabang ini.</p>
            </div>
          </div>

          <!-- Catalog Footer Bar -->
          <footer class="pos-catalog-footer">
            <div class="pos-footer-info">
              Menampilkan {{ products.length }} dari {{ totalProducts || products.length }} produk
            </div>

            <div class="pos-pagination-container">
              <VPagination
                v-model="page"
                :length="totalPages"
                :total-visible="$vuetify.display.xs ? 3 : 5"
                density="compact"
                size="small"
                active-color="primary"
                variant="flat"
              />
            </div>

            <div class="pos-perpage-select">
              <select v-model="perPage" class="pos-custom-select">
                <option :value="6">6 / halaman</option>
                <option :value="12">12 / halaman</option>
                <option :value="24">24 / halaman</option>
              </select>
            </div>
          </footer>
        </div>
      </section>

      <!-- Right: Cart Section -->
      <aside
        v-if="!$vuetify.display.xs"
        class="pos-cart-section"
      >
        <div class="pos-cart-box">
          <!-- Cart Header -->
          <div class="pos-cart-header">
            <div class="d-flex align-center">
              <VIcon icon="ri-shopping-cart-2-line" size="18" class="me-2 text-white" />
              <span class="font-weight-bold text-white text-subtitle-2 me-2">Keranjang Belanja</span>
              <span class="pos-cart-count-badge">{{ cart.length }}</span>
            </div>
            <button
              v-if="cart.length > 0"
              type="button"
              class="pos-cart-clear-btn"
              title="Kosongkan Keranjang"
              @click="clearCart"
            >
              <VIcon icon="ri-delete-bin-line" size="17" color="white" />
            </button>
          </div>

          <!-- Cart Items List Body -->
          <div class="pos-cart-body">
            <div v-if="cart.length > 0" class="pos-cart-items-list">
              <div
                v-for="(item, index) in cart"
                :key="index"
                class="pos-cart-item-row"
              >
                <div class="pos-cart-item-thumb">
                  <VIcon icon="ri-box-3-line" size="20" color="primary" />
                </div>

                <div class="flex-grow-1 overflow-hidden pe-2">
                  <div class="font-weight-bold text-slate-800 text-truncate text-caption">
                    {{ item.name }}
                  </div>
                  <div class="text-slate-400 text-xs text-truncate mb-1">
                    {{ item.sku || 'SKU' }} • {{ item.unit || 'PCS' }}
                  </div>
                  
                  <div class="d-flex align-center gap-2 mt-1">
                    <!-- Quantity Selector -->
                    <div class="pos-qty-group">
                      <button
                        type="button"
                        class="pos-qty-btn"
                        @click="item.qty > 1 ? item.qty-- : null"
                      >
                        <VIcon icon="ri-subtract-line" size="13" />
                      </button>
                      <span class="pos-qty-val">{{ item.qty }}</span>
                      <button
                        type="button"
                        class="pos-qty-btn"
                        @click="item.qty < item.max_stock ? item.qty++ : null"
                      >
                        <VIcon icon="ri-add-line" size="13" />
                      </button>
                    </div>

                    <!-- Editable Nego Price Input -->
                    <div class="pos-cart-price-input-wrapper">
                      <span class="pos-cart-price-prefix">Rp</span>
                      <input
                        :value="formatInputRupiah(item.price)"
                        type="text"
                        class="pos-cart-price-input"
                        placeholder="0"
                        title="Edit harga nego item"
                        @input="e => item.price = parseInputRupiah(e.target.value)"
                      />
                    </div>
                  </div>

                  <!-- Warning below minimum price limit -->
                  <div
                    v-if="Number(item.price) < Number(item.min_nego_price > 0 ? item.min_nego_price : item.original_price)"
                    class="text-caption text-error mt-1 d-flex align-center font-weight-medium"
                    style="font-size: 10px;"
                  >
                    <VIcon icon="ri-error-warning-line" size="11" class="me-1" />
                    Di bawah batas nego ({{ formatRupiah(item.min_nego_price > 0 ? item.min_nego_price : item.original_price) }})!
                  </div>
                </div>

                <div class="text-right d-flex flex-column justify-space-between align-end flex-shrink-0" style="min-height: 52px;">
                  <button
                    type="button"
                    class="pos-item-del-btn"
                    title="Hapus Item"
                    @click="removeFromCart(index)"
                  >
                    <VIcon icon="ri-delete-bin-line" size="16" color="error" />
                  </button>

                  <div class="font-weight-bold text-slate-800 text-caption mt-auto">
                    {{ formatRupiah(item.price * item.qty) }}
                  </div>
                </div>
              </div>
            </div>
            
            <div
              v-else
              class="d-flex flex-column justify-center align-center h-100 text-disabled pa-6"
            >
              <VIcon
                icon="ri-shopping-bag-3-line"
                size="44"
                class="mb-2 text-slate-300"
              />
              <p class="text-caption text-slate-400 mb-0 font-weight-medium">Keranjang belanja kosong</p>
            </div>
          </div>

          <!-- Checkout Summary & Action Buttons -->
          <div class="pos-cart-footer">
            <div class="d-flex justify-space-between align-center mb-1 text-caption text-slate-600">
              <span>Subtotal</span>
              <span class="font-weight-semibold text-slate-800">{{ formatRupiah(subtotal) }}</span>
            </div>

            <!-- Diskon with Type Toggle (Rp / %) -->
            <div class="d-flex justify-space-between align-center mb-1 text-caption text-slate-600">
              <div class="d-flex align-center">
                <span>Diskon</span>
                <div class="pos-discount-type-toggle ms-2">
                  <button
                    type="button"
                    class="pos-disc-btn"
                    :class="{'pos-disc-btn-active': discountType === 'rupiah'}"
                    title="Diskon Nominal Rupiah"
                    @click="discountType = 'rupiah'"
                  >
                    Rp
                  </button>
                  <button
                    type="button"
                    class="pos-disc-btn"
                    :class="{'pos-disc-btn-active': discountType === 'percent'}"
                    title="Diskon Persentase (%)"
                    @click="discountType = 'percent'"
                  >
                    %
                  </button>
                </div>
              </div>

              <div style="width: 140px;">
                <div v-if="discountType === 'rupiah'" class="pos-diskon-input-wrapper">
                  <span class="pos-diskon-prefix">Rp</span>
                  <input
                    :value="formatInputRupiah(discountRupiahInput)"
                    type="text"
                    placeholder="0"
                    class="pos-diskon-input"
                    @input="e => discountRupiahInput = parseInputRupiah(e.target.value)"
                  />
                </div>

                <div v-else class="pos-diskon-input-wrapper">
                  <input
                    :value="discountPercentInput"
                    type="number"
                    min="0"
                    max="100"
                    placeholder="0"
                    class="pos-diskon-input text-right"
                    @input="e => discountPercentInput = Number(e.target.value) || 0"
                  />
                  <span class="pos-diskon-suffix">%</span>
                </div>
              </div>
            </div>

            <!-- Discount Nominal Preview if Percent -->
            <div v-if="discountType === 'percent' && discountPercentInput > 0" class="text-right text-xs text-slate-500 mb-1" style="font-size: 10.5px;">
              Potongan: -{{ formatRupiah(discountNominal) }}
            </div>

            <div class="pos-summary-divider my-2"></div>

            <div class="d-flex justify-space-between align-center mb-3">
              <span class="font-weight-bold text-slate-800 text-subtitle-2">TOTAL</span>
              <span class="font-weight-bold text-primary text-h6">{{ formatRupiah(totalAmount) }}</span>
            </div>

            <div class="d-flex gap-2">
              <button
                type="button"
                class="pos-btn-hold"
                :disabled="cart.length === 0 || isHoldingBill"
                @click="holdCurrentBill"
              >
                <VIcon icon="ri-pause-circle-line" size="17" class="me-1 text-amber-600" />
                Hold (F6)
              </button>

              <button
                type="button"
                class="pos-btn-pay"
                :disabled="cart.length === 0"
                @click="handleCheckoutClick"
              >
                <VIcon icon="ri-bank-card-line" size="17" class="me-1 text-white" />
                Bayar (F8)
              </button>
            </div>
          </div>
        </div>
      </aside>
    </main>

    <!-- Bottom Shortcut Banner -->
    <footer class="pos-bottom-banner">
      <div class="d-flex align-center gap-2 me-4 text-slate-700 font-weight-bold text-xs">
        <VIcon icon="ri-keyboard-line" size="16" class="text-primary" />
        Shortcut Keyboard
      </div>

      <div class="d-flex align-center gap-3 overflow-x-auto">
        <div class="pos-shortcut-badge cursor-pointer" title="Tekan F1 untuk cari produk" @click="focusSearchInput">
          <span class="pos-kbd-key text-primary bg-primary-subtle">F1</span>
          <span>Cari Produk</span>
        </div>
        <div class="pos-shortcut-badge cursor-pointer" title="Tekan F4 untuk scan barcode" @click="focusSearchInput">
          <span class="pos-kbd-key text-primary bg-primary-subtle">F4</span>
          <span>Scan Barcode</span>
        </div>
        <div class="pos-shortcut-badge cursor-pointer" title="Tekan F6 untuk tahan transaksi" @click="cart.length > 0 ? holdCurrentBill() : snackbar.show('Keranjang belanja kosong', 'warning')">
          <span class="pos-kbd-key text-amber-600 bg-amber-subtle">F6</span>
          <span>Hold Transaksi</span>
        </div>
        <div class="pos-shortcut-badge cursor-pointer" title="Tekan F8 untuk bayar" @click="cart.length > 0 ? handleCheckoutClick() : snackbar.show('Keranjang belanja kosong', 'warning')">
          <span class="pos-kbd-key text-indigo-600 bg-indigo-subtle">F8</span>
          <span>Bayar</span>
        </div>
        <div class="pos-shortcut-badge cursor-pointer" title="Tekan F11 untuk Layar Penuh" @click="toggleFullscreen">
          <span class="pos-kbd-key text-teal-600 bg-teal-subtle">F11</span>
          <span>Layar Penuh</span>
        </div>
        <div class="pos-shortcut-badge cursor-pointer" title="Tekan ESC untuk membatalkan" @click="cart.length > 0 ? clearCart() : router.push({ name: 'dashboards-analytics' })">
          <span class="pos-kbd-key text-slate-600 bg-slate-subtle">ESC</span>
          <span>Batal / Keluar</span>
        </div>
      </div>
    </footer>

    <!-- Mobile Bottom Floating Cart Bar -->
    <div
      v-if="$vuetify.display.xs && cart.length > 0"
      class="pos-mobile-cart-bar d-flex align-center justify-space-between"
    >
      <div>
        <div class="text-caption text-medium-emphasis">{{ cart.reduce((acc, it) => acc + (it.qty || 1), 0) }} Item dalam Keranjang</div>
        <div class="font-weight-bold text-primary text-subtitle-1">{{ formatRupiah(totalAmount) }}</div>
      </div>
      <VBtn
        color="primary"
        class="font-weight-bold shadow-md"
        prepend-icon="ri-shopping-cart-2-line"
        @click="isMobileCartDrawerVisible = true"
      >
        Keranjang & Bayar
      </VBtn>
    </div>

    <!-- Mobile Cart Bottom Sheet Drawer -->
    <VNavigationDrawer
      v-if="$vuetify.display.xs"
      v-model="isMobileCartDrawerVisible"
      temporary
      location="bottom"
      style="height: 82vh; max-height: 82vh;"
      class="rounded-t-xl"
    >
      <div class="pa-4 bg-primary text-white d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-2 font-weight-bold text-subtitle-1">
          <VIcon icon="ri-shopping-cart-2-line" />
          <span>Keranjang Belanja ({{ cart.length }})</span>
        </div>
        <VBtn
          icon="ri-close-line"
          variant="text"
          color="white"
          size="small"
          @click="isMobileCartDrawerVisible = false"
        />
      </div>

      <div class="pa-3 overflow-y-auto" style="max-height: calc(82vh - 230px);">
        <VList lines="two" class="pa-0">
          <template v-for="(item, index) in cart" :key="index">
            <VListItem class="py-2 px-1">
              <div class="d-flex justify-space-between w-100 mb-1">
                <div class="font-weight-bold text-truncate pe-2 text-body-2" style="max-width: 75%;">
                  {{ item.name }}
                </div>
                <IconBtn size="x-small" color="error" @click="removeFromCart(index)">
                  <VIcon icon="ri-delete-bin-line" size="18" />
                </IconBtn>
              </div>
              <div class="d-flex align-center justify-space-between gap-2">
                <div class="d-flex align-center border rounded" style="width: 88px; height: 32px;">
                  <VBtn size="x-small" variant="text" icon="ri-subtract-line" @click="item.qty > 1 ? item.qty-- : null" />
                  <div class="text-center flex-grow-1 font-weight-bold text-caption">{{ item.qty }}</div>
                  <VBtn size="x-small" variant="text" icon="ri-add-line" @click="item.qty < item.max_stock ? item.qty++ : null" />
                </div>
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
            </VListItem>
            <VDivider v-if="index < cart.length - 1" />
          </template>
        </VList>
      </div>

      <div class="pa-3 border-t bg-var-theme-background">
        <div class="d-flex justify-space-between align-center mb-3">
          <span class="text-subtitle-1 font-weight-bold">TOTAL BAYAR</span>
          <span class="text-h6 font-weight-bold text-primary">{{ formatRupiah(totalAmount) }}</span>
        </div>
        <div class="d-flex gap-2">
          <VBtn
            variant="tonal"
            color="warning"
            class="font-weight-bold"
            style="width: 35%;"
            @click="holdCurrentBill(); isMobileCartDrawerVisible = false"
          >
            Hold
          </VBtn>
          <VBtn
            color="primary"
            class="flex-grow-1 font-weight-bold"
            prepend-icon="ri-bank-card-line"
            @click="isMobileCartDrawerVisible = false; handleCheckoutClick()"
          >
            Proses Bayar
          </VBtn>
        </div>
      </div>
    </VNavigationDrawer>

    <!-- Checkout Dialog -->
    <VDialog
      v-model="isCheckoutDialogVisible"
      :fullscreen="$vuetify.display.xs"
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

          <!-- Dynamic Bank Transfer / EDC Selection from Database -->
          <VExpandTransition>
            <div
              v-if="(transactionType === 'lunas' || dpAmountRaw > 0) && paymentMethod === 'transfer'"
              class="mt-4"
            >
              <div class="mb-2">
                <span class="text-caption font-weight-bold text-high-emphasis">Pilih Rekening Bank Penerima:</span>
              </div>

              <!-- Dynamic Bank Chips -->
              <div class="mb-3 d-flex align-center gap-2 flex-wrap" v-if="bankAccounts.length > 0">
                <VChip
                  v-for="b in bankAccounts.filter(acc => acc.type !== 'qris')"
                  :key="b.id"
                  size="small"
                  :color="selectedBankAccountId === b.id ? 'primary' : 'default'"
                  :variant="selectedBankAccountId === b.id ? 'flat' : 'outlined'"
                  class="font-weight-bold cursor-pointer"
                  @click="selectBankAccount(b)"
                >
                  <VIcon icon="ri-bank-card-line" size="14" class="me-1" />
                  {{ b.bank_name }}
                  <span v-if="b.is_default" class="ms-1 font-weight-normal text-caption">(Utama)</span>
                </VChip>
              </div>

              <!-- Selected Bank Details Info Card -->
              <div v-if="selectedBankAccount" class="pa-3 rounded-lg border bg-var-theme-surface shadow-xs mb-2">
                <div class="d-flex justify-space-between align-center mb-1">
                  <div class="d-flex align-center gap-2">
                    <VIcon icon="ri-bank-line" color="primary" size="18" />
                    <span class="font-weight-bold text-subtitle-2 text-primary">{{ selectedBankAccount.bank_name }}</span>
                  </div>
                  <VChip size="x-small" color="info" variant="tonal">
                    {{ selectedBankAccount.branch?.name || 'Semua Cabang (Global)' }}
                  </VChip>
                </div>
                
                <div class="d-flex justify-space-between align-center text-caption py-1 border-b">
                  <span class="text-medium-emphasis">Nomor Rekening:</span>
                  <div class="d-flex align-center gap-1">
                    <strong class="font-mono font-weight-bold text-high-emphasis" style="font-size: 13px;">
                      {{ selectedBankAccount.account_number || '-' }}
                    </strong>
                    <VBtn
                      v-if="selectedBankAccount.account_number"
                      icon="ri-file-copy-line"
                      size="x-small"
                      variant="text"
                      color="primary"
                      title="Salin Nomor Rekening"
                      @click="copyToClipboard(selectedBankAccount.account_number, 'Nomor rekening ' + selectedBankAccount.bank_name)"
                    />
                  </div>
                </div>

                <div class="d-flex justify-space-between align-center text-caption pt-1">
                  <span class="text-medium-emphasis">Atas Nama (A.N):</span>
                  <strong class="text-uppercase text-high-emphasis">{{ selectedBankAccount.account_name || '-' }}</strong>
                </div>
              </div>
              <div v-else class="text-caption text-disabled italic mb-2">
                Belum ada rekening bank yang dipilih.
              </div>
            </div>
          </VExpandTransition>

          <!-- Dynamic QRIS Selection & Display from Database -->
          <VExpandTransition>
            <div
              v-if="(transactionType === 'lunas' || dpAmountRaw > 0) && paymentMethod === 'qris'"
              class="mt-4 text-center"
            >
              <!-- QRIS Bank Account Selector -->
              <div class="mb-3 d-flex justify-center gap-2 flex-wrap" v-if="qrisBankAccounts.length > 1">
                <VChip
                  v-for="b in qrisBankAccounts"
                  :key="b.id"
                  size="small"
                  :color="selectedBankAccountId === b.id ? 'primary' : 'default'"
                  :variant="selectedBankAccountId === b.id ? 'flat' : 'outlined'"
                  class="font-weight-bold cursor-pointer"
                  @click="selectBankAccount(b)"
                >
                  <VIcon icon="ri-qr-code-line" size="14" class="me-1" />
                  {{ b.bank_name }}
                </VChip>
              </div>

              <!-- QRIS Display -->
              <template v-if="selectedBankAccount?.qris_image || branches.find(b => b.id === activeBranchId)?.owner?.qris_image">
                <VAlert
                  type="info"
                  variant="tonal"
                  density="compact"
                  class="mb-3 text-caption py-2 text-start"
                >
                  Minta pelanggan memindai kode QRIS di bawah ini. Tekan <strong>Proses Pembayaran</strong> setelah dana berhasil masuk.
                </VAlert>
                <div class="pa-4 bg-white rounded-lg d-inline-block border shadow-xs">
                  <img
                    :src="selectedBankAccount?.qris_image || `/storage/${branches.find(b => b.id === activeBranchId)?.owner?.qris_image}`"
                    alt="QRIS"
                    style="max-width: 220px; height: auto;"
                    class="rounded"
                  >
                  <div class="mt-2 font-weight-bold text-subtitle-2 text-primary">
                    {{ selectedBankAccount?.bank_name || 'QRIS Pembayaran Resmi' }}
                  </div>
                  <div v-if="selectedBankAccount?.account_number" class="text-caption font-mono text-medium-emphasis">
                    {{ selectedBankAccount.account_number }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    a.n. {{ selectedBankAccount?.account_name || branches.find(b => b.id === activeBranchId)?.owner?.name || 'PT. DUMAI' }}
                  </div>
                </div>
              </template>
              <template v-else>
                <VAlert
                  type="info"
                  variant="tonal"
                  density="compact"
                  class="mb-3 text-start"
                  icon="ri-qr-code-line"
                >
                  <div><strong>Pembayaran QRIS Standee / Statis Toko:</strong></div>
                  <div class="text-caption mt-1">
                    Silakan arahkan pelanggan memindai QRIS fisik / standee yang tersedia di meja kasir. Setelah pelanggan menunjukkan bukti sukses, klik tombol <strong>Proses Pembayaran</strong>.
                  </div>
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
      :fullscreen="$vuetify.display.xs"
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
      :fullscreen="$vuetify.display.xs"
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
      :setting="activeReceiptSetting"
    />

    <!-- QR Catalog Dialog -->
    <VDialog
      v-model="isQrDialogVisible"
      :fullscreen="$vuetify.display.xs"
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
      :fullscreen="$vuetify.display.xs"
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
      :fullscreen="$vuetify.display.xs"
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
                  Harga Jual: <strong class="text-success">{{ formatRupiah(Number(batch.price) > 0 ? batch.price : (selectedProductForBatch?.price || selectedProductForBatch?.selling_price || 0)) }}</strong> | 
                  Batas Nego: <strong>{{ formatRupiah(Number(batch.min_nego_price) > 0 ? batch.min_nego_price : (selectedProductForBatch?.min_nego_price || 0)) }}</strong> | 
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
      :fullscreen="$vuetify.display.xs"
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
      :fullscreen="$vuetify.display.xs"
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
              <span class="font-weight-bold">Ekspektasi Kas Fisik Laci:</span>
              <span class="font-weight-bold text-primary">{{ formatRupiah(shiftSummary.expected_cash) }}</span>
            </div>

            <!-- Rincian Penerimaan per Bank & QRIS Selama Shift -->
            <div v-if="shiftSummary.bank_breakdown && shiftSummary.bank_breakdown.length > 0" class="mt-3 pt-2 border-t">
              <div class="d-flex align-center gap-1 text-caption font-weight-bold text-primary text-uppercase mb-1">
                <VIcon icon="ri-bank-card-line" size="14" />
                Rincian Penerimaan Bank & QRIS (Shift Ini):
              </div>
              <div
                v-for="b in shiftSummary.bank_breakdown"
                :key="b.bank_account_id"
                class="d-flex justify-space-between py-1 text-caption border-b"
              >
                <div>
                  <span class="font-weight-bold">{{ b.bank_name }}:</span>
                  <span v-if="b.bank_account_number" class="text-disabled font-mono ms-1" style="font-size: 10px;">
                    ({{ b.bank_account_number }})
                  </span>
                  <span class="text-caption text-primary ms-1">[{{ b.count }} tx]</span>
                </div>
                <span class="font-weight-bold font-mono text-primary">+ {{ formatRupiah(b.total) }}</span>
              </div>
              <div class="d-flex justify-space-between pt-1 text-caption font-weight-bold">
                <span>Total Omzet Non-Tunai:</span>
                <span class="text-success">{{ formatRupiah(shiftSummary.total_non_cash_sales) }}</span>
              </div>
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
      :fullscreen="$vuetify.display.xs"
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
.pos-root-container {
  position: fixed;
  inset: 0;
  width: 100vw;
  height: 100vh;
  max-height: 100vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  background-color: #f8fafc;
  padding: 10px 14px;
  gap: 8px;
  box-sizing: border-box;
  z-index: 10;
}

/* Header Bar */
.pos-header-bar {
  height: 42px;
  min-height: 42px;
  max-height: 42px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}

.pos-top-pill {
  height: 34px;
  padding: 0 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
}
.pos-top-pill:hover {
  background: #f1f5f9;
}

.pos-pill-success {
  color: #16a34a;
  border-color: #bbf7d0;
  background: #f0fdf4;
}
.pos-pill-error {
  color: #dc2626;
  border-color: #fecaca;
  background: #fef2f2;
}

.status-dot-green {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10b981;
  display: inline-block;
}

/* Main Content Area */
.pos-main-content {
  flex: 1 1 0;
  min-height: 0;
  display: flex;
  gap: 12px;
  overflow: hidden;
}

/* Catalog Section */
.pos-catalog-section {
  flex: 1 1 0;
  min-width: 0;
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.pos-catalog-box {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.pos-catalog-header {
  flex-shrink: 0;
  background: #ffffff;
  border-bottom: 1px solid #f1f5f9;
  padding: 10px 14px 8px;
}

.pos-search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0 10px;
  height: 38px;
}

.pos-search-icon {
  color: #94a3b8;
  margin-right: 8px;
}

.pos-search-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 13px;
  color: #1e293b;
  background: transparent;
}

.pos-kbd-badge {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  padding: 1px 6px;
}

.pos-scan-btn {
  height: 38px;
  padding: 0 14px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #ffffff;
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  display: flex;
  align-items: center;
  cursor: pointer;
  transition: all 0.15s;
}
.pos-scan-btn:hover {
  border-color: #3b82f6;
  background: #f8fafc;
}

.pos-category-row {
  display: flex;
  align-items: center;
  gap: 8px;
  overflow-x: auto;
  padding-top: 4px;
  padding-bottom: 2px;
}
.pos-category-row::-webkit-scrollbar {
  height: 3px;
}
.pos-category-row::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.pos-cat-pill {
  height: 30px;
  padding: 0 12px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  font-size: 11.5px;
  font-weight: 600;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s;
}
.pos-cat-pill:hover {
  border-color: #3b82f6;
  color: #3b82f6;
}
.pos-cat-pill-active {
  background: #3b82f6 !important;
  color: #ffffff !important;
  border-color: #3b82f6 !important;
}

.pos-catalog-body {
  flex: 1 1 0;
  min-height: 0;
  height: 100%;
  overflow-y: auto;
  padding: 12px 14px;
  background: #f8fafc;
}
.pos-catalog-body::-webkit-scrollbar {
  width: 5px;
}
.pos-catalog-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

/* Product Card Grid */
.pos-product-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

@media (max-width: 1200px) {
  .pos-product-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.pos-product-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  min-height: 145px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}
.pos-product-card:hover {
  transform: translateY(-2px);
  border-color: #3b82f6;
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.1);
}

.pos-card-disabled {
  opacity: 0.45;
  pointer-events: none;
}

.pos-card-icon-box {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: #eef2ff;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.pos-card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.pos-star-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 2px;
  display: flex;
  align-items: center;
}

.pos-card-title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 2px 0;
  line-height: 1.3;
}

.pos-card-sku {
  font-size: 11px;
  color: #94a3b8;
  line-height: 1.2;
}

.pos-card-price {
  font-size: 14px;
  font-weight: 800;
  color: #3b82f6;
  line-height: 1.2;
}

.pos-method-badge {
  font-size: 9.5px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 5px;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  display: inline-block;
}
.pos-method-badge.fifo {
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #dbeafe;
}
.pos-method-badge.lifo {
  background: #f5f3ff;
  color: #7c3aed;
  border: 1px solid #ede9fe;
}
.pos-method-badge.fefo {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fee2e2;
}

.pos-stock-pill {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 6px;
  display: inline-block;
}
.pos-stock-pill.in-stock {
  background: #ecfdf5;
  color: #059669;
}
.pos-stock-pill.low-stock {
  background: #fef3c7;
  color: #d97706;
}
.pos-stock-pill.out-of-stock {
  background: #fef2f2;
  color: #ef4444;
}

/* Catalog Footer */
.pos-catalog-footer {
  height: 48px;
  min-height: 48px;
  max-height: 48px;
  flex-shrink: 0;
  background: #ffffff;
  border-top: 1px solid #f1f5f9;
  padding: 0 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.pos-footer-info {
  font-size: 12px;
  color: #64748b;
  font-weight: 500;
}

.pos-custom-select {
  height: 30px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  font-size: 12px;
  color: #475569;
  padding: 0 8px;
  outline: none;
  cursor: pointer;
}

/* Cart Section */
.pos-cart-section {
  width: 380px;
  max-width: 40%;
  min-width: 320px;
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  overflow: hidden;
}

.pos-cart-box {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.pos-cart-header {
  height: 44px;
  min-height: 44px;
  flex-shrink: 0;
  background: #3b82f6;
  color: #ffffff;
  padding: 0 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.pos-cart-count-badge {
  background: #ffffff;
  color: #3b82f6;
  font-size: 11px;
  font-weight: 800;
  padding: 1px 7px;
  border-radius: 12px;
}

.pos-cart-clear-btn {
  background: none;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  opacity: 0.85;
  transition: opacity 0.15s;
}
.pos-cart-clear-btn:hover {
  opacity: 1;
}

.pos-cart-body {
  flex: 1 1 0;
  min-height: 0;
  height: 100%;
  overflow-y: auto;
  padding: 10px 12px;
  background: #ffffff;
}
.pos-cart-body::-webkit-scrollbar {
  width: 5px;
}
.pos-cart-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.pos-cart-items-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.pos-cart-item-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  background: #f8fafc;
}

.pos-cart-item-thumb {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: #eef2ff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.pos-qty-group {
  display: inline-flex;
  align-items: center;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: #ffffff;
  height: 26px;
}

.pos-qty-btn {
  width: 24px;
  height: 100%;
  border: none;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #64748b;
}
.pos-qty-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.pos-qty-val {
  padding: 0 6px;
  font-size: 11px;
  font-weight: 700;
  color: #1e293b;
  min-width: 18px;
  text-align: center;
}

.pos-cart-price-input-wrapper {
  display: inline-flex;
  align-items: center;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: #ffffff;
  height: 26px;
  padding: 0 6px;
  flex: 1;
  max-width: 120px;
  transition: all 0.15s;
}
.pos-cart-price-input-wrapper:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
}
.pos-cart-price-prefix {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 600;
  margin-right: 3px;
}
.pos-cart-price-input {
  width: 100%;
  border: none;
  outline: none;
  font-size: 11.5px;
  font-weight: 700;
  color: #2563eb;
  background: transparent;
  text-align: right;
  font-family: monospace;
}

.pos-item-del-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 2px;
  display: flex;
  align-items: center;
  opacity: 0.7;
  transition: opacity 0.15s;
}
.pos-item-del-btn:hover {
  opacity: 1;
}

.pos-cart-footer {
  flex-shrink: 0;
  background: #ffffff;
  border-top: 1px solid #f1f5f9;
  padding: 12px 14px;
}

.pos-discount-type-toggle {
  display: inline-flex;
  background: #f1f5f9;
  border-radius: 6px;
  padding: 2px;
}
.pos-disc-btn {
  border: none;
  background: transparent;
  font-size: 10.5px;
  font-weight: 700;
  padding: 1px 7px;
  border-radius: 4px;
  cursor: pointer;
  color: #64748b;
  transition: all 0.15s;
}
.pos-disc-btn-active {
  background: #3b82f6;
  color: #ffffff;
  box-shadow: 0 1px 2px rgba(59, 130, 246, 0.2);
}

.pos-diskon-input-wrapper {
  display: flex;
  align-items: center;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 0 8px;
  height: 30px;
  background: #ffffff;
  transition: all 0.15s;
}
.pos-diskon-input-wrapper:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
}
.pos-diskon-prefix,
.pos-diskon-suffix {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 600;
}
.pos-diskon-prefix {
  margin-right: 4px;
}
.pos-diskon-suffix {
  margin-left: 4px;
}

.pos-diskon-input {
  width: 100%;
  border: none;
  outline: none;
  font-size: 12px;
  font-weight: 600;
  text-align: right;
  background: transparent;
  color: #1e293b;
  font-family: monospace;
}

.pos-summary-divider {
  border-bottom: 1px dashed #e2e8f0;
}

.pos-btn-hold {
  flex: 1;
  height: 40px;
  border: 1px solid #fef3c7;
  background: #fef3c7;
  color: #d97706;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
}
.pos-btn-hold:hover:not(:disabled) {
  background: #fde68a;
}
.pos-btn-hold:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pos-btn-pay {
  flex: 1.5;
  height: 40px;
  border: none;
  background: #3b82f6;
  color: #ffffff;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  box-shadow: 0 2px 6px rgba(59, 130, 246, 0.25);
}
.pos-btn-pay:hover:not(:disabled) {
  background: #2563eb;
}
.pos-btn-pay:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Bottom Shortcut Banner */
.pos-bottom-banner {
  height: 38px;
  min-height: 38px;
  max-height: 38px;
  flex-shrink: 0;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0 14px;
  display: flex;
  align-items: center;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.pos-shortcut-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  color: #475569;
  font-weight: 500;
  white-space: nowrap;
}

.pos-kbd-key {
  font-size: 11px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 4px;
}

.bg-primary-subtle {
  background: #eff6ff;
}
.bg-amber-subtle {
  background: #fffbeb;
}
.bg-indigo-subtle {
  background: #eef2ff;
}
.bg-slate-subtle {
  background: #f1f5f9;
}
.bg-teal-subtle {
  background: #f0fdfa;
}
</style>

<route lang="yaml">
meta:
  action: read
  subject: Kasir (POS)
  layout: blank
</route>
