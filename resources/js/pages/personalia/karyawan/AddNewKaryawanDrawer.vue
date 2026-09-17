<script setup>
import { ref, watch, computed } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedData: {
    type: Object,
    default: () => null,
  },
  branchesList: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'saveData',
])

const isFormValid = ref(false)
const refForm = ref()
const localData = ref({
  id: null,
  name: '',
  nik: '',
  birth_place: '',
  birth_date: '',
  gender: 'L',
  religion: '',
  marital_status: '',
  education: '',
  phone: '',
  email: '',
  address: '',
  emergency_contact_name: '',
  emergency_contact_phone: '',
  branch_id: null,
  joined_date: '',
  status: 'Aktif',
  position_id: null,
  custom_base_salary: null,
  custom_allowance: null,
  custom_deduction: null,
  bank_name: '',
  bank_account_number: '',
  attendance_machine_id: '',
  deduction_type_ids: [],
})

const resetForm = () => {
  localData.value = {
    id: null,
    name: '',
    nik: '',
    birth_place: '',
    birth_date: '',
    gender: 'L',
    religion: '',
    marital_status: '',
    education: '',
    phone: '',
    email: '',
    address: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    branch_id: null,
    joined_date: '',
    status: 'Aktif',
    position_id: null,
    custom_base_salary: null,
    custom_allowance: null,
    custom_deduction: null,
    bank_name: '',
    bank_account_number: '',
    attendance_machine_id: '',
    deduction_type_ids: [],
  }
}

import { onMounted } from 'vue'
const positionsList = ref([])
const deductionTypesList = ref([])

const fetchPositions = async () => {
  try {
    const res = await $api('/apps/positions?itemsPerPage=-1')
    if (res && res.data) {
      positionsList.value = res.data
    } else if (Array.isArray(res)) {
      positionsList.value = res
    }
  } catch (e) {
    console.error('Failed to load positions', e)
  }
}

const fetchDeductionTypes = async () => {
  try {
    const res = await $api('/apps/deduction-types?itemsPerPage=-1')
    if (res && res.data) {
      deductionTypesList.value = res.data.filter(d => d.status === 'Aktif')
    } else if (Array.isArray(res)) {
      deductionTypesList.value = res.filter(d => d.status === 'Aktif')
    }
  } catch (e) {
    console.error('Failed to load deduction types', e)
  }
}

onMounted(() => {
  fetchPositions()
  fetchDeductionTypes()
})

watch(
  () => props.selectedData,
  newVal => {
    if (newVal) {
      let dTypeIds = []
      if (newVal.deductionTypes) {
        dTypeIds = newVal.deductionTypes.map(d => d.id)
      } else if (newVal.deduction_type_ids) {
        dTypeIds = newVal.deduction_type_ids
      }
      
      localData.value = { 
        ...newVal, 
        status: newVal.status === 'Aktif' ? 'Aktif' : 'Nonaktif',
        deduction_type_ids: dTypeIds
      }
    } else {
      resetForm()
    }
  },
  { immediate: true },
)

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('saveData', { ...localData.value })
      emit('update:isDrawerOpen', false)
      resetForm()
    }
  })
}

const dialogTitle = computed(() => props.selectedData ? 'Edit Data Karyawan' : 'Tambah Karyawan Baru')

