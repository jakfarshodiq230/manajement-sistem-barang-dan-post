<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { themeConfig } from '@themeConfig'

definePage({
  meta: {
    layout: 'blank',
    public: true,
  },
})

const route = useRoute()
const branchId = route.params.id

const isLoading = ref(true)
const branch = ref(null)
const categories = ref([])
const products = ref([])
const totalProducts = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)

const searchQuery = ref('')
const selectedCategory = ref(null)

// Detail Modal
const isDetailModalOpen = ref(false)
const selectedProduct = ref(null)

const isQrModalOpen = ref(false)

const currentUrl = computed(() => {
  if (typeof window !== 'undefined') return window.location.href
  return ''
})

const qrCodeUrl = computed(() => {
  return `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(currentUrl.value)}`
})

const fetchKatalog = async (page = 1) => {
  isLoading.value = true
  try {
    let url = `/katalog/${branchId}?page=${page}`
    if (searchQuery.value) url += `&search=${encodeURIComponent(searchQuery.value)}`
    if (selectedCategory.value) url += `&category_id=${selectedCategory.value}`

    const res = await $api(url)

    branch.value = res.branch
    categories.value = res.categories || []
    products.value = res.products?.data || []
    currentPage.value = res.products?.current_page || 1
    lastPage.value = res.products?.last_page || 1
    totalProducts.value = res.products?.total || 0
  } catch (error) {
    console.error('Failed to fetch katalog:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchKatalog()
})

watch(selectedCategory, () => {
  fetchKatalog(1)
})

let searchTimeout
watch(searchQuery, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchKatalog(1)
  }, 400)
})

const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(val || 0)
}

const getStockColor = stock => {
  if (stock > 10) return 'success'
  if (stock > 0) return 'warning'
  return 'error'
}

const getStockText = stock => {
  if (stock > 10) return 'Stok Tersedia'
  if (stock > 0) return `Sisa ${stock}`
  return 'Stok Habis'
}

const openProductDetail = item => {
  selectedProduct.value = item
  isDetailModalOpen.value = true
}

const copyCatalogLink = () => {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(currentUrl.value)
    alert('Tautan katalog online berhasil disalin!')
  }
}
</script>

