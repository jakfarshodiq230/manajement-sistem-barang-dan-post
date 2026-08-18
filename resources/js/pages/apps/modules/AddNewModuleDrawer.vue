<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  selectedModule: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'moduleData',
])

const isFormValid = ref(false)
const refForm = ref()
const name = ref('')
const slug = ref('')
const icon = ref('')
const sequence = ref(0)
const parent_id = ref(null)
const category = ref('')

const parentModules = ref([])

onMounted(async () => {
  try {
    const data = await $api('/apps/modules?all=true')

    parentModules.value = data.data || data
  } catch (e) {
    console.error(e)
  }
})

watch(
  () => props.selectedModule,
  newModule => {
    if (newModule) {
      name.value = newModule.name || ''
      slug.value = newModule.slug || ''
      icon.value = newModule.icon || ''
      sequence.value = newModule.sequence || 0
      parent_id.value = newModule.parent_id || null
      category.value = newModule.category || ''
    } else {
      name.value = ''
      slug.value = ''
      icon.value = ''
      sequence.value = 0
      parent_id.value = null
      category.value = ''
    }
  },
  { immediate: true },
)

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('moduleData', {
        name: name.value,
        slug: slug.value,
        icon: icon.value,
        sequence: sequence.value,
        parent_id: parent_id.value,
        category: category.value,
      })
      emit('update:isDrawerOpen', false)
      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
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
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <AppDrawerHeaderSection
      :title="props.selectedModule ? 'Edit Module' : 'Add New Module'"
      @cancel="closeNavigationDrawer"
    />

    <VDivider />

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
                  :rules="[requiredValidator]"
                  label="Module Name"
                  placeholder="e.g. User Management"
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="parent_id"
                  :items="parentModules"
                  item-title="name"
                  item-value="id"
                  label="Parent Module (Optional)"
                  placeholder="Select Parent Module"
                  clearable
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="slug"
                  label="Slug (Optional)"
                  placeholder="e.g. user-management"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="icon"
                  label="Icon"
                  placeholder="e.g. ri-user-line"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="sequence"
                  type="number"
                  label="Sequence"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="category"
                  label="Kategori / Judul Grup (Opsional)"
                  placeholder="e.g. Umum"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                >
                  Submit
                </VBtn>
                <VBtn
                  type="reset"
                  variant="outlined"
                  color="secondary"
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
