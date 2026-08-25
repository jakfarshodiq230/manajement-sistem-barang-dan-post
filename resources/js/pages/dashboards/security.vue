<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useSnackbarStore } from '@/stores/snackbar'

definePage({
  meta: {
    action: 'read',
    subject: 'Log Keamanan',
  },
})

const snackbar = useSnackbarStore()

// State Data
const logs = ref([])
const summary = ref({
  total_logs_24h: 0,
  threats_detected_24h: 0,
  suspicious_ips_count: 0,
  total_blocked_ips: 0,
  recent_threats: [],
  top_attacking_ips: [],
})
const blockedIps = ref([])

const isLoading = ref(false)
const isSummaryLoading = ref(false)
const isSubmitting = ref(false)

// Filters & Pagination
const search = ref('')
const selectedRisk = ref('all')
const selectedEvent = ref('all')
const selectedBlocked = ref('all')
const page = ref(1)
const itemsPerPage = ref(15)
const totalLogs = ref(0)
let searchTimeout = null
let autoRefreshTimer = null

// Modals
const isDetailDialogVisible = ref(false)
const isBlockDialogVisible = ref(false)
const isBlacklistModalVisible = ref(false)
const isClearLogsDialogVisible = ref(false)

const selectedLog = ref(null)
const blockForm = ref({
  ip_address: '',
  reason: 'Terdeteksi melakukan percobaan serangan / brute-force',
})

// Formatting Helpers
const formatDateTime = dateStr => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