<template>
  <div class="katalog-page min-vh-100 bg-background text-high-emphasis">
    <!-- Top Sticky Navigation Bar -->
    <header class="katalog-nav bg-surface elevation-1 py-3 px-4 px-md-8 sticky-top">
      <div class="max-w-1200 mx-auto d-flex align-center justify-space-between gap-3">
        <!-- Store Brand -->
        <div class="d-flex align-center gap-3">
          <VAvatar
            size="48"
            rounded="lg"
            class="elevation-1 border"
            :color="!branch?.logo ? 'primary' : undefined"
            :variant="!branch?.logo ? 'tonal' : undefined"
          >
            <VImg
              v-if="branch?.logo"
              :src="`/storage/${branch.logo}`"
              cover
            />
            <VIcon
              v-else
              icon="ri-store-2-line"
              size="26"
              color="primary"
            />
          </VAvatar>
          <div>
            <div class="d-flex align-center gap-2">
              <h1 class="text-subtitle-1 font-weight-bold mb-0 text-truncate max-w-250">
                {{ branch?.name || 'Katalog Toko' }}
              </h1>
              <VChip size="x-small" color="success" variant="tonal" class="font-weight-bold">
                <VIcon icon="ri-checkbox-circle-fill" size="12" class="me-1" />
                Resmi
              </VChip>
            </div>
            <p class="text-caption text-medium-emphasis mb-0 d-flex align-center gap-1 text-truncate max-w-300">
              <VIcon icon="ri-map-pin-line" size="13" class="text-primary flex-shrink-0" />
              <span>{{ branch?.address || 'Cabang Terverifikasi' }}</span>
            </p>
          </div>
        </div>

        <!-- Right Action Buttons -->
        <div class="d-flex align-center gap-2">
          <VBtn
            size="small"
            variant="tonal"
            color="primary"
            prepend-icon="ri-qr-code-line"
            @click="isQrModalOpen = true"
          >
            Bagikan QR & Tautan
          </VBtn>
        </div>
      </div>
    </header>

    <!-- Hero Banner & Search Section -->
    <section class="hero-section py-8 px-4 bg-gradient-hero border-b">
      <div class="max-w-1200 mx-auto text-center">
        <VChip color="primary" variant="tonal" size="small" class="font-weight-bold mb-2">
          <VIcon icon="ri-shopping-bag-3-line" size="14" class="me-1" />
          Katalog Produk Digital & Stok Real-Time
        </VChip>
        <h2 class="text-h4 font-weight-extrabold mb-2 text-high-emphasis">
          Daftar Produk & Stok Barang Toko
        </h2>
        <p class="text-body-2 text-medium-emphasis max-w-600 mx-auto mb-6">
          Jelajahi daftar produk lengkap cabang <strong>{{ branch?.name }}</strong>. Stok terhubung langsung dengan sistem inventaris pusat.
        </p>

        <!-- Search Bar Card -->
        <VCard elevation="3" class="max-w-700 mx-auto rounded-xl pa-2 border">
          <VTextField
            v-model="searchQuery"
            prepend-inner-icon="ri-search-line"
            placeholder="Ketik nama barang, kode SKU, atau merk produk..."
            variant="plain"
            density="comfortable"
            hide-details
            clearable
            class="px-2"
          />
        </VCard>
      </div>
    </section>

    <!-- Category Chips Carousel (VSlideGroup with Arrows) -->
    <div class="bg-surface border-b py-2 px-4 sticky-category">
      <div class="max-w-1200 mx-auto">
        <VSlideGroup
          v-model="selectedCategory"
          show-arrows
          class="category-slider"
        >
          <VSlideGroupItem>
            <VChip
              size="default"
              :color="!selectedCategory ? 'primary' : 'default'"
              :variant="!selectedCategory ? 'elevated' : 'tonal'"
              class="ma-1 cursor-pointer font-weight-bold px-4"
              @click="selectedCategory = null"
            >
              <VIcon icon="ri-apps-2-line" size="16" class="me-1" />
              Semua Kategori ({{ totalProducts }})
            </VChip>
          </VSlideGroupItem>

          <VSlideGroupItem
            v-for="cat in categories"
            :key="cat.id"
          >
            <VChip
              size="default"
              :color="selectedCategory === cat.id ? 'primary' : 'default'"
              :variant="selectedCategory === cat.id ? 'elevated' : 'tonal'"
              class="ma-1 cursor-pointer font-weight-medium px-4"
              @click="selectedCategory = selectedCategory === cat.id ? null : cat.id"
            >
              {{ cat.name }}
            </VChip>
          </VSlideGroupItem>
        </VSlideGroup>
      </div>
    </div>

    <!-- Main Products Grid -->
    <main class="max-w-1200 mx-auto px-4 py-8">
      <!-- Loading State -->
      <div
        v-if="isLoading"
        class="text-center py-16"
      >
        <VProgressCircular
          indeterminate
          color="primary"
          size="56"
          width="4"
        />
        <div class="text-body-2 text-medium-emphasis mt-4">Memuat produk katalog...</div>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="products.length === 0"
        class="text-center py-16 bg-surface rounded-xl border pa-8 max-w-600 mx-auto"
      >
        <VAvatar
          size="80"
          color="primary"
          variant="tonal"
          class="mb-4"
        >
          <VIcon icon="ri-search-eye-line" size="44" />
        </VAvatar>
        <h3 class="text-h5 font-weight-bold mb-2">
          Produk Tidak Ditemukan
        </h3>
        <p class="text-body-2 text-medium-emphasis mb-4">
          Tidak ada produk yang cocok dengan pencarian atau kategori yang dipilih.
        </p>
        <VBtn
          color="primary"
          variant="tonal"
          prepend-icon="ri-refresh-line"
          @click="searchQuery = ''; selectedCategory = null"
        >
          Tampilkan Semua Produk
        </VBtn>
      </div>

      <!-- Products Grid -->
      <div v-else>
        <div class="d-flex align-center justify-space-between mb-4">
          <div class="text-body-2 text-medium-emphasis">
            Menampilkan <strong>{{ products.length }}</strong> dari <strong>{{ totalProducts }}</strong> produk
          </div>
        </div>

        <VRow>
          <VCol
            v-for="item in products"
            :key="item.id"
            cols="6"
            sm="6"
            md="4"
            lg="3"
          >
            <VCard
              class="h-100 product-card rounded-xl overflow-hidden d-flex flex-column transition-all"
              elevation="1"
              hover
              @click="openProductDetail(item)"
            >
              <!-- Image Area -->
              <div class="position-relative bg-grey-lighten-4 overflow-hidden" style="height: 190px;">
                <VImg
                  v-if="item.product?.image"
                  :src="`/storage/${item.product.image}`"
                  height="190"
                  cover
                  class="product-image"
                >
                  <template #placeholder>
                    <div class="d-flex align-center justify-center h-100">
                      <VProgressCircular indeterminate color="primary" />
                    </div>
                  </template>
                </VImg>
                <div
                  v-else
                  class="d-flex flex-column align-center justify-center h-100 text-disabled"
                >
                  <VIcon icon="ri-box-3-line" size="48" color="primary" opacity="0.4" />
                  <span class="text-caption mt-1">Foto Belum Tersedia</span>
                </div>

                <!-- Stock Badge -->
                <div class="position-absolute top-0 end-0 pa-2">
                  <VChip
                    :color="getStockColor(item.stock)"
                    size="x-small"
                    variant="elevated"
                    class="font-weight-bold elevation-2"
                  >
                    {{ getStockText(item.stock) }}
                  </VChip>
                </div>
              </div>

              <!-- Product Details -->
              <VCardText class="pa-4 d-flex flex-column flex-grow-1">
                <div class="text-caption text-primary font-weight-bold mb-1 text-truncate">
                  {{ item.product?.category?.name || 'Umum' }}
                </div>

                <h4
                  class="text-subtitle-2 font-weight-bold text-high-emphasis line-clamp-2 mb-2"
                  style="line-height: 1.35; min-height: 2.7em;"
                  :title="item.product?.name"
                >
                  {{ item.product?.name }}
                </h4>

                <div class="text-caption text-disabled mb-3 d-flex align-center gap-1">
                  <span>SKU: {{ item.product?.sku || '-' }}</span>
                  <span v-if="item.product?.unit">• {{ item.product.unit }}</span>
                </div>

                <VSpacer />

                <div class="d-flex align-end justify-space-between pt-2 border-t mt-auto">
                  <div>
                    <div class="text-caption text-medium-emphasis" style="font-size: 11px;">Harga Satuan</div>
                    <div class="text-h6 font-weight-extrabold text-primary mb-0" style="line-height: 1.1;">
                      {{ formatCurrency(item.active_batch ? item.active_batch.price : item.price) }}
                    </div>
                  </div>

                  <VBtn
                    size="x-small"
                    color="primary"
                    variant="tonal"
                    icon="ri-eye-line"
                    title="Lihat Detail"
                    @click.stop="openProductDetail(item)"
                  />
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>

        <!-- Pagination -->
        <div
          v-if="lastPage > 1"
          class="d-flex justify-center mt-10"
        >
          <VPagination
            v-model="currentPage"
            :length="lastPage"
            :total-visible="5"
            rounded="circle"
            active-color="primary"
            @update:model-value="fetchKatalog"
          />
        </div>
      </div>
    </main>

    <!-- Product Detail Modal -->
    <VDialog
      v-model="isDetailModalOpen"
      max-width="560"
    >
      <VCard v-if="selectedProduct" class="rounded-xl overflow-hidden">
        <!-- Modal Image -->
        <div class="position-relative bg-grey-lighten-4" style="height: 260px;">
          <VImg
            v-if="selectedProduct.product?.image"
            :src="`/storage/${selectedProduct.product.image}`"
            height="260"
            cover
          />
          <div
            v-else
            class="d-flex flex-column align-center justify-center h-100"
          >
            <VIcon icon="ri-box-3-line" size="64" color="primary" opacity="0.4" />
          </div>

          <VBtn
            icon="ri-close-line"
            size="small"
            color="surface"
            variant="elevated"
            class="position-absolute top-0 end-0 ma-3 elevation-2"
            @click="isDetailModalOpen = false"
          />
        </div>

        <VCardText class="pa-6">
          <div class="d-flex align-center justify-space-between mb-2">
            <VChip color="primary" size="small" variant="tonal" class="font-weight-bold">
              {{ selectedProduct.product?.category?.name || 'Kategori Umum' }}
            </VChip>
            <VChip :color="getStockColor(selectedProduct.stock)" size="small" variant="elevated" class="font-weight-bold">
              {{ getStockText(selectedProduct.stock) }}
            </VChip>
          </div>

          <h3 class="text-h5 font-weight-bold mb-2 text-high-emphasis">
            {{ selectedProduct.product?.name }}
          </h3>

          <div class="text-h4 font-weight-extrabold text-primary mb-4">
            {{ formatCurrency(selectedProduct.active_batch ? selectedProduct.active_batch.price : selectedProduct.price) }}
            <span class="text-body-2 font-weight-regular text-medium-emphasis">/ {{ selectedProduct.product?.unit || 'Pcs' }}</span>
          </div>

          <VDivider class="my-4" />

          <!-- Specifications -->
          <div class="d-flex flex-column gap-2 mb-6">
            <div class="d-flex justify-space-between text-body-2">
              <span class="text-medium-emphasis">Kode SKU:</span>
              <span class="font-weight-medium">{{ selectedProduct.product?.sku || '-' }}</span>
            </div>
            <div class="d-flex justify-space-between text-body-2">
              <span class="text-medium-emphasis">Satuan Produk:</span>
              <span class="font-weight-medium">{{ selectedProduct.product?.unit || 'Pcs' }}</span>
            </div>
            <div v-if="selectedProduct.product?.description" class="mt-2 text-body-2 text-medium-emphasis">
              <div class="font-weight-bold text-high-emphasis mb-1">Deskripsi Produk:</div>
              <p class="mb-0">{{ selectedProduct.product.description }}</p>
            </div>
          </div>

          <!-- Close Action Button -->
          <VBtn
            block
            color="primary"
            size="large"
            variant="tonal"
            prepend-icon="ri-close-line"
            @click="isDetailModalOpen = false"
            class="font-weight-bold"
          >
            Tutup Detail Produk
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Share QR Modal -->
    <VDialog
      v-model="isQrModalOpen"
      max-width="400"
    >
      <VCard class="rounded-xl pa-6 text-center">
        <h4 class="text-h5 font-weight-bold mb-1">QR Katalog Online</h4>
        <p class="text-caption text-medium-emphasis mb-4">
          Pindai kode QR untuk membuka katalog cabang ini di smartphone Anda.
        </p>

        <div class="d-flex justify-center mb-4">
          <VImg
            :src="qrCodeUrl"
            width="200"
            height="200"
            class="elevation-2 rounded-lg pa-2 border"
          />
        </div>

        <div class="d-flex gap-2">
          <VBtn
            block
            color="primary"
            variant="tonal"
            prepend-icon="ri-file-copy-line"
            @click="copyCatalogLink"
          >
            Salin Tautan
          </VBtn>
        </div>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.max-w-1200 {
  max-width: 1200px;
}
.max-w-700 {
  max-width: 700px;
}
.max-w-600 {
  max-width: 600px;
}
.max-w-300 {
  max-width: 300px;
}
.max-w-250 {
  max-width: 250px;
}

.katalog-nav {
  position: sticky;
  top: 0;
  z-index: 100;
  backdrop-filter: blur(12px);
  background-color: rgba(var(--v-theme-surface), 0.95) !important;
}

.sticky-category {
  position: sticky;
  top: 68px;
  z-index: 90;
  backdrop-filter: blur(10px);
  background-color: rgba(var(--v-theme-surface), 0.95) !important;
}

.bg-gradient-hero {
  background: linear-gradient(180deg, rgba(var(--v-theme-primary), 0.05) 0%, rgba(var(--v-theme-background), 1) 100%);
}

.product-card {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.12) !important;
  border-color: rgba(var(--v-theme-primary), 0.4);
}

.product-image {
  transition: transform 0.4s ease;
}

.product-card:hover .product-image {
  transform: scale(1.06);
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
