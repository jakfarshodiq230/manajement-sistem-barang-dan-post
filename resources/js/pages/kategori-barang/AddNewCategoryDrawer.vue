<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { VForm } from 'vuetify/components/VForm'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  editingCategory: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'update:is-drawer-open',
  'close',
  'cancel',
  'categoryData',
])

const isFormValid = ref(false)
const refForm = ref()

const name = ref('')
const description = ref('')

const isEditing = computed(() => !!props.editingCategory)

// Watch for editing
watch(
  () => props.editingCategory,
  val => {
    if (val) {
      name.value = val.name
      description.value = val.description || ''
    } else {
      resetForm()
    }
  },
  { immediate: true },
)

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  emit('update:is-drawer-open', false)
  emit('close')
  emit('cancel')
  resetForm()
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('categoryData', {
        id: isEditing.value ? props.editingCategory.id : undefined,
        name: name.value,
        description: description.value,
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
    resetForm()
  }
}

function resetForm() {
  name.value = ''
  description.value = ''
  if (refForm.value) {
    refForm.value.resetValidation()
  }
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '92vw' : 460)"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  
      disable-resize-watcher>
    <!-- Header -->
    <div class="d-flex align-center justify-space-between px-6 py-5 border-b">
      <h6 class="text-h6 font-weight-bold mb-0">
        {{ isEditing ? 'Edit Kategori Produk' : 'Tambah Kategori Baru' }}
      </h6>
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
        <VForm
          ref="refForm"
          v-model="isFormValid"
          @submit.prevent="onSubmit"
        >
          <VRow dense>
            <VCol cols="12" class="mb-2">
              <VTextField
                v-model="name"
                :rules="[v => !!v || 'Nama kategori wajib diisi']"
                label="Nama Kategori Produk"
                placeholder="Misal: Aki Basah / Aki Kering / Oli"
                density="compact"
                variant="outlined"
                prepend-inner-icon="ri-folder-3-line"
              />
            </VCol>

            <VCol cols="12" class="mb-4">
              <VTextarea
                v-model="description"
                label="Deskripsi & Keterangan Kategori"
                placeholder="Tuliskan catatan klasifikasi barang..."
                rows="3"
                density="compact"
                variant="outlined"
                prepend-inner-icon="ri-file-text-line"
              />
            </VCol>

            <!-- Action Bar -->
            <VCol cols="12">
              <div class="d-flex align-center gap-3 pt-2">
                <VBtn
                  type="submit"
                  color="primary"
                  size="large"
                  prepend-icon="ri-save-3-line"
                  class="font-weight-bold flex-grow-1 rounded-lg shadow-sm"
                >
                  {{ isEditing ? 'Simpan Perubahan Kategori' : 'Daftarkan Kategori' }}
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
            </VCol>
          </VRow>
        </VForm>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>

<style scoped>
.bg-gradient-header {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.05) 0%, rgba(var(--v-theme-surface), 1) 100%);
}
</style>
