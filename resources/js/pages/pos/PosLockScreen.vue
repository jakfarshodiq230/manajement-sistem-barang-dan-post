<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

const props = defineProps({
  isLocked: {
    type: Boolean,
    default: false,
  },
  userData: {
    type: Object,
    default: () => ({}),
  },
  activeBranchName: {
    type: String,
    default: 'Cabang Utama',
  },
  hasActiveShift: {
    type: Boolean,
    default: false,
  },
  cartCount: {
    type: Number,
    default: 0,
  },
  cartTotal: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['unlock', 'logout'])

const snackbar = useSnackbarStore()

const pin = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')
const isShaking = ref(false)

// Real-time Clock
const currentTime = ref('')
const currentDate = ref('')
let timerInterval = null

const updateClock = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
}

// User Initials
const userInitials = computed(() => {
  const name = props.userData?.fullName || props.userData?.name || props.userData?.username || 'Kasir'
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
})

const formatRupiah = val => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0)
}

// Keypad Actions
const pressDigit = num => {
  if (pin.value.length < 6) {
    pin.value += num.toString()
    errorMessage.value = ''
    if (pin.value.length === 6) {
      submitUnlock()
    }
  }
}

const clearPin = () => {
  pin.value = ''
  errorMessage.value = ''
}

const backspace = () => {
  if (pin.value.length > 0) {
    pin.value = pin.value.slice(0, -1)
    errorMessage.value = ''
  }
}

// Physical Keyboard Listener
const handleGlobalKey = e => {
  if (!props.isLocked) return

  // Prevent background actions
  e.stopPropagation()

  if (e.key >= '0' && e.key <= '9') {
    e.preventDefault()
    pressDigit(e.key)
  } else if (e.key === 'Backspace') {
    e.preventDefault()
    backspace()
  } else if (e.key === 'Escape' || e.key.toLowerCase() === 'c') {
    e.preventDefault()
    clearPin()
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (pin.value.length > 0) {
      submitUnlock()
    }
  }
}

// Submit Unlock
const submitUnlock = async () => {
  if (!pin.value) {
    errorMessage.value = 'Masukkan 6 digit PIN'
    triggerShake()
    return
  }

  isSubmitting.value = true
  errorMessage.value = ''

  try {
    const payload = {
      pin: pin.value,
      purpose: 'unlock',
      user_id: props.userData?.id,
    }

    const res = await $api('/apps/verify-pin', {
      method: 'POST',
      body: payload,
    })

    snackbar.show(res.message || 'Layar POS berhasil dibuka!', 'success')
    pin.value = ''
    emit('unlock')
  } catch (err) {
    const msg = err.data?.message || err.message || 'PIN salah!'
    errorMessage.value = msg
    triggerShake()
    pin.value = ''
  } finally {
    isSubmitting.value = false
  }
}

const triggerShake = () => {
  isShaking.value = true
  setTimeout(() => {
    isShaking.value = false
  }, 600)
}

// Logout Confirmation
const isConfirmLogoutVisible = ref(false)
const handleLogout = () => {
  isConfirmLogoutVisible.value = false
  emit('logout')
}

watch(() => props.isLocked, (val) => {
  if (val) {
    pin.value = ''
    errorMessage.value = ''
    updateClock()
    window.addEventListener('keydown', handleGlobalKey, true)
  } else {
    window.removeEventListener('keydown', handleGlobalKey, true)
  }
})

onMounted(() => {
  updateClock()
  timerInterval = setInterval(updateClock, 1000)
  if (props.isLocked) {
    window.addEventListener('keydown', handleGlobalKey, true)
  }
})

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval)
  window.removeEventListener('keydown', handleGlobalKey, true)
})
</script>