const formatTimeAgo = dateStr => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  const diff = Math.floor((new Date() - d) / 1000)
  if (diff < 60) return `${diff} dtk lalu`
  if (diff < 3600) return `${Math.floor(diff / 60)} mnt lalu`
  if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`
  return `${Math.floor(diff / 86400)} hari lalu`
}

const getRiskColor = level => {
  switch (level) {
    case 'critical': return 'error'
    case 'high': return 'deep-orange'
    case 'medium': return 'warning'
    default: return 'success'
  }
}

const getRiskLabel = level => {
  switch (level) {
    case 'critical': return 'KRITIS / SERANGAN'
    case 'high': return 'MENCURIGAKAN'
    case 'medium': return 'PERINGATAN'
    default: return 'NORMAL / AMAN'
  }
}

const getEventIcon = eventType => {
  switch (eventType) {
    case 'login_success': return 'ri-login-circle-line'
    case 'login_failed': return 'ri-lock-password-line'
    case 'brute_force_attempt': return 'ri-alarm-warning-line'
    case 'unauthorized_access': return 'ri-shield-cross-line'
    case 'sql_injection_attempt': return 'ri-bug-line'
    case 'xss_attempt': return 'ri-code-s-slash-line'
    case 'blocked_ip_access': return 'ri-forbid-2-line'
    case 'data_mutation': return 'ri-edit-line'
    default: return 'ri-global-line'
  }
}

const getDeviceIcon = deviceType => {
  switch (deviceType) {
    case 'Mobile': return 'ri-smartphone-line'
    case 'Tablet': return 'ri-tablet-line'
    case 'Bot / Scanner': return 'ri-robot-line'
    default: return 'ri-computer-line'
  }
}

// Fetch Summary
const fetchSummary = async () => {
  isSummaryLoading.value = true
  try {
    const res = await $api('/apps/security/summary')
    summary.value = { ...summary.value, ...res }
  } catch (error) {
    console.error('Error fetching security summary:', error)
  } finally {
    isSummaryLoading.value = false
  }
}

// Fetch Logs
const fetchLogs = async () => {
  isLoading.value = true
  try {
    const params = {
      page: page.value,
      per_page: itemsPerPage.value,
    }
    if (search.value) params.search = search.value
    if (selectedRisk.value !== 'all') params.risk_level = selectedRisk.value
    if (selectedEvent.value !== 'all') params.event_type = selectedEvent.value
    if (selectedBlocked.value !== 'all') params.is_blocked = selectedBlocked.value

    const res = await $api('/apps/security/logs', { query: params })
    logs.value = res.data || []
    totalLogs.value = res.total || 0
  } catch (error) {
    console.error('Error fetching logs:', error)
    snackbar.show('Gagal memuat riwayat log keamanan', 'error')
  } finally {
    isLoading.value = false
  }
}

// Fetch Blocked IPs
const fetchBlockedIps = async () => {
  try {
    const res = await $api('/apps/security/blocked-ips')
    blockedIps.value = res.data || []
  } catch (error) {
    console.error('Error fetching blocked ips:', error)
  }
}

// Block IP
const openBlockDialog = (ip, reason = '') => {
  blockForm.value = {
    ip_address: ip,
    reason: reason || 'Terdeteksi aktivitas mencurigakan / percobaan serangan',
  }
  isBlockDialogVisible.value = true
}

const handleBlockIp = async () => {
  if (!blockForm.value.ip_address) return
  isSubmitting.value = true
  try {
    const res = await $api('/apps/security/block-ip', {
      method: 'POST',
      body: blockForm.value,
    })
    snackbar.show(res.message || 'Alamat IP berhasil diblokir.', 'success')
    isBlockDialogVisible.value = false
    if (isDetailDialogVisible.value) isDetailDialogVisible.value = false
    fetchSummary()
    fetchLogs()
    fetchBlockedIps()
  } catch (error) {
    console.error('Error blocking IP:', error)
    snackbar.show(error.response?._data?.message || 'Gagal memblokir IP', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// Unblock IP
const handleUnblockIp = async ip => {
  try {
    const res = await $api('/apps/security/unblock-ip', {
      method: 'POST',
      body: { ip_address: ip },
    })
    snackbar.show(res.message || 'Blokir IP berhasil dibuka.', 'info')
    fetchSummary()
    fetchLogs()
    fetchBlockedIps()
  } catch (error) {
    console.error('Error unblocking IP:', error)
    snackbar.show('Gagal membuka blokir IP', 'error')
  }
}

// Clear Old Logs
const handleClearOldLogs = async () => {
  isSubmitting.value = true
  try {
    const res = await $api('/apps/security/logs/clear', { method: 'DELETE' })
    snackbar.show(res.message || 'Log lama berhasil dibersihkan.', 'success')
    isClearLogsDialogVisible.value = false
    fetchSummary()
    fetchLogs()
  } catch (error) {
    console.error('Error clearing logs:', error)
    snackbar.show('Gagal membersihkan log', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// Show Detail Modal
const showDetail = log => {
  selectedLog.value = log
  isDetailDialogVisible.value = true
}

// Copy IP Helper
const copyToClipboard = text => {
  navigator.clipboard.writeText(text)
  snackbar.show(`Alamat IP ${text} disalin ke clipboard`, 'info')
}

// Watchers
watch([selectedRisk, selectedEvent, selectedBlocked], () => {
  page.value = 1
  fetchLogs()
})

watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    fetchLogs()
  }, 400)
})

watch([page, itemsPerPage], () => {
  fetchLogs()
})

onMounted(() => {
  fetchSummary()
  fetchLogs()
  fetchBlockedIps()

  // Auto refresh logs every 30 seconds
  autoRefreshTimer = setInterval(() => {
    fetchSummary()
    fetchLogs()
  }, 30000)
})

onUnmounted(() => {
  if (autoRefreshTimer) clearInterval(autoRefreshTimer)
})
</script>

<template>
  <div class="pa-4">
    <!-- Header Banner -->
    <VCard elevation="2" class="mb-4 pa-4 rounded-xl border bg-var-theme-surface">
      <div class="d-flex flex-wrap align-center justify-space-between gap-4">
        <!-- Title -->
        <div class="d-flex align-center gap-3">
          <VAvatar color="error" variant="tonal" rounded size="48">
            <VIcon icon="ri-shield-flash-line" size="28" />
          </VAvatar>
          <div>
            <div class="d-flex align-center gap-2">
              <h2 class="text-h5 font-weight-bold mb-0">
                Pusat Keamanan & Pelacakan Hacker (SIEM)
              </h2>
              <VChip color="success" size="x-small" variant="elevated" class="font-weight-bold">
                <VIcon icon="ri-broadcast-line" size="12" class="me-1 animate-pulse" />
                LIVE SHIELD ACTIVE
              </VChip>
            </div>
            <p class="text-caption text-medium-emphasis mb-0">
              Monitoring real-time log akses, deteksi serangan brute-force, pelacakan IP address, device/browser, dan firewall pemblokiran
            </p>
          </div>
        </div>

        <!-- Header Actions -->
        <div class="d-flex flex-wrap align-center gap-2">
          <!-- Refresh Button -->
          <VBtn
            variant="tonal"
            color="primary"
            prepend-icon="ri-refresh-line"
            size="small"
            class="text-none font-weight-medium"
            :loading="isLoading"
            @click="() => { fetchSummary(); fetchLogs(); }"
          >
            Segarkan
          </VBtn>

          <!-- Blacklist IP Manager -->
          <VBtn
            variant="tonal"
            color="error"
            prepend-icon="ri-forbid-2-line"
            size="small"
            class="text-none font-weight-medium"
            @click="() => { fetchBlockedIps(); isBlacklistModalVisible = true; }"
          >
            Daftar IP Diblokir ({{ summary.total_blocked_ips || 0 }})
          </VBtn>

          <!-- Clear Logs Button -->
          <VBtn
            v-if="$can('delete', 'Log Keamanan') || $can('manage', 'all')"
            variant="outlined"
            color="secondary"
            prepend-icon="ri-delete-bin-line"
            size="small"
            class="text-none"
            @click="isClearLogsDialogVisible = true"
          >
            Bersihkan Log Lama
          </VBtn>
        </div>
      </div>
    </VCard>

    <!-- Top 4 Security KPI Metrics -->
    <VRow class="mb-4 match-height">
      <!-- 1. Total Akses 24 Jam -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-primary h-100 d-flex flex-column justify-space-between" :loading="isSummaryLoading">
          <div>
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption text-primary font-weight-bold text-uppercase">Total Akses Masuk (24 Jam)</span>
              <VAvatar color="primary" variant="tonal" rounded size="40">
                <VIcon icon="ri-global-line" size="22" />
              </VAvatar>
            </div>
            <div class="text-h4 font-weight-bold text-primary mt-2">
              {{ summary.total_logs_24h.toLocaleString('id-ID') }}
            </div>
          </div>
          <div class="d-flex align-center gap-1 mt-3 text-caption text-medium-emphasis">
            <VIcon icon="ri-check-line" size="14" color="success" class="me-1" />
            <span>Permintaan HTTP terekam lengkap</span>
          </div>
        </VCard>
      </VCol>

      <!-- 2. Ancaman & Serangan Terdeteksi -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-error h-100 d-flex flex-column justify-space-between" :loading="isSummaryLoading">
          <div>
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption text-error font-weight-bold text-uppercase">Serangan / Ancaman (24 Jam)</span>
              <VAvatar color="error" variant="tonal" rounded size="40">
                <VIcon icon="ri-alarm-warning-line" size="22" />
              </VAvatar>
            </div>
            <div class="text-h4 font-weight-bold text-error mt-2">
              {{ summary.threats_detected_24h.toLocaleString('id-ID') }}
            </div>
          </div>
          <div class="d-flex align-center gap-1 mt-3 text-caption text-error font-weight-medium">
            <VIcon icon="ri-shield-cross-line" size="14" class="me-1" />
            <span>Brute-force, SQLi & akses mencurigakan</span>
          </div>
        </VCard>
      </VCol>

      <!-- 3. Alamat IP Mencurigakan -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-warning h-100 d-flex flex-column justify-space-between" :loading="isSummaryLoading">
          <div>
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption text-warning font-weight-bold text-uppercase">IP Mencurigakan Unik</span>
              <VAvatar color="warning" variant="tonal" rounded size="40">
                <VIcon icon="ri-radar-line" size="22" />
              </VAvatar>
            </div>
            <div class="text-h4 font-weight-bold text-warning mt-2">
              {{ summary.suspicious_ips_count.toLocaleString('id-ID') }}
            </div>
          </div>
          <div class="d-flex align-center gap-1 mt-3 text-caption text-medium-emphasis">
            <span>Terdeteksi oleh detektor pola ancaman</span>
          </div>
        </VCard>
      </VCol>

      <!-- 4. Total IP Diblokir (Blacklist) -->
      <VCol cols="12" sm="6" md="3">
        <VCard elevation="2" class="pa-4 border-s-lg border-secondary h-100 d-flex flex-column justify-space-between" :loading="isSummaryLoading">
          <div>
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption text-secondary font-weight-bold text-uppercase">IP Dicekal (Blacklist)</span>
              <VAvatar color="secondary" variant="tonal" rounded size="40">
                <VIcon icon="ri-forbid-2-line" size="22" />
              </VAvatar>
            </div>
            <div class="text-h4 font-weight-bold text-secondary mt-2">
              {{ summary.total_blocked_ips.toLocaleString('id-ID') }}
            </div>
          </div>
          <div class="d-flex align-center gap-1 mt-3 text-caption text-success font-weight-medium">
            <VIcon icon="ri-lock-line" size="14" class="me-1" />
            <span>Akses langsung ditolak (403 Forbidden)</span>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Top Suspicious / Attacking IPs Quick Action Bar -->
    <VCard v-if="summary?.top_attacking_ips && summary.top_attacking_ips.length > 0" elevation="2" class="mb-4 pa-4 rounded-xl border border-error bg-var-theme-surface">
      <div class="d-flex align-center justify-space-between mb-2">
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-fire-line" color="error" size="20" />
          <span class="text-subtitle-2 font-weight-bold text-error">Alamat IP Terindikasi Percobaan Serangan Paling Sering (7 Hari Terakhir):</span>
        </div>
        <span class="text-caption text-medium-emphasis">Klik tombol blokir untuk pencegahan instan</span>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <VChip
          v-for="att in summary.top_attacking_ips"
          :key="att.ip_address"
          color="error"
          variant="tonal"
          class="font-weight-medium pa-2"
        >
          <VIcon icon="ri-error-warning-line" size="14" class="me-1" />
          <strong class="me-1">{{ att.ip_address }}</strong> ({{ att.total_events }}x percobaan)
          <VBtn
            size="x-small"
            color="error"
            variant="elevated"
            class="ms-2 font-weight-bold"
            @click="openBlockDialog(att.ip_address, 'Terdeteksi percobaan serangan berulang ' + att.total_events + 'x')"
          >
            Ban IP
          </VBtn>
        </VChip>
      </div>
    </VCard>

    <!-- Main Log Table Card -->
    <VCard elevation="2" class="rounded-xl border">
      <!-- Filter Bar -->
      <VCardText class="pa-4 border-b">
        <div class="d-flex flex-wrap align-center justify-space-between gap-3">
          <div class="d-flex flex-wrap align-center gap-3 flex-grow-1">
            <!-- Search -->
            <VTextField
              v-model="search"
              placeholder="Cari IP address, nama user, URL, browser..."
              density="compact"
              variant="outlined"
              prepend-inner-icon="ri-search-line"
              style="min-width: 250px; max-width: 320px;"
              clearable
              hide-details
            />

            <!-- Risk Level Filter -->
            <VSelect
              v-model="selectedRisk"
              :items="[
                { value: 'all', title: 'Semua Tingkat Risiko' },
                { value: 'critical', title: '🔴 Kritis / Serangan' },
                { value: 'high', title: '🟠 Mencurigakan (High)' },
                { value: 'medium', title: '🟡 Peringatan (Medium)' },
                { value: 'low', title: '🟢 Aman / Normal (Low)' }
              ]"
              density="compact"
              variant="outlined"
              label="Tingkat Risiko"
              style="min-width: 210px; max-width: 260px;"
              hide-details
            />

            <!-- Event Type Filter -->
            <VSelect
              v-model="selectedEvent"
              :items="[
                { value: 'all', title: 'Semua Jenis Aktivitas' },
                { value: 'login_success', title: 'Login Berhasil' },
                { value: 'login_failed', title: 'Login Gagal' },
                { value: 'brute_force_attempt', title: 'Brute Force Attack' },
                { value: 'unauthorized_access', title: 'Akses Ditolak (401/403)' },
                { value: 'sql_injection_attempt', title: 'SQL Injection' },
                { value: 'xss_attempt', title: 'XSS Injection' },
                { value: 'blocked_ip_access', title: 'Akses IP Terblokir' },
                { value: 'data_mutation', title: 'Modifikasi Data (POST/PUT/DEL)' }
              ]"
              density="compact"
              variant="outlined"
              label="Kategori Event"
              style="min-width: 220px; max-width: 270px;"
              hide-details
            />
          </div>
        </div>
      </VCardText>

      <!-- Table Body -->
      <VTable class="text-no-wrap" hover>
        <thead>
          <tr>
            <th class="text-uppercase font-weight-bold">Waktu</th>
            <th class="text-uppercase font-weight-bold">Tingkat Risiko</th>
            <th class="text-uppercase font-weight-bold">Alamat IP & Perangkat</th>
            <th class="text-uppercase font-weight-bold">Pengguna</th>
            <th class="text-uppercase font-weight-bold">Aktivitas / Event</th>
            <th class="text-uppercase font-weight-bold">Endpoint / URL</th>
            <th class="text-uppercase font-weight-bold text-center">Status</th>
            <th class="text-uppercase font-weight-bold text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <tr v-if="isLoading">
            <td colspan="8" class="text-center pa-6">
              <VProgressCircular indeterminate color="primary" size="32" class="me-2" />
              <span>Memuat riwayat log keamanan & IP...</span>
            </td>
          </tr>

          <tr v-else-if="logs.length === 0">
            <td colspan="8" class="text-center pa-6 text-medium-emphasis">
              <VIcon icon="ri-shield-check-line" size="36" color="success" class="d-block mx-auto mb-2 opacity-50" />
              <span>Tidak ada log keamanan yang sesuai filter saat ini.</span>
            </td>
          </tr>

          <tr
            v-for="item in logs"
            :key="item.id"
            :class="{ 'bg-error-subtle': item.risk_level === 'critical', 'bg-warning-subtle': item.risk_level === 'high' }"
          >
            <!-- Waktu -->
            <td>
              <div class="font-weight-medium text-body-2">{{ formatDateTime(item.created_at) }}</div>
              <div class="text-caption text-medium-emphasis">{{ formatTimeAgo(item.created_at) }}</div>
            </td>

            <!-- Tingkat Risiko -->
            <td>
              <VChip
                size="small"
                :color="getRiskColor(item.risk_level)"
                variant="tonal"
                class="font-weight-bold"
              >
                <VIcon :icon="item.risk_level === 'critical' || item.risk_level === 'high' ? 'ri-alarm-warning-line' : 'ri-shield-check-line'" size="14" class="me-1" />
                {{ getRiskLabel(item.risk_level) }}
              </VChip>
            </td>

            <!-- IP & Device -->
            <td>
              <div class="d-flex align-center gap-1">
                <span class="font-weight-bold text-body-2 cursor-pointer text-primary" @click="copyToClipboard(item.ip_address)">
                  {{ item.ip_address }}
                </span>
                <IconBtn size="x-small" color="secondary" title="Salin IP" @click="copyToClipboard(item.ip_address)">
                  <VIcon icon="ri-file-copy-line" size="12" />
                </IconBtn>
              </div>
              <div class="text-caption text-medium-emphasis d-flex align-center gap-1">
                <VIcon :icon="getDeviceIcon(item.device_type)" size="12" />
                <span>{{ item.operating_system || 'OS Unknown' }} • {{ item.browser || 'Browser' }}</span>
              </div>
            </td>

            <!-- User -->
            <td>
              <div v-if="item.user" class="text-body-2 font-weight-medium">
                {{ item.user.name }}
                <div class="text-caption text-medium-emphasis">{{ item.user.email }}</div>
              </div>
              <div v-else class="text-caption text-medium-emphasis font-italic">
                Tamu / Tidak Terotentikasi
              </div>
            </td>

            <!-- Event -->
            <td>
              <div class="d-flex align-center gap-1">
                <VIcon :icon="getEventIcon(item.event_type)" size="16" :color="getRiskColor(item.risk_level)" />
                <span class="text-body-2 font-weight-medium text-capitalize">
                  {{ item.event_type.replace(/_/g, ' ') }}
                </span>
              </div>
              <!-- Threat tags pills -->
              <div v-if="item.threat_tags && item.threat_tags.length > 0" class="d-flex flex-wrap gap-1 mt-1">
                <VChip
                  v-for="tag in item.threat_tags"
                  :key="tag"
                  size="x-small"
                  color="error"
                  variant="elevated"
                  class="font-weight-bold"
                >
                  {{ tag }}
                </VChip>
              </div>
            </td>

            <!-- Endpoint & Method -->
            <td>
              <div class="d-flex align-center gap-1">
                <VChip
                  size="x-small"
                  :color="item.method === 'GET' ? 'info' : (item.method === 'POST' ? 'success' : (item.method === 'DELETE' ? 'error' : 'warning'))"
                  variant="elevated"
                  class="font-weight-bold"
                >
                  {{ item.method }}
                </VChip>
                <span class="text-caption text-truncate" style="max-width: 250px;" :title="item.endpoint">
                  {{ item.endpoint }}
                </span>
              </div>
            </td>

            <!-- HTTP Status Code -->
            <td class="text-center">
              <VChip
                size="small"
                :color="item.status_code >= 200 && item.status_code < 300 ? 'success' : (item.status_code === 403 || item.status_code === 401 ? 'error' : 'warning')"
                variant="tonal"
                class="font-weight-bold"
              >
                {{ item.status_code }}
              </VChip>
            </td>

            <!-- Action Buttons -->
            <td class="text-center">
              <div class="d-flex align-center justify-center gap-1">
                <!-- Detail (Eye) -->
                <IconBtn size="small" color="info" title="Lihat Detail Forensik" @click="showDetail(item)">
                  <VIcon icon="ri-eye-line" />
                </IconBtn>

                <!-- Block / Ban IP -->
                <VBtn
                  v-if="$can('block', 'Log Keamanan') || $can('manage', 'all')"
                  size="x-small"
                  color="error"
                  variant="tonal"
                  class="px-2 font-weight-bold"
                  title="Blokir IP Ini"
                  @click="openBlockDialog(item.ip_address, 'Diblokir dari log #' + item.id + ' (' + item.event_type + ')')"
                >
                  Ban IP
                </VBtn>
              </div>
            </td>
          </tr>
        </tbody>
      </VTable>

      <!-- Pagination -->
      <VCardText class="d-flex align-center justify-space-between pa-4 border-t">
        <span class="text-caption text-medium-emphasis">
          Menampilkan {{ (page - 1) * itemsPerPage + 1 }} - {{ Math.min(page * itemsPerPage, totalLogs) }} dari {{ totalLogs }} data log
        </span>
        <VPagination
          v-model="page"
          :length="Math.ceil(totalLogs / itemsPerPage) || 1"
          total-visible="5"
          density="compact"
          size="small"
        />
      </VCardText>
    </VCard>

    <!-- MODAL 1: DETAIL FORENSIK LOG AKSES -->
    <VDialog v-model="isDetailDialogVisible" max-width="650">
      <VCard class="pa-4 rounded-xl">
        <div class="d-flex align-center justify-space-between mb-3 border-b pb-3">
          <div class="d-flex align-center gap-2">
            <VAvatar :color="getRiskColor(selectedLog?.risk_level)" variant="tonal" size="36">
              <VIcon :icon="getEventIcon(selectedLog?.event_type)" size="20" />
            </VAvatar>
            <div>
              <span class="text-subtitle-1 font-weight-bold d-block">Detail Forensik Akses & Header</span>
              <span class="text-caption text-medium-emphasis">ID Log: #{{ selectedLog?.id }} • {{ formatDateTime(selectedLog?.created_at) }}</span>
            </div>
          </div>
          <VBtn icon="ri-close-line" variant="text" density="compact" @click="isDetailDialogVisible = false" />
        </div>

        <div v-if="selectedLog">
          <!-- Summary Banner -->
          <div class="d-flex align-center justify-space-between pa-3 rounded-lg bg-var-theme-background mb-3">
            <div>
              <span class="text-caption text-medium-emphasis d-block">Alamat IP Penyerang / Client</span>
              <span class="text-h6 font-weight-bold text-primary">{{ selectedLog.ip_address }}</span>
            </div>
            <VChip :color="getRiskColor(selectedLog.risk_level)" variant="elevated" class="font-weight-bold">
              {{ getRiskLabel(selectedLog.risk_level) }}
            </VChip>
          </div>

          <!-- Metadata Grid -->
          <VRow class="mb-3 text-body-2">
            <VCol cols="6" class="py-1">
              <span class="text-medium-emphasis d-block">Pengguna:</span>
              <strong>{{ selectedLog.user?.name || 'Tamu (Unauthenticated)' }}</strong>
            </VCol>
            <VCol cols="6" class="py-1">
              <span class="text-medium-emphasis d-block">Perangkat:</span>
              <strong>{{ selectedLog.device_type }} ({{ selectedLog.operating_system }})</strong>
            </VCol>
            <VCol cols="6" class="py-1">
              <span class="text-medium-emphasis d-block">Browser / Client:</span>
              <strong>{{ selectedLog.browser }}</strong>
            </VCol>
            <VCol cols="6" class="py-1">
              <span class="text-medium-emphasis d-block">Metode & Status HTTP:</span>
              <strong>{{ selectedLog.method }} (Status: {{ selectedLog.status_code }})</strong>
            </VCol>
            <VCol cols="12" class="py-1">
              <span class="text-medium-emphasis d-block">Endpoint URL Lengkap:</span>
              <code class="pa-1 rounded text-caption bg-var-theme-surface d-block text-break">{{ selectedLog.endpoint }}</code>
            </VCol>
            <VCol cols="12" class="py-1" v-if="selectedLog.user_agent">
              <span class="text-medium-emphasis d-block">Raw User-Agent String:</span>
              <code class="pa-1 rounded text-caption bg-var-theme-surface d-block text-break">{{ selectedLog.user_agent }}</code>
            </VCol>
          </VRow>

          <!-- Threat Tags -->
          <div v-if="selectedLog.threat_tags && selectedLog.threat_tags.length > 0" class="pa-3 rounded-lg border border-error mb-3 bg-error-subtle">
            <span class="text-caption font-weight-bold text-uppercase d-block mb-1 text-error">Indikator Ancaman Terdeteksi:</span>
            <div class="d-flex flex-wrap gap-1">
              <VChip v-for="tag in selectedLog.threat_tags" :key="tag" size="small" color="error" variant="elevated" class="font-weight-bold">
                {{ tag }}
              </VChip>
            </div>
          </div>

          <!-- Payload Request -->
          <div v-if="selectedLog.payload" class="pa-3 rounded-lg bg-var-theme-background mb-3">
            <span class="text-caption font-weight-bold d-block mb-1">Payload / Parameter Request (Data Sensitif Di-mask):</span>
            <pre class="text-caption pa-2 rounded bg-var-theme-surface overflow-x-auto" style="max-height: 180px;">{{ selectedLog.payload }}</pre>
          </div>

          <!-- Dialog Footer Actions -->
          <div class="d-flex justify-end gap-2 mt-4">
            <VBtn variant="outlined" color="secondary" @click="isDetailDialogVisible = false">
              Tutup
            </VBtn>
            <VBtn
              v-if="$can('block', 'Log Keamanan') || $can('manage', 'all')"
              color="error"
              prepend-icon="ri-forbid-2-line"
              @click="openBlockDialog(selectedLog.ip_address, 'Terdeteksi ancaman pada log #' + selectedLog.id)"
            >
              Blokir / Ban IP Ini
            </VBtn>
          </div>
        </div>
      </VCard>
    </VDialog>

    <!-- MODAL 2: KONFIRMASI BLOKIR / BAN IP -->
    <VDialog v-model="isBlockDialogVisible" max-width="500">
      <VCard class="pa-4 rounded-xl">
        <div class="d-flex align-center gap-2 mb-3">
          <VAvatar color="error" variant="tonal" size="36">
            <VIcon icon="ri-forbid-2-line" size="22" />
          </VAvatar>
          <span class="text-h6 font-weight-bold">Blokir / Ban Alamat IP</span>
        </div>

        <p class="text-body-2 mb-3">
          Apakah Anda yakin ingin memblokir alamat IP <strong>{{ blockForm.ip_address }}</strong>? Semua permintaan selanjutnya dari IP ini akan langsung ditolak sistem (403 Forbidden).
        </p>

        <VTextField
          v-model="blockForm.ip_address"
          label="Alamat IP yang Diblokir *"
          density="compact"
          variant="outlined"
          class="mb-3"
          readonly
        />

        <VTextarea
          v-model="blockForm.reason"
          label="Alasan Pemblokiran *"
          placeholder="Contoh: Percobaan brute-force login berulang kali..."
          density="compact"
          variant="outlined"
          rows="3"
          class="mb-4"
        />

        <div class="d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" @click="isBlockDialogVisible = false">
            Batal
          </VBtn>
          <VBtn color="error" :loading="isSubmitting" @click="handleBlockIp">
            Ya, Blokir IP Sekarang
          </VBtn>
        </div>
      </VCard>
    </VDialog>

    <!-- MODAL 3: DAFTAR IP DIBLOKIR (BLACKLIST MANAGER) -->
    <VDialog v-model="isBlacklistModalVisible" max-width="700">
      <VCard class="pa-4 rounded-xl">
        <div class="d-flex align-center justify-space-between mb-3 border-b pb-3">
          <div class="d-flex align-center gap-2">
            <VAvatar color="error" variant="tonal" size="36">
              <VIcon icon="ri-forbid-2-line" size="20" />
            </VAvatar>
            <div>
              <span class="text-subtitle-1 font-weight-bold d-block">Daftar Alamat IP yang Diblokir (Blacklist)</span>
              <span class="text-caption text-medium-emphasis">Daftar IP yang dicekal dan ditolak otomatis oleh firewall sistem</span>
            </div>
          </div>
          <VBtn icon="ri-close-line" variant="text" density="compact" @click="isBlacklistModalVisible = false" />
        </div>

        <VTable class="text-no-wrap mb-4" hover>
          <thead>
            <tr>
              <th class="text-uppercase font-weight-bold">Alamat IP</th>
              <th class="text-uppercase font-weight-bold">Alasan Blokir</th>
              <th class="text-uppercase font-weight-bold text-center">Percobaan Dicekal</th>
              <th class="text-uppercase font-weight-bold">Waktu Diblokir</th>
              <th class="text-uppercase font-weight-bold text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="blockedIps.length === 0">
              <td colspan="5" class="text-center pa-4 text-medium-emphasis">
                Belum ada alamat IP yang diblokir saat ini.
              </td>
            </tr>
            <tr v-for="b in blockedIps" :key="b.id">
              <td class="font-weight-bold text-error">{{ b.ip_address }}</td>
              <td class="text-caption" style="max-width: 200px;">{{ b.reason }}</td>
              <td class="text-center">
                <VChip size="x-small" color="error" variant="tonal" class="font-weight-bold">
                  {{ b.attempts_count }}x ditolak
                </VChip>
              </td>
              <td class="text-caption">{{ formatDateTime(b.created_at) }}</td>
              <td class="text-center">
                <VBtn
                  size="x-small"
                  color="success"
                  variant="tonal"
                  class="font-weight-bold"
                  @click="handleUnblockIp(b.ip_address)"
                >
                  Buka Blokir (Unban)
                </VBtn>
              </td>
            </tr>
          </tbody>
        </VTable>

        <div class="d-flex justify-end">
          <VBtn variant="outlined" color="secondary" @click="isBlacklistModalVisible = false">
            Tutup
          </VBtn>
        </div>
      </VCard>
    </VDialog>

    <!-- MODAL 4: KONFIRMASI BERSIHKAN LOG LAMA -->
    <VDialog v-model="isClearLogsDialogVisible" max-width="450">
      <VCard class="pa-4 rounded-xl">
        <div class="d-flex align-center gap-2 mb-3">
          <VAvatar color="warning" variant="tonal" size="36">
            <VIcon icon="ri-delete-bin-line" size="22" />
          </VAvatar>
          <span class="text-h6 font-weight-bold">Bersihkan Log Akses Lama</span>
        </div>

        <p class="text-body-2 mb-4">
          Apakah Anda yakin ingin menghapus arsip log akses keamanan yang berusia lebih dari <strong>30 hari</strong>? Tindakan ini bermanfaat untuk menjaga ukuran database tetap ringan dan cepat.
        </p>

        <div class="d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" @click="isClearLogsDialogVisible = false">
            Batal
          </VBtn>
          <VBtn color="warning" :loading="isSubmitting" @click="handleClearOldLogs">
            Ya, Bersihkan Log Lama
          </VBtn>
        </div>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
.bg-error-subtle {
  background-color: rgba(var(--v-theme-error), 0.08) !important;
}
.bg-warning-subtle {
  background-color: rgba(var(--v-theme-warning), 0.05) !important;
}
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}
</style>
