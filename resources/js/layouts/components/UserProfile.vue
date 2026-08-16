<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const router = useRouter()
const ability = useAbility()

const userData = useCookie('userData')

// Switch Role dialog
const isSwitchRoleVisible = ref(false)
const userAssignments = ref([])
const switchingRole = ref(false)

const openSwitchRole = async () => {
  try {
    const data = await $api('/user')

    userAssignments.value = data.assignments || []
    isSwitchRoleVisible.value = true
  } catch(e) { console.error(e) }
}

const switchRole = async assignment => {
  switchingRole.value = true
  try {
    const res = await $api('/apps/switch-role', {
      method: 'POST',
      body: { role_id: assignment.role_id },
    })


    // Update userData cookie
    if (userData.value) {
      userData.value = {
        ...userData.value,
        role: res.active_role,
      }
    }

    // Update permissions
    if (res.userAbilityRules) {
      localStorage.setItem('userAbilityRules', JSON.stringify(res.userAbilityRules))
      ability.update(res.userAbilityRules)
    }

    isSwitchRoleVisible.value = false

    // Reload to refresh sidebar/permissions
    window.location.reload()
  } catch(e) { console.error(e) }
  finally { switchingRole.value = false }
}

const logout = async () => {
  useCookie('accessToken').value = null
  userData.value = null
  await router.push('/login')
  localStorage.removeItem('userAbilityRules')
  ability.update([])
}

const userProfileList = [
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
  {
    type: 'navItem',
    icon: 'ri-settings-4-line',
    title: 'Settings',
    to: {
      name: 'pages-account-settings-tab',
      params: { tab: 'account' },
    },
  },
  { type: 'divider' },
]

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
                  class="mt-1"
                >
                  <VIcon
                    start
                    size="10"
                    icon="ri-shield-user-line"
                  />
                  {{ Array.isArray(userData.role) ? userData.role[0] : userData.role }}
                </VChip>
              </div>
            </div>
          </VListItem>

          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <template
              v-for="item in userProfileList"
              :key="item.title"
            >
              <VListItem
                v-if="item.type === 'navItem'"
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
          class="rounded border"
        >
          <VListItem
            v-for="(assignment, idx) in userAssignments"
            :key="idx"
            :title="assignment.role_name"
            :subtitle="assignment.branch_name"
            class="cursor-pointer"
            :class="{ 'bg-primary-subtle': userData?.role === assignment.role_name }"
            @click="switchRole(assignment)"
          >
            <template #prepend>
              <VAvatar
                size="36"
                :color="getRoleColor(assignment.role_name)"
                variant="tonal"
                class="me-1"
              >
                <VIcon
                  icon="ri-shield-user-line"
                  size="20"
                />
              </VAvatar>
            </template>
            <template #append>
              <VIcon
                v-if="userData?.role === assignment.role_name"
                icon="ri-check-line"
                color="primary"
              />
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
