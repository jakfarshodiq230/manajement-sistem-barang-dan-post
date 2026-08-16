<script setup>
import { ref, watch, onMounted } from 'vue'
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

const fetchKatalog = async (page = 1) => {
  isLoading.value = true
  try {
    let url = `/katalog/${branchId}?page=${page}`
    if (searchQuery.value) url += `&search=${encodeURIComponent(searchQuery.value)}`
    if (selectedCategory.value) url += `&category_id=${selectedCategory.value}`

    const res = await $api(url)

    branch.value = res.branch
    categories.value = res.categories
    products.value = res.products.data
    currentPage.value = res.products.current_page
    lastPage.value = res.products.last_page
    totalProducts.value = res.products.total
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
  }, 500)
})

const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(val)
}

const getStockColor = stock => {
  if (stock > 10) return 'success'
  if (stock > 0) return 'warning'
  
  return 'error'
}

const getStockText = stock => {
  if (stock > 10) return 'Stok Aman'
  if (stock > 0) return `Sisa ${stock}`
  
  return 'Habis'
}
</script>

<template>
  <div class="katalog-container bg-background min-vh-100">
    <!-- Header -->
    <div class="katalog-header bg-surface elevation-2 py-4 px-6 mb-6">
      <div class="d-flex align-center justify-space-between max-w-1200 mx-auto">
        <div class="d-flex align-center gap-4">
          <VAvatar
            v-if="branch?.logo"
            size="50"
            rounded
          >
            <VImg
              :src="`/storage/${branch.logo}`"
              cover
            />
          </VAvatar>
          <VAvatar
            v-else
            size="50"
            rounded
            color="primary"
            variant="tonal"
          >
            <VIcon
              icon="ri-store-2-line"
              size="30"
            />
          </VAvatar>
          <div>
            <h1 class="text-h5 font-weight-bold mb-0 text-primary">
              {{ branch?.name || 'Loading...' }}
            </h1>
            <p class="text-caption text-medium-emphasis mb-0 d-flex align-center gap-1">
              <VIcon
                icon="ri-map-pin-line"
                size="14"
              />
              {{ branch?.address || '-' }}
            </p>
          </div>
        </div>
        <div class="d-none d-sm-block text-right">
          <h2 class="text-h6 font-weight-semibold mb-0">
            {{ themeConfig.app.title }}
          </h2>
          <p class="text-caption text-medium-emphasis mb-0">
            Official Catalog
          </p>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-1200 mx-auto px-4 pb-10">
      <!-- Search & Filters -->
      <VCard
        class="mb-6 rounded-lg elevation-1"
        style="border-top: 4px solid rgb(var(--v-theme-primary))"
      >
        <VCardText class="d-flex flex-column flex-md-row gap-4 align-center">
          <div class="flex-grow-1 w-100">
            <VTextField
              v-model="searchQuery"
              prepend-inner-icon="ri-search-2-line"
              placeholder="Cari produk (misal: Busi, Oli...)"
              variant="outlined"
              density="comfortable"
              hide-details
              clearable
              rounded="lg"
            />
          </div>
          <div
            style="min-width: 250px;"
            class="w-100 w-md-auto"
          >
            <VSelect
              v-model="selectedCategory"
              :items="categories"
              item-title="name"
              item-value="id"
              placeholder="Semua Kategori"
              variant="outlined"
              density="comfortable"
              hide-details
              clearable
              prepend-inner-icon="ri-layout-grid-line"
              rounded="lg"
            />
          </div>
        </VCardText>
      </VCard>

      <!-- Products Grid -->
      <div
        v-if="isLoading && products.length === 0"
        class="d-flex justify-center py-10"
      >
        <VProgressCircular
          indeterminate
          color="primary"
          size="40"
        />
      </div>

      <div
        v-else-if="products.length === 0"
        class="text-center py-12"
      >
        <VAvatar
          size="80"
          color="secondary"
          variant="tonal"
          class="mb-4"
        >
          <VIcon
            icon="ri-search-eye-line"
            size="40"
          />
        </VAvatar>
        <h3 class="text-h5 font-weight-medium mb-1">
          Produk Tidak Ditemukan
        </h3>
        <p class="text-medium-emphasis">
          Coba cari dengan kata kunci lain atau pilih kategori berbeda.
        </p>
        <VBtn
          variant="tonal"
          class="mt-2"
          @click="searchQuery = ''; selectedCategory = null"
        >
          Reset Filter
        </VBtn>
      </div>

      <VRow v-else>
        <VCol
          v-for="item in products"
          :key="item.id"
          cols="6"
          sm="4"
          md="3"
          lg="3"
        >
          <VCard
            class="h-100 product-card rounded-lg overflow-hidden transition-swing"
            elevation="1"
          >
            <!-- Image Area -->
            <div class="product-img-wrapper position-relative bg-grey-50">
              <VImg
                v-if="item.product?.image"
                :src="`/storage/${item.product.image}`"
                height="180"
                cover
                class="product-image"
              >
                <template #placeholder>
                  <div class="d-flex align-center justify-center h-100">
                    <VProgressCircular
                      indeterminate
                      color="grey-lighten-4"
                    />
                  </div>
                </template>
              </VImg>
              <div
                v-else
                class="d-flex align-center justify-center bg-grey-100"
                style="height: 180px;"
              >
                <VIcon
                  icon="ri-image-line"
                  size="40"
                  color="grey-400"
                />
              </div>
              
              <!-- Stock Badge Floating -->
              <VChip
                :color="getStockColor(item.stock)"
                size="small"
                class="position-absolute font-weight-bold elevation-1"
                style="top: 10px; right: 10px; font-size: 11px; z-index: 2;"
                variant="elevated"
              >
                {{ getStockText(item.stock) }}
              </VChip>
            </div>

            <!-- Content Area -->
            <VCardText
              class="pa-4 d-flex flex-column"
              style="height: calc(100% - 180px);"
            >
              <p class="text-caption text-primary font-weight-medium mb-1 text-truncate">
                {{ item.product?.category?.name || 'Tanpa Kategori' }}
              </p>
              
              <h3
                class="text-subtitle-1 font-weight-bold mb-2 text-high-emphasis line-clamp-2"
                style="line-height: 1.3;"
              >
                {{ item.product?.name }}
              </h3>
              
              <VSpacer />
              
              <div class="mt-2 d-flex align-end justify-space-between">
                <div>
                  <div
                    class="text-h6 font-weight-bold text-primary mb-0"
                    style="line-height: 1;"
                  >
                    {{ formatCurrency(item.active_batch ? item.active_batch.price : item.price) }}
                  </div>
                  <div
                    v-if="item.active_batch"
                    class="text-caption text-medium-emphasis mt-1 d-flex align-center gap-1"
                  >
                    <VIcon icon="ri-price-tag-3-line" size="14" />
                    Harga Batch Aktif
                  </div>
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Pagination -->
      <div
        v-if="lastPage > 1"
        class="d-flex justify-center mt-8"
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
  </div>
</template>

<style scoped>
.max-w-1200 {
  max-width: 1200px;
}
.katalog-header {
  position: sticky;
  top: 0;
  z-index: 10;
  backdrop-filter: blur(10px);
  background-color: rgba(var(--v-theme-surface), 0.9) !important;
}
.product-card {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
  border-color: rgba(var(--v-theme-primary), 0.3);
}
.product-img-wrapper {
  overflow: hidden;
}
.product-image {
  transition: transform 0.3s ease;
}
.product-card:hover .product-image {
  transform: scale(1.05);
}
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}
.bg-grey-50 {
  background-color: #fafafa;
}
</style>