<template>
  <Transition name="fade">
    <div v-if="isLocked" class="pos-lock-overlay">
      <div class="pos-lock-card" :class="{ 'shake-anim': isShaking }">
        
        <!-- Compact Clock & Header -->
        <div class="d-flex align-center justify-space-between mb-2">
          <div class="d-flex align-center gap-1 text-slate-700">
            <VIcon icon="ri-lock-2-line" size="16" class="text-warning" />
            <span class="font-weight-bold text-caption text-uppercase" style="letter-spacing: 0.5px;">POS Terkunci</span>
          </div>
          <div class="text-right">
            <div class="pos-lock-time">{{ currentTime }}</div>
            <div class="pos-lock-date">{{ currentDate }}</div>
          </div>
        </div>

        <!-- Cashier & Session Compact Badge -->
        <div class="pos-cashier-badge d-flex align-center gap-2 mb-3">
          <VAvatar color="primary" size="32" class="font-weight-bold text-caption">
            {{ userInitials }}
          </VAvatar>
          <div class="text-start flex-grow-1 overflow-hidden" style="line-height: 1.2;">
            <div class="d-flex align-center justify-space-between">
              <span class="font-weight-bold text-caption text-slate-800 text-truncate" style="max-width: 140px;">
                {{ userData?.fullName || userData?.name || 'Kasir' }}
              </span>
              <span v-if="hasActiveShift" class="pos-shift-indicator active">Shift Aktif</span>
            </div>
            <div class="text-slate-500 d-flex align-center gap-1" style="font-size: 11px;">
              <span>{{ activeBranchName }}</span>
              <span v-if="cartCount > 0" class="text-primary font-weight-bold ms-auto">
                {{ cartCount }} item
              </span>
            </div>
          </div>
        </div>

        <!-- Masked PIN Indicators -->
        <div class="pos-pin-dots d-flex justify-center align-center gap-2 my-2">
          <div
            v-for="i in 6"
            :key="i"
            class="pos-pin-dot"
            :class="{ 'filled': pin.length >= i, 'active': pin.length === i - 1 }"
          ></div>
        </div>

        <!-- Error Alert (if any) -->
        <div v-if="errorMessage" class="pos-lock-error-alert d-flex align-center justify-center gap-1 mb-2">
          <VIcon icon="ri-error-warning-line" size="13" color="error" />
          <span>{{ errorMessage }}</span>
        </div>

        <!-- Compact Numeric Keypad -->
        <div class="pos-num-keypad">
          <button
            v-for="n in [1, 2, 3, 4, 5, 6, 7, 8, 9]"
            :key="n"
            type="button"
            class="pos-key-btn"
            @click="pressDigit(n)"
          >
            {{ n }}
          </button>
          
          <!-- Clear Button -->
          <button
            type="button"
            class="pos-key-btn pos-key-action text-error"
            title="Hapus (C)"
            @click="clearPin"
          >
            C
          </button>

          <!-- Zero Button -->
          <button
            type="button"
            class="pos-key-btn"
            @click="pressDigit(0)"
          >
            0
          </button>

          <!-- Backspace Button -->
          <button
            type="button"
            class="pos-key-btn pos-key-action"
            title="Backspace"
            @click="backspace"
          >
            <VIcon icon="ri-delete-back-2-line" size="18" />
          </button>
        </div>

        <!-- Submit & Actions -->
        <div class="mt-3 d-flex flex-column gap-1">
          <VBtn
            block
            color="primary"
            size="small"
            rounded="lg"
            class="font-weight-bold shadow-sm text-none"
            style="height: 38px;"
            :loading="isSubmitting"
            :disabled="pin.length === 0"
            prepend-icon="ri-lock-unlock-line"
            @click="submitUnlock"
          >
            Buka Kunci Layar
          </VBtn>

          <button
            type="button"
            class="pos-logout-link text-slate-400 mt-1"
            style="font-size: 11px;"
            @click="isConfirmLogoutVisible = true"
          >
            <VIcon icon="ri-logout-box-r-line" size="12" class="me-1" />
            Ganti Kasir / Keluar
          </button>
        </div>

      </div>
    </div>
  </Transition>

  <!-- Logout Confirmation Dialog -->
  <VDialog v-model="isConfirmLogoutVisible" max-width="360" persistent>
    <VCard class="pa-4 rounded-xl text-center">
      <VAvatar color="error" variant="tonal" size="44" class="mb-2 mx-auto">
        <VIcon icon="ri-logout-box-r-line" size="24" />
      </VAvatar>
      <h3 class="text-subtitle-1 font-weight-bold text-slate-800 mb-1">Konfirmasi Keluar?</h3>
      <p class="text-caption text-slate-600 mb-3">
        Sesi kasir akan diakhiri. Transaksi tertahan tetap tersimpan.
      </p>
      <VRow dense class="mt-2">
        <VCol cols="6">
          <VBtn
            block
            size="small"
            variant="tonal"
            color="secondary"
            class="text-none"
            @click="isConfirmLogoutVisible = false"
          >
            Batal
          </VBtn>
        </VCol>
        <VCol cols="6">
          <VBtn
            block
            size="small"
            color="error"
            class="font-weight-bold text-none"
            @click="handleLogout"
          >
            Ya, Keluar
          </VBtn>
        </VCol>
      </VRow>
    </VCard>
  </VDialog>
</template>

<style scoped>
.pos-lock-overlay {
  position: fixed;
  inset: 0;
  z-index: 99999;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
  user-select: none;
}

.pos-lock-card {
  width: 100%;
  max-width: 320px;
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 20px;
  padding: 18px 18px 14px 18px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
}

.pos-lock-time {
  font-size: 1.25rem;
  font-weight: 800;
  color: #1e293b;
  letter-spacing: 0.5px;
  line-height: 1.1;
  font-family: monospace, system-ui, sans-serif;
}

.pos-lock-date {
  font-size: 0.7rem;
  font-weight: 600;
  color: #64748b;
}

.pos-cashier-badge {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 6px 10px;
}

.pos-shift-indicator {
  font-size: 9px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 8px;
  text-transform: uppercase;
}

.pos-shift-indicator.active {
  background: #dcfce7;
  color: #15803d;
}

.pos-pin-dots {
  height: 22px;
}

.pos-pin-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 2px solid #cbd5e1;
  background: transparent;
  transition: all 0.15s ease;
}

.pos-pin-dot.filled {
  background: #6366f1;
  border-color: #6366f1;
  box-shadow: 0 0 6px rgba(99, 102, 241, 0.4);
  transform: scale(1.1);
}

.pos-pin-dot.active {
  border-color: #6366f1;
}

.pos-lock-error-alert {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  font-size: 11px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 8px;
}

.pos-num-keypad {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6px;
}

.pos-key-btn {
  height: 38px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1.15rem;
  font-weight: 700;
  color: #1e293b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.1s ease;
  user-select: none;
}

.pos-key-btn:hover {
  background: #e2e8f0;
}

.pos-key-btn:active {
  background: #cbd5e1;
  transform: scale(0.96);
}

.pos-key-btn.pos-key-action {
  font-size: 0.95rem;
  background: #f1f5f9;
}

.pos-logout-link {
  background: transparent;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
  border-radius: 6px;
  transition: all 0.2s;
}

.pos-logout-link:hover {
  color: #ef4444 !important;
  background: #fee2e2;
}

/* Shake Animation */
.shake-anim {
  animation: shake 0.45s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

@keyframes shake {
  10%, 90% { transform: translate3d(-2px, 0, 0); }
  20%, 80% { transform: translate3d(3px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}

/* Fade Transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
