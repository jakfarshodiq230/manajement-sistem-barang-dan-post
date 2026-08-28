<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
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
    // For demo: get employees, we assume head of branches are here
    const data = await $api('/apps/employees')

    supervisors.value = data.data || data
    if (data.length > 0) {
      selectedSupervisor.value = data[0].user_id
    }
  } catch (error) {
    console.error(error)
  }
}

watch(() => props.isDialogVisible, newVal => {
  if (newVal) {
    pin.value = ''
    errorMsg.value = ''
    if (supervisors.value.length === 0) {
      fetchSupervisors()
    }
  }
})

const submitApproval = async () => {
  if (!selectedSupervisor.value || !pin.value) {
    errorMsg.value = 'Supervisor dan PIN wajib diisi'
    
    return
  }

  isLoading.value = true
  errorMsg.value = ''

  try {
    const response = await $api('/apps/verify-pin', {
      method: 'POST',
      body: {
        user_id: selectedSupervisor.value,
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
  emit('cancel')
}
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    max-width="400"
    persistent
  >
    <VCard>
      <VCardItem class="bg-error text-white pa-4">
        <VCardTitle class="d-flex align-center text-white">
          <VIcon
            icon="ri-lock-password-line"
            class="me-2"
            size="24"
          />
          {{ props.title }}
        </VCardTitle>
      </VCardItem>

      <VCardText class="pt-6">
        <p class="mb-6 text-body-1">
          {{ props.description }}
        </p>

        <VAutocomplete
          v-model="selectedSupervisor"
          :items="supervisors"
          :item-title="item => item.user ? item.user.name : (item.name || 'Unknown')"
          item-value="user_id"
          label="Pilih Otorisator"
          class="mb-4"
        />

        <VTextField
          v-model="pin"
          type="password"
          label="Masukkan PIN Rahasia"
          placeholder="••••••"
          :error-messages="errorMsg"
          autocomplete="off"
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
