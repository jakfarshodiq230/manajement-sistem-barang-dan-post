<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  branchId: {
    type: [Number, String],
    default: null,
  },
  title: {
    type: String,
    default: 'Otorisasi Diperlukan',
  },
  description: {
    type: String,
    default: 'Transaksi ini memerlukan persetujuan dan PIN dari Kepala Cabang / Supervisor.',
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'success',
  'cancel',
])

const pin = ref('')
const isLoading = ref(false)
const errorMsg = ref('')

const supervisors = ref([])
const selectedSupervisor = ref(null)

const fetchSupervisors = async () => {
  try {
    const res = await $api('/apps/users', {
      query: {
        branch_id: props.branchId || undefined,
        itemsPerPage: 100,
      },
    })
    const rawList = res.users || res.data || (Array.isArray(res) ? res : [])
    
    // Filter: Exclude users who only have role 'Kasir' (they cannot authorize)
    const filtered = rawList.filter(u => {
      const roles = (u.roles || []).map(r => (typeof r === 'string' ? r : (r.name || '')).toLowerCase())
      // If user has only 'kasir' role and nothing else, exclude from supervisor dropdown
      if (roles.length === 1 && roles.includes('kasir')) {
        return false
      }
      return true
    })

    supervisors.value = filtered.map(u => ({
      id: u.id,
      name: u.name + (u.roles && u.roles.length > 0 ? ` (${u.roles.map(r => typeof r === 'string' ? r : (r.name || r)).join(', ')})` : ''),
    }))

    if (supervisors.value.length > 0 && !selectedSupervisor.value) {
      selectedSupervisor.value = supervisors.value[0].id
    }
  } catch (error) {
    try {
      const data = await $api('/apps/employees')
      const empList = Array.isArray(data) ? data : (data.data || [])
      supervisors.value = empList.filter(e => e.user_id).map(e => ({
        id: e.user_id,
        name: e.name,
      }))
      if (supervisors.value.length > 0 && !selectedSupervisor.value) {
        selectedSupervisor.value = supervisors.value[0].id
      }
    } catch (e) {
      console.error(e)
    }
  }
}

watch(() => props.isDialogVisible, newVal => {
  if (newVal) {
    pin.value = ''
    errorMsg.value = ''
    fetchSupervisors()
  }
})

const submitApproval = async () => {
  if (!selectedSupervisor.value || !pin.value) {
    errorMsg.value = 'Otorisator dan PIN wajib diisi'
    return
  }

  isLoading.value = true
  errorMsg.value = ''

  try {
    const response = await $api('/apps/verify-pin', {
      method: 'POST',
      body: {
        user_id: selectedSupervisor.value,
        branch_id: props.branchId || undefined,
        pin: pin.value,
      },
    })
    
    // Success! Return the approver's user_id
    emit('success', response.approver_id)
  } catch (error) {
    errorMsg.value = error.data?.message || error.message || 'Otorisasi gagal'
  } finally {
    isLoading.value = false
  }
}

const handleCancel = () => {
  emit('update:isDialogVisible', false)
  emit('cancel')
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    :fullscreen="$vuetify.display.xs"
    max-width="420"
    @update:model-value="val => emit('update:isDialogVisible', val)"
  >
    <VCard>
      <VCardItem class="bg-error text-white pa-4">
        <VCardTitle class="d-flex align-center text-white text-h6 font-weight-bold">
          <VIcon
            icon="ri-shield-keyhole-line"
            class="me-2"
            size="24"
          />
          {{ props.title }}
        </VCardTitle>
      </VCardItem>

      <VCardText class="pt-6">
        <p class="mb-4 text-body-1 text-medium-emphasis">
          {{ props.description }}
        </p>

        <VAutocomplete
          v-model="selectedSupervisor"
          :items="supervisors"
          item-title="name"
          item-value="id"
          label="Pilih Otorisator / Supervisor"
          placeholder="Cari nama supervisor"
          class="mb-4"
          variant="outlined"
          density="comfortable"
          no-data-text="Tidak ada supervisor untuk cabang ini"
        />

        <VTextField
          v-model="pin"
          type="password"
          label="Masukkan PIN Rahasia (6 Digit)"
          placeholder="••••••"
          :error-messages="errorMsg"
          autocomplete="off"
          variant="outlined"
          density="comfortable"
          maxlength="6"
          @keyup.enter="submitApproval"
        />
      </VCardText>

      <VCardActions class="pa-4 pt-0 justify-end">
        <VBtn
          color="secondary"
          variant="outlined"
          :disabled="isLoading"
          @click="handleCancel"
        >
          Batal Transaksi
        </VBtn>
        <VBtn
          color="error"
          variant="elevated"
          :loading="isLoading"
          @click="submitApproval"
        >
          Otorisasi & Lanjutkan
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
