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
      emit('update:isDrawerOpen', false)
      resetForm()
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
  if (!val) resetForm()
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
    :width="$vuetify.display.xs ? '100%' : ($vuetify.display.smAndDown ? '90vw' : 400)"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection
      :title="isEditing ? 'Edit Kategori' : 'Tambah Kategori'"
      @cancel="closeNavigationDrawer"
    />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="name"
                  :rules="[v => !!v || 'Nama kategori wajib diisi']"
                  label="Nama Kategori"
                  placeholder="Mis. Oli & Pelumas"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="description"
                  label="Deskripsi"
                  placeholder="Keterangan singkat tentang kategori ini"
                  rows="3"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  Simpan
                </VBtn>
                <VBtn
                  type="button"
                  variant="outlined"
                  color="error"
                  @click="closeNavigationDrawer"
                >
                  Batal
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
