<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const router = useRouter()
const ability = useAbility()

const userData = useCookie('userData')

// Switch Role dialog
const isSwitchRoleVisible = ref(false)
const userAssignments = ref([])
const activeBranchId = ref(null)
const switchingRole = ref(false)

const currentBranchDisplayName = computed(() => {
  if (userData.value?.branch_name) {
    return userData.value.branch_name
  }
  const currentBId = activeBranchId.value ?? userData.value?.branch_id
  if (currentBId) {
    const found = userAssignments.value.find(a => a.branch_id == currentBId)
    if (found) return found.branch_name
    return `Cabang #${currentBId}`
  }
  return 'Semua Cabang'
})

onMounted(async () => {
  try {
    const data = await $api('/user')
    userAssignments.value = data.assignments || []
    activeBranchId.value = data.active_branch_id
    if (userData.value) {
      userData.value = {
        ...userData.value,
        role: data.role,
        branch_id: data.active_branch_id,
        branch_name: data.active_branch_name,
      }
    }
  } catch(e) {
    // silent
  }
})

const openSwitchRole = async () => {
  try {
    const data = await $api('/user')

    userAssignments.value = data.assignments || []
    activeBranchId.value = data.active_branch_id
    isSwitchRoleVisible.value = true
  } catch(e) { console.error(e) }
}

const isAssignmentActive = assignment => {
  const currentRole = userData.value?.role
  const currentBranchId = activeBranchId.value ?? userData.value?.branch_id ?? null

  const isRoleMatch = currentRole === assignment.role_name
  if (assignment.is_all) {
    return isRoleMatch && (!currentBranchId)
  }
  return isRoleMatch && (currentBranchId == assignment.branch_id)
}

const switchRole = async assignment => {
  if (switchingRole.value) return
  switchingRole.value = true
  try {
    const res = await $api('/apps/switch-role', {
      method: 'POST',
      body: { 
        role_id: assignment.role_id,
        branch_id: assignment.branch_id || null,
      },
    })

    activeBranchId.value = res.active_branch_id

    // Update userData cookie
    if (userData.value) {
      userData.value = {
        ...userData.value,
        role: res.active_role,
        branch_id: res.active_branch_id,
      }
    }

    // Update permissions
    if (res.userAbilityRules) {
      localStorage.setItem('userAbilityRules', JSON.stringify(res.userAbilityRules))
      ability.update(res.userAbilityRules)
    }

    isSwitchRoleVisible.value = false

    setTimeout(() => {
      window.location.reload()
    }, 150)
  } catch(e) {
    console.error('Failed to switch role/branch:', e)
  } finally {
    switchingRole.value = false
  }
}

const logout = async () => {
  useCookie('accessToken').value = null
  userData.value = null
  await router.push('/login')
  localStorage.removeItem('userAbilityRules')
  ability.update([])
}

const userProfileList = computed(() => [
  { type: 'divider' },
  {
    type: 'navItem',
    icon: 'ri-user-line',
    title: 'Profile',
    to: {
      name: 'apps-user-view-id',
      params: { id: userData.value?.id || 1 },
    },
  },
  { type: 'divider' },
])

const roleColors = ['primary', 'success', 'warning', 'info', 'error', 'secondary']

const getRoleColor = roleName => {
  const idx = (roleName || '').split('').reduce((acc, c) => acc + c.charCodeAt(0), 0) % roleColors.length
  
  return roleColors[idx]
}
</script>

