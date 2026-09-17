<script setup>
import { ref, watch, nextTick } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedData: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'submit',
])

const isFormValid = ref(false)
const refForm = ref()

const formData = ref({
  id: null,
  name: '',
  type: 'potongan',
  amount: 0,
  is_percentage: false,
  status: 'Aktif',
  description: '',
})

const resetForm = () => {
  formData.value = {
    id: null,
    name: '',
    type: 'potongan',
    amount: 0,
    is_percentage: false,
    status: 'Aktif',
    description: '',
  }
  nextTick(() => {
    refForm.value?.resetValidation()
  })
}

watch(() => props.isDrawerOpen, val => {
  if (val) {
    if (props.selectedData) {
      formData.value = { ...props.selectedData, is_percentage: Boolean(props.selectedData.is_percentage) }
    } else {
      resetForm()
    }
  }
})

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('submit', formData.value)
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
}

</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  
      disable-resize-watcher>
    <div class="d-flex align-center pa-6 pb-1">
      <h6 class="text-h6">
        {{ props.selectedData ? 'Edit Master Potongan' : 'Tambah Master Potongan' }}
      </h6>
      <VSpacer />
      <VBtn
        icon="ri-close-line"
        variant="text"
        color="default"
        size="small"
        @click="closeNavigationDrawer"
      />
    </div>

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
                  v-model="formData.name"
                  label="Nama Potongan / Tunjangan"
                  placeholder="Contoh: BPJS Ketenagakerjaan"
                  :rules="[v => !!v || 'Wajib diisi']"
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="formData.type"
                  label="Jenis"
                  :items="[{title: 'Potongan (Mengurangi Gaji)', value: 'potongan'}, {title: 'Tunjangan (Menambah Gaji)', value: 'tunjangan'}]"
                />
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="formData.is_percentage"
                  label="Gunakan Persentase (%) dari Gaji Pokok"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model.number="formData.amount"
                  :label="formData.is_percentage ? 'Persentase (%)' : 'Nominal (Rp)'"
                  :prefix="formData.is_percentage ? '' : 'Rp'"
                  :suffix="formData.is_percentage ? '%' : ''"
                  type="number"
                  :rules="[v => v !== null || 'Wajib diisi']"
                />
              </VCol>
              
              <VCol cols="12">
                <VTextarea
                  v-model="formData.description"
                  label="Deskripsi / Keterangan"
                  rows="3"
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="formData.status"
                  label="Status"
                  :items="['Aktif', 'Nonaktif']"
                />
              </VCol>

              <VCol cols="12" class="mt-4">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  Simpan
                </VBtn>
                <VBtn
                  variant="outlined"
                  color="secondary"
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
