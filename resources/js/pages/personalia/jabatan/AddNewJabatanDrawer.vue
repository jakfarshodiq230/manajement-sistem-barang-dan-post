<script setup>
import { ref, watch, nextTick } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  position: {
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
  department: '',
  level: 1,
  base_salary: 0,
  default_allowance: 0,
  default_deduction: 0,
  description: '',
  status: 'Aktif',
})

const resetForm = () => {
  formData.value = {
    id: null,
    name: '',
    department: '',
    level: 1,
    base_salary: 0,
    default_allowance: 0,
    default_deduction: 0,
    description: '',
    status: 'Aktif',
  }
  nextTick(() => {
    refForm.value?.resetValidation()
  })
}

watch(() => props.isDrawerOpen, val => {
  if (val) {
    if (props.position) {
      formData.value = { ...props.position }
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

import { computed } from 'vue'

const formattedBaseSalary = computed(() => {
  if (formData.value.base_salary === null || formData.value.base_salary === undefined || formData.value.base_salary === '') return ''
  return new Intl.NumberFormat('id-ID').format(formData.value.base_salary)
})

const updateBaseSalary = (val) => {
  if (!val) {
    formData.value.base_salary = 0
    return
  }
  // Remove non-numeric characters
  const numericString = val.toString().replace(/[^0-9]/g, '')
  formData.value.base_salary = numericString ? parseInt(numericString, 10) : 0
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
        {{ props.position ? 'Edit Jabatan' : 'Tambah Jabatan' }}
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
                  label="Nama Jabatan"
                  placeholder="Contoh: Kasir"
                  :rules="[v => !!v || 'Nama Jabatan wajib diisi']"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="formData.department"
                  label="Departemen"
                  placeholder="Contoh: Operasional"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model.number="formData.level"
                  label="Level"
                  type="number"
                  placeholder="1"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  :model-value="formattedBaseSalary"
                  label="Gaji Pokok"
                  prefix="Rp"
                  @update:model-value="updateBaseSalary"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model.number="formData.default_allowance"
                  label="Standar Tunjangan / Bonus (Default)"
                  prefix="Rp"
                  type="number"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model.number="formData.default_deduction"
                  label="Standar Potongan (Default)"
                  prefix="Rp"
                  type="number"
                />
              </VCol>
              
              <VCol cols="12">
                <VTextarea
                  v-model="formData.description"
                  label="Deskripsi Tugas"
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

              <VCol cols="12">
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