<template>
  <VBadge
    v-if="userData"
    dot
    bordered
    location="bottom right"
    offset-x="2"
    offset-y="2"
    color="success"
    class="user-profile-badge"
  >
    <VAvatar
      class="cursor-pointer"
      size="38"
      :color="!(userData && userData.avatar) ? 'primary' : undefined"
      :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
    >
      <VImg
        v-if="userData && userData.avatar"
        :src="userData.avatar"
      />
      <VIcon
        v-else
        icon="ri-user-line"
      />

      <!-- SECTION Menu -->
      <VMenu
        activator="parent"
        width="250"
        location="bottom end"
        offset="15px"
      >
        <VList>
          <!-- User info -->
          <VListItem class="px-4">
            <div class="d-flex gap-x-2 align-center">
              <VAvatar
                :color="!(userData && userData.avatar) ? 'primary' : undefined"
                :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
              >
                <VImg
                  v-if="userData && userData.avatar"
                  :src="userData.avatar"
                />
                <VIcon
                  v-else
                  icon="ri-user-line"
                />
              </VAvatar>

              <div>
                <div class="text-body-2 font-weight-medium text-high-emphasis">
                  {{ userData.fullName || userData.username }}
                </div>
                <VChip
                  v-if="userData.role"
                  size="x-small"
                  :color="getRoleColor(Array.isArray(userData.role) ? userData.role[0] : userData.role)"
                  class="mt-1 font-weight-medium"
                >
                  <VIcon
                    start
                    size="11"
                    icon="ri-shield-user-line"
                  />
                  {{ Array.isArray(userData.role) ? userData.role[0] : userData.role }} - {{ currentBranchDisplayName }}
                </VChip>
              </div>
            </div>
          </VListItem>

          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <template
              v-for="(item, index) in userProfileList"
              :key="item?.title || index"
            >
              <VListItem
                v-if="item?.type === 'navItem'"
                :to="item.to"
                class="px-4"
              >
                <template #prepend>
                  <VIcon
                    :icon="item.icon"
                    size="22"
                  />
                </template>

                <VListItemTitle>{{ item.title }}</VListItemTitle>

                <template
                  v-if="item.chipsProps"
                  #append
                >
                  <VChip
                    v-bind="item.chipsProps"
                    variant="elevated"
                  />
                </template>
              </VListItem>

              <VDivider
                v-else
                class="my-1"
              />
            </template>

            <!-- Switch Role button -->
            <VListItem
              class="px-4"
              @click="openSwitchRole"
            >
              <template #prepend>
                <VIcon
                  icon="ri-loop-left-line"
                  size="22"
                />
              </template>
              <VListItemTitle>Ganti Peran</VListItemTitle>
            </VListItem>

            <VDivider class="my-1" />

            <VListItem class="px-4">
              <VBtn
                block
                color="error"
                size="small"
                append-icon="ri-logout-box-r-line"
                @click="logout"
              >
                Logout
              </VBtn>
            </VListItem>
          </PerfectScrollbar>
        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>
  </VBadge>

  <!-- Switch Role Dialog -->
  <VDialog
    v-model="isSwitchRoleVisible"
    max-width="420"
  >
    <VCard>
      <VCardTitle class="d-flex align-center pa-4 pb-2">
        <VIcon
          icon="ri-loop-left-line"
          class="me-2"
          color="primary"
        />
        Pilih Peran Aktif
        <VSpacer />
        <IconBtn @click="isSwitchRoleVisible = false">
          <VIcon icon="ri-close-line" />
        </IconBtn>
      </VCardTitle>
      <VDivider />
      <VCardText class="pt-4">
        <p class="text-body-2 text-medium-emphasis mb-4">
          Pilih jabatan yang ingin Anda masuki. Tampilan menu akan menyesuaikan.
        </p>

        <div
          v-if="userAssignments.length === 0"
          class="text-center text-medium-emphasis py-4"
        >
          <VIcon
            icon="ri-shield-user-line"
            size="40"
            class="mb-2"
          />
          <p>Belum ada jabatan yang ditetapkan.</p>
        </div>

        <VList
          v-else
          lines="two"
          class="rounded-xl border pa-2"
        >
          <VListItem
            v-for="(assignment, idx) in userAssignments"
            :key="idx"
            :title="assignment.is_all ? assignment.branch_name : assignment.role_name"
            :subtitle="assignment.is_all ? 'Akses & Agregasi Seluruh Cabang (' + assignment.role_name + ')' : assignment.branch_name"
            class="cursor-pointer mb-2 rounded-lg border transition-all"
            :class="isAssignmentActive(assignment) ? 'bg-primary-lighten-5 border-primary' : 'bg-surface'"
            @click="switchRole(assignment)"
          >
            <template #prepend>
              <VAvatar
                size="38"
                :color="assignment.is_all ? 'primary' : getRoleColor(assignment.role_name)"
                variant="tonal"
                rounded="lg"
                class="me-2"
              >
                <VIcon
                  :icon="assignment.is_all ? 'ri-global-line' : 'ri-store-2-line'"
                  size="22"
                />
              </VAvatar>
            </template>
            <template #append>
              <VChip
                v-if="isAssignmentActive(assignment)"
                size="small"
                color="primary"
                variant="flat"
                class="font-weight-bold"
              >
                <VIcon icon="ri-check-line" size="14" class="me-1" />
                Aktif
              </VChip>
            </template>
          </VListItem>
        </VList>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.user-profile-badge {
  &.v-badge--bordered.v-badge--dot .v-badge__badge::after {
    color: rgb(var(--v-theme-background));
  }
}
</style>