const requiredValidator = val => !!val || 'Kolom ini wajib diisi'
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
    <!-- Header -->
    <div class="d-flex align-center pa-6 pb-1">
      <h6 class="text-h6">
        {{ dialogTitle }}
      </h6>
      <VSpacer />
      <VBtn
        icon="ri-close-line"
        variant="text"
        color="default"
        @click="handleDrawerModelValueUpdate(false)"
      />
    </div>
    
    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <!-- Name -->
              <VCol cols="12">
                <VTextField
                  v-model="localData.name"
                  label="Nama Lengkap Karyawan"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <!-- NIK -->
              <VCol cols="12">
                <VTextField
                  v-model="localData.nik"
                  label="NIK / Nomor KTP"
                  :rules="[requiredValidator]"
                />
              </VCol>
              
              <!-- Phone -->
              <VCol cols="12">
                <VTextField
                  v-model="localData.phone"
                  label="No. Handphone / WhatsApp"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <!-- Email -->
              <VCol cols="12">
                <VTextField
                  v-model="localData.email"
                  label="Email (Opsional)"
                  type="email"
                />
              </VCol>

              <!-- Branch -->
              <VCol cols="12">
                <VSelect
                  v-model="localData.branch_id"
                  :items="props.branchesList"
                  item-title="name"
                  item-value="id"
                  label="Penempatan Cabang"
                  clearable
                />
              </VCol>
              
              <!-- Gender & Status -->
              <VCol cols="12" md="6">
                <VSelect
                  v-model="localData.gender"
                  :items="[{title: 'Laki-Laki', value: 'L'}, {title: 'Perempuan', value: 'P'}]"
                  label="Jenis Kelamin"
                />
              </VCol>
              
              <VCol cols="12" md="6">
                <VSelect
                  v-model="localData.status"
                  :items="['Aktif', 'Nonaktif']"
                  label="Status Karyawan"
                />
              </VCol>
              
              <!-- Joined Date -->
              <VCol cols="12">
                <VTextField
                  v-model="localData.joined_date"
                  label="Tanggal Bergabung"
                  type="date"
                />
              </VCol>

              <!-- Address -->
              <VCol cols="12">
                <VTextarea
                  v-model="localData.address"
                  label="Alamat Lengkap"
                  rows="2"
                />
              </VCol>
              
              <VCol cols="12">
                <VDivider class="my-2" />
                <p class="text-caption font-weight-medium mb-1">Kontak Darurat (Opsional)</p>
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="localData.emergency_contact_name"
                  label="Nama Kontak"
                  density="compact"
                />
              </VCol>
              
              <VCol cols="12" md="6">
                <VTextField
                  v-model="localData.emergency_contact_phone"
                  label="No HP Kontak"
                  density="compact"
                />
              </VCol>

              <VCol cols="12">
                <VDivider class="my-2" />
                <p class="text-caption font-weight-medium mb-1">Data HR & Penggajian</p>
              </VCol>

              <!-- Position -->
              <VCol cols="12">
                <VSelect
                  v-model="localData.position_id"
                  :items="positionsList"
                  item-title="name"
                  item-value="id"
                  label="Jabatan Karyawan"
                  clearable
                />
              </VCol>

              <!-- Attendance Machine ID -->
              <VCol cols="12">
                <VTextField
                  v-model="localData.attendance_machine_id"
                  label="ID Mesin Absensi"
                  placeholder="Contoh: 101"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="localData.custom_base_salary"
                  label="Gaji Pokok Khusus (Override)"
                  prefix="Rp"
                  type="number"
                  placeholder="Kosongkan jika ikut jabatan"
                />
              </VCol>
              
              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="localData.custom_allowance"
                  label="Tunjangan Khusus (Override)"
                  prefix="Rp"
                  type="number"
                  placeholder="Kosongkan jika ikut jabatan"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model.number="localData.custom_deduction"
                  label="Potongan Khusus (Override)"
                  prefix="Rp"
                  type="number"
                  placeholder="Kosongkan jika ikut jabatan"
                />
              </VCol>

              <!-- Deduction Types -->
              <VCol cols="12">
                <VSelect
                  v-model="localData.deduction_type_ids"
                  :items="deductionTypesList"
                  item-title="name"
                  item-value="id"
                  label="Pilih Master Potongan & Tunjangan"
                  multiple
                  chips
                  clearable
                />
              </VCol>

              <!-- Bank Details -->
              <VCol cols="12">
                <VDivider class="my-2" />
                <p class="text-caption font-weight-medium mb-1">Informasi Bank</p>
              </VCol>
              
              <VCol cols="12" md="6">
                <VTextField
                  v-model="localData.bank_name"
                  label="Nama Bank"
                  placeholder="BCA / Mandiri / BNI"
                />
              </VCol>

              <VCol cols="12" md="6">
                <VTextField
                  v-model="localData.bank_account_number"
                  label="Nomor Rekening"
                />
              </VCol>

              <!-- Action Buttons -->
              <VCol cols="12" class="d-flex gap-4">
                <VBtn type="submit" color="primary">Simpan</VBtn>
                <VBtn
                  color="secondary"
                  variant="outlined"
                  @click="handleDrawerModelValueUpdate(false)"
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
