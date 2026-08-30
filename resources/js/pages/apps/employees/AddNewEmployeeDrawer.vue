<script setup>
import { ref, watch } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedEmployee: {
    type: Object,
    default: null,
  },
  branchesList: {
    type: Array,
    required: true,
  },
  rolesList: {
    type: Array,
    required: true,
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'employeeData',
])

const name = ref('')
const nik = ref('')
const birthPlace = ref('')
const birthDate = ref('')
const gender = ref('')
const religion = ref('')
const maritalStatus = ref('')
const education = ref('')
const phone = ref('')
const email = ref('')
const address = ref('')
const emergencyContactName = ref('')
const emergencyContactPhone = ref('')
const branchId = ref(null)
const roleId = ref(null)
const joinedDate = ref('')
const status = ref('Aktif')

watch(() => props.isDrawerOpen, newVal => {
  if (newVal) {
    if (props.selectedEmployee) {
      name.value = props.selectedEmployee.name
      nik.value = props.selectedEmployee.nik
      birthPlace.value = props.selectedEmployee.birth_place
      birthDate.value = props.selectedEmployee.birth_date
      gender.value = props.selectedEmployee.gender
      religion.value = props.selectedEmployee.religion
      maritalStatus.value = props.selectedEmployee.marital_status
      education.value = props.selectedEmployee.education
      phone.value = props.selectedEmployee.phone
      email.value = props.selectedEmployee.email
      address.value = props.selectedEmployee.address
      emergencyContactName.value = props.selectedEmployee.emergency_contact_name
      emergencyContactPhone.value = props.selectedEmployee.emergency_contact_phone
      branchId.value = props.selectedEmployee.branch_id
      roleId.value = props.selectedEmployee.role_id
      joinedDate.value = props.selectedEmployee.joined_date
      status.value = props.selectedEmployee.status
    } else {
      name.value = ''
      nik.value = ''
      birthPlace.value = ''
      birthDate.value = ''
      gender.value = ''
      religion.value = ''
      maritalStatus.value = ''
      education.value = ''
      phone.value = ''
      email.value = ''
      address.value = ''
      emergencyContactName.value = ''
      emergencyContactPhone.value = ''
      branchId.value = null
      roleId.value = null
      joinedDate.value = ''
      status.value = 'Aktif'
    }
  }
})

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
}

const onSubmit = () => {
  emit('employeeData', {
    name: name.value,
    nik: nik.value,
    birth_place: birthPlace.value,
    birth_date: birthDate.value,
    gender: gender.value,
    religion: religion.value,
    marital_status: maritalStatus.value,
    education: education.value,
    phone: phone.value,
    email: email.value,
    address: address.value,
    emergency_contact_name: emergencyContactName.value,
    emergency_contact_phone: emergencyContactPhone.value,
    branch_id: branchId.value,
    role_id: roleId.value,
    joined_date: joinedDate.value,
    status: status.value,
  })
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="closeNavigationDrawer"
  >
    <!-- 👉 Title -->
    <div class="d-flex align-center pa-6 pb-1">
      <h6 class="text-h6">
        {{ props.selectedEmployee ? 'Edit Karyawan' : 'Tambah Karyawan' }}
      </h6>
      <VSpacer />
      <VBtn
        icon
        variant="tonal"
        color="default"
        size="32"
        @click="closeNavigationDrawer"
      >
        <VIcon
          size="18"
          icon="ri-close-line"
        />
      </VBtn>
    </div>

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <p class="text-subtitle-2 mb-2">
                  Informasi Pekerjaan
                </p>
                <VDivider class="mb-4" />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="branchId"
                  label="Penempatan (Cabang)"
                  :items="props.branchesList"
                  item-title="name"
                  item-value="id"
                  placeholder="Pilih Cabang"
                  required
                />
              </VCol>
              
              <VCol cols="12">
                <VSelect
                  v-model="roleId"
                  label="Jabatan / Hak Akses"
                  :items="[{ id: null, role: 'Tanpa Hak Akses (Biasa)' }, ...props.rolesList]"
                  item-title="role"
                  item-value="id"
                  placeholder="Pilih Hak Akses"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="joinedDate"
                  type="date"
                  label="Tanggal Bergabung"
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="status"
                  label="Status Karyawan"
                  :items="['Aktif', 'Resign', 'Diberhentikan']"
                />
              </VCol>

              <VCol cols="12">
                <p class="text-subtitle-2 mb-2 mt-4">
                  Identitas Pribadi
                </p>
                <VDivider class="mb-4" />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="name"
                  label="Nama Lengkap"
                  placeholder="Masukkan nama"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="nik"
                  label="NIK (Nomor Induk Kependudukan)"
                />
              </VCol>
              
              <VCol cols="6">
                <VTextField
                  v-model="birthPlace"
                  label="Tempat Lahir"
                />
              </VCol>

              <VCol cols="6">
                <VTextField
                  v-model="birthDate"
                  type="date"
                  label="Tanggal Lahir"
                />
              </VCol>
              
              <VCol cols="12">
                <VSelect
                  v-model="gender"
                  label="Jenis Kelamin"
                  :items="['Laki-laki', 'Perempuan']"
                  clearable
                />
              </VCol>
              
              <VCol cols="12">
                <VSelect
                  v-model="religion"
                  label="Agama"
                  :items="['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']"
                  clearable
                />
              </VCol>
              
              <VCol cols="12">
                <VSelect
                  v-model="maritalStatus"
                  label="Status Pernikahan"
                  :items="['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati']"
                  clearable
                />
              </VCol>
              
              <VCol cols="12">
                <VSelect
                  v-model="education"
                  label="Pendidikan Terakhir"
                  :items="['SD', 'SMP', 'SMA/SMK', 'D3', 'S1', 'S2', 'S3']"
                  clearable
                />
              </VCol>

              <VCol cols="12">
                <p class="text-subtitle-2 mb-2 mt-4">
                  Kontak & Alamat
                </p>
                <VDivider class="mb-4" />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="phone"
                  label="Nomor HP"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="email"
                  label="Email"
                  placeholder="Diperlukan jika diberi Hak Akses"
                  :required="roleId !== null"
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="address"
                  label="Alamat Lengkap"
                  rows="3"
                />
              </VCol>

              <VCol cols="12">
                <p class="text-subtitle-2 mb-2 mt-4">
                  Kontak Darurat
                </p>
                <VDivider class="mb-4" />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="emergencyContactName"
                  label="Nama Kontak Darurat"
                />
              </VCol>
              
              <VCol cols="12">
                <VTextField
                  v-model="emergencyContactPhone"
                  label="Nomor HP Kontak Darurat"
                />
              </VCol>

              <!-- 👉 Submit and Cancel -->
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
