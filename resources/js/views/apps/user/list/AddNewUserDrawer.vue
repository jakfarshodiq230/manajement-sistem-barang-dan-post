<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'userData',
])

const isFormValid = ref(false)
const refForm = ref()
const fullName = ref('')
const userName = ref('')
const email = ref('')
const company = ref('')
const country = ref()
const contact = ref('')
const plan = ref()
const status = ref()

// Branch-role assignments
const assignments = ref([{ branch_id: null, role: '' }])
const availableBranches = ref([])
const availableRoles = ref([])

onMounted(async () => {
  try {
    const [branchData, roleData] = await Promise.all([
      $api('/apps/branches'),
      $api('/apps/roles'),
    ])

    availableBranches.value = branchData.data || branchData || []
    const rawRoles = roleData.data || roleData || []
    availableRoles.value = Array.isArray(rawRoles) ? rawRoles.map(r => r.role || r.name) : []
  } catch (error) {
    console.error(error)
  }
})

const addAssignment = () => {
  assignments.value.push({ branch_id: null, role: '' })
}

const removeAssignment = idx => {
  assignments.value.splice(idx, 1)
}

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    assignments.value = [{ branch_id: null, role: '' }]
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('userData', {
        id: 0,
        fullName: fullName.value,
        company: company.value,
        username: userName.value,
        country: country.value,
        contact: contact.value,
        email: email.value,
        currentPlan: plan.value,
        status: status.value,
        avatar: '',
        assignments: assignments.value.filter(a => a.branch_id && a.role),
      })
      emit('update:isDrawerOpen', false)
      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
        assignments.value = [{ branch_id: null, role: '' }]
      })
    }
  })
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    data-allow-mismatch
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <AppDrawerHeaderSection
      title="Add User"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- 👉 Full name -->
              <VCol cols="12">
                <VTextField
                  v-model="fullName"
                  :rules="[requiredValidator]"
                  label="Full Name"
                  placeholder="John Doe"
                />
              </VCol>

              <!-- 👉 Username -->
              <VCol cols="12">
                <VTextField
                  v-model="userName"
                  :rules="[requiredValidator]"
                  label="Username"
                  placeholder="Johndoe"
                />
              </VCol>

              <!-- 👉 Email -->
              <VCol cols="12">
                <VTextField
                  v-model="email"
                  :rules="[requiredValidator, emailValidator]"
                  label="Email"
                  placeholder="johndoe@email.com"
                />
              </VCol>

              <!-- 👉 company -->
              <VCol cols="12">
                <VTextField
                  v-model="company"
                  :rules="[requiredValidator]"
                  label="Company"
                  placeholder="Pixinvent"
                />
              </VCol>

              <!-- 👉 Country -->
              <VCol cols="12">
                <VSelect
                  v-model="country"
                  label="Select Country"
                  placeholder="Select Country"
                  :rules="[requiredValidator]"
                  :items="['United States', 'United Kingdom', 'France']"
                />
              </VCol>

              <!-- 👉 Contact -->
              <VCol cols="12">
                <VTextField
                  v-model="contact"
                  type="number"
                  :rules="[requiredValidator]"
                  label="Contact"
                  placeholder="+1-541-754-3010"
                />
              </VCol>

              <!-- 👉 Penugasan (Cabang + Jabatan) -->
              <VCol cols="12">
                <div class="text-subtitle-2 mb-2 font-weight-medium">
                  Penugasan Jabatan
                </div>
                <div
                  v-for="(assignment, idx) in assignments"
                  :key="idx"
                  class="d-flex gap-2 mb-2 align-center"
                >
                  <VSelect
                    v-model="assignment.branch_id"
                    :items="availableBranches"
                    item-title="name"
                    item-value="id"
                    label="Cabang/Toko"
                    density="compact"
                    hide-details
                    style="min-width:150px"
                  />
                  <VSelect
                    v-model="assignment.role"
                    :items="availableRoles"
                    label="Jabatan"
                    density="compact"
                    hide-details
                    style="min-width:130px"
                  />
                  <IconBtn
                    color="error"
                    size="small"
                    :disabled="assignments.length === 1"
                    @click="removeAssignment(idx)"
                  >
                    <VIcon
                      icon="ri-delete-bin-7-line"
                      size="16"
                    />
                  </IconBtn>
                </div>
                <VBtn
                  variant="tonal"
                  color="primary"
                  size="x-small"
                  prepend-icon="ri-add-line"
                  class="mt-1"
                  @click="addAssignment"
                >
                  Tambah Cabang
                </VBtn>
              </VCol>

              <!-- 👉 Plan -->
              <VCol cols="12">
                <VSelect
                  v-model="plan"
                  label="Select Plan"
                  placeholder="Select Plan"
                  :items="['Basic', 'Company', 'Enterprise', 'Team']"
                />
              </VCol>

              <!-- 👉 Status -->
              <VCol cols="12">
                <VSelect
                  v-model="status"
                  label="Select Status"
                  placeholder="Select Status"
                  :items="[{ title: 'Active', value: 'Active' }, { title: 'Inactive', value: 'Inactive' }, { title: 'Pending', value: 'Pending' }]"
                />
              </VCol>

              <!-- 👉 Submit and Cancel -->
              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-4"
                >
                  Submit
                </VBtn>
                <VBtn
                  type="reset"
                  variant="outlined"
                  color="error"
                  @click="closeNavigationDrawer"
                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
