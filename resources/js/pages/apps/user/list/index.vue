<script setup>
import AddNewUserDrawer from '@/views/apps/user/list/AddNewUserDrawer.vue'

const searchQuery = ref('')
const selectedRole = ref()
const selectedPlan = ref()
const selectedStatus = ref()

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const selectedRows = ref([])

// Assignment dialog state
const isAssignmentDialogVisible = ref(false)
const editingUser = ref(null)
const editingAssignments = ref([])

// Dynamic branches + roles
const availableBranches = ref([])
const availableRoles = ref([])

onMounted(async () => {
  try {
    const [branchData, roleData] = await Promise.all([
      $api('/apps/branches'),
      $api('/apps/roles'),
    ])

    availableBranches.value = branchData.data || branchData
    availableRoles.value = roleData.map(r => r.role)
  } catch(e) { console.error(e) }
})

const openAssignmentDialog = user => {
  editingUser.value = { ...user }
  editingAssignments.value = (user.assignments || []).map(a => ({ ...a }))
  isAssignmentDialogVisible.value = true
}

const addAssignmentRow = () => {
  editingAssignments.value.push({ branch_id: null, role_name: '' })
}

const removeAssignmentRow = index => {
  editingAssignments.value.splice(index, 1)
}

const saveAssignments = async () => {
  await $api(`/apps/users/${editingUser.value.id}`, {
    method: 'PUT',
    body: {
      fullName: editingUser.value.fullName,
      email: editingUser.value.email,
      assignments: editingAssignments.value.map(a => ({
        branch_id: a.branch_id,
        role: a.role_name,
      })),
    },
  })
  isAssignmentDialogVisible.value = false
  fetchUsers()
}

const updateOptions = options => {
  page.value = options.page
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// Headers
const headers = [
  {
    title: 'User',
    key: 'user',
  },
  {
    title: 'Email',
    key: 'email',
  },
  {
    title: 'Role',
    key: 'role',
  },
  {
    title: 'Plan',
    key: 'plan',
  },
  {
    title: 'Status',
    key: 'status',
  },
  {
    title: 'Actions',
    key: 'actions',
    sortable: false,
  },
]

const {
  data: usersData,
  execute: fetchUsers,
} = await useApi(createUrl('/apps/users', {
  query: {
    q: searchQuery,
    status: selectedStatus,
    plan: selectedPlan,
    role: selectedRole,
    itemsPerPage,
    page,
    sortBy,
    orderBy,
  },
}))

const users = computed(() => usersData.value.users)
const totalUsers = computed(() => usersData.value.totalUsers)

// 👉 search filters (roles loaded dynamically via onMounted)
const roles = computed(() => availableRoles.value.map(r => ({ title: r, value: r })))

const plans = [
  {
    title: 'Basic',
    value: 'basic',
  },
  {
    title: 'Company',
    value: 'company',
  },
  {
    title: 'Enterprise',
    value: 'enterprise',
  },
  {
    title: 'Team',
    value: 'team',
  },
]

const status = [
  {
    title: 'Pending',
    value: 'Pending',
  },
  {
    title: 'Active',
    value: 'Active',
  },
  {
    title: 'Inactive',
    value: 'Inactive',
  },
]

const roleColors = ['primary', 'success', 'warning', 'info', 'error', 'secondary']

const resolveUserRoleVariant = role => {
  // Deterministic color from role name
  const idx = (role || '').split('').reduce((acc, c) => acc + c.charCodeAt(0), 0) % roleColors.length
  
  return { color: roleColors[idx], icon: 'ri-shield-user-line' }
}

const resolveUserStatusVariant = stat => {
  const statLowerCase = stat.toLowerCase()
  if (statLowerCase === 'pending')
    return 'warning'
  if (statLowerCase === 'active')
    return 'success'
  if (statLowerCase === 'inactive')
    return 'secondary'
  
  return 'primary'
}

const isAddNewUserDrawerVisible = ref(false)

const addNewUser = async userData => {

  // userListStore.addUser(userData)
  await $api('/apps/users', {
    method: 'POST',
    body: userData,
  })

  // Refetch User
  fetchUsers()
}

const deleteUser = async id => {
  await $api(`/apps/users/${ id }`, { method: 'DELETE' })

  // Delete from selectedRows
  const index = selectedRows.value.findIndex(row => row === id)
  if (index !== -1)
    selectedRows.value.splice(index, 1)

  // Refetch User
  fetchUsers()
}

const widgetData = ref([
  {
    title: 'Session',
    value: '21,459',
    change: 29,
    desc: 'Total Users',
    icon: 'ri-group-line',
    iconColor: 'primary',
  },
  {
    title: 'Paid Users',
    value: '4,567',
    change: 18,
    desc: 'Last Week Analytics',
    icon: 'ri-user-add-line',
    iconColor: 'error',
  },
  {
    title: 'Active Users',
    value: '19,860',
    change: -14,
    desc: 'Last Week Analytics',
    icon: 'ri-user-follow-line',
    iconColor: 'success',
  },
  {
    title: 'Pending Users',
    value: '237',
    change: 42,
    desc: 'Last Week Analytics',
    icon: 'ri-user-search-line',
    iconColor: 'warning',
  },
])
</script>

<template>
  <section>
    <!-- 👉 Widgets -->
    <div class="d-flex mb-6">
      <VRow>
        <template
          v-for="(data, id) in widgetData"
          :key="id"
        >
          <VCol
            cols="12"
            md="3"
            sm="6"
          >
            <VCard>
              <VCardText>
                <div class="d-flex justify-space-between">
                  <div class="d-flex flex-column gap-y-1">
                    <span class="text-base text-high-emphasis">{{ data.title }}</span>
                    <h4 class="text-h4 d-flex align-center gap-2">
                      {{ data.value }}
                      <span
                        class="text-base font-weight-regular"
                        :class="data.change > 0 ? 'text-success' : 'text-error'"
                      >({{ prefixWithPlus(data.change) }}%)</span>
                    </h4>

                    <p class="text-sm mb-0">
                      {{ data.desc }}
                    </p>
                  </div>
                  <VAvatar
                    :color="data.iconColor"
                    variant="tonal"
                    rounded
                    size="42"
                  >
                    <VIcon
                      :icon="data.icon"
                      size="26"
                    />
                  </VAvatar>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </template>
      </VRow>
    </div>

    <VCard class="mb-6">
      <VCardItem class="pb-4">
        <VCardTitle>Filters</VCardTitle>
      </VCardItem>
      <VCardText>
        <VRow>
          <!-- 👉 Select Role -->
          <VCol
            cols="12"
            sm="4"
          >
            <VSelect
              v-model="selectedRole"
              label="Select Role"
              placeholder="Select Role"
              :items="roles"
              clearable
              clear-icon="ri-close-line"
            />
          </VCol>
          <!-- 👉 Select Plan -->
          <VCol
            cols="12"
            sm="4"
          >
            <VSelect
              v-model="selectedPlan"
              label="Select Plan"
              placeholder="Select Plan"
              :items="plans"
              clearable
              clear-icon="ri-close-line"
            />
          </VCol>
          <!-- 👉 Select Status -->
          <VCol
            cols="12"
            sm="4"
          >
            <VSelect
              v-model="selectedStatus"
              label="Select Status"
              placeholder="Select Status"
              :items="status"
              clearable
              clear-icon="ri-close-line"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VCardText class="d-flex flex-wrap gap-4 align-center">
        <!-- 👉 Export button -->
        <VBtn
          variant="outlined"
          color="secondary"
          prepend-icon="ri-upload-2-line"
        >
          Export
        </VBtn>
        <VSpacer />
        <div class="d-flex align-center gap-4 flex-wrap">
          <!-- 👉 Search  -->
          <div class="app-user-search-filter">
            <VTextField
              v-model="searchQuery"
              placeholder="Search User"
              density="compact"
            />
          </div>
          <!-- 👉 Add user button -->
          <VBtn @click="isAddNewUserDrawerVisible = true">
            Add New User
          </VBtn>
        </div>
      </VCardText>

      <!-- SECTION datatable -->
      <VDataTableServer
        v-model:model-value="selectedRows"
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :items="users"
        item-value="id"
        :items-length="totalUsers"
        :headers="headers"
        show-select
        class="text-no-wrap rounded-0"
        @update:options="updateOptions"
      >
        <!-- User -->
        <template #item.user="{ item }">
          <div class="d-flex align-center">
            <VAvatar
              size="34"
              :variant="!item.avatar ? 'tonal' : undefined"
              :color="!item.avatar ? resolveUserRoleVariant(Array.isArray(item.role) ? item.role[0] : item.role).color : undefined"
              class="me-3"
            >
              <VImg
                v-if="item.avatar"
                :src="item.avatar"
              />
              <span v-else>{{ avatarText(item.fullName) }}</span>
            </VAvatar>

            <div class="d-flex flex-column">
              <RouterLink
                :to="{ name: 'apps-user-view-id', params: { id: item.id } }"
                class="text-link text-base font-weight-medium"
              >
                {{ item.fullName }}
              </RouterLink>

              <span class="text-sm text-medium-emphasis">@{{ item.username }}</span>
            </div>
          </div>
        </template>
        <!-- Role -->
        <template #item.role="{ item }">
          <div class="d-flex flex-wrap gap-1">
            <template v-if="item.role && item.role.length > 0">
              <VTooltip
                v-for="(roleName, idx) in item.role"
                :key="idx"
                :text="item.assignments?.find(a => a.role_name === roleName)?.branch_name || ''"
              >
                <template #activator="{ props: tooltipProps }">
                  <VChip
                    v-bind="tooltipProps"
                    size="x-small"
                    :color="resolveUserRoleVariant(roleName).color"
                    class="text-capitalize"
                  >
                    <VIcon
                      start
                      size="12"
                      :icon="resolveUserRoleVariant(roleName).icon"
                    />
                    {{ roleName }}
                  </VChip>
                </template>
              </VTooltip>
            </template>
            <VChip
              v-else
              size="x-small"
              color="secondary"
            >
              Unassigned
            </VChip>
          </div>
        </template>
        <!-- Plan -->
        <template #item.plan="{ item }">
          <span class="text-capitalize text-high-emphasis">{{ item.currentPlan }}</span>
        </template>
        <!-- Status -->
        <template #item.status="{ item }">
          <VChip
            :color="resolveUserStatusVariant(item.status)"
            size="small"
            class="text-capitalize"
          >
            {{ item.status }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <IconBtn
            size="small"
            @click="deleteUser(item.id)"
          >
            <VIcon icon="ri-delete-bin-7-line" />
          </IconBtn>

          <IconBtn
            size="small"
            :to="{ name: 'apps-user-view-id', params: { id: item.id } }"
          >
            <VIcon icon="ri-eye-line" />
          </IconBtn>

          <IconBtn
            size="small"
            color="medium-emphasis"
          >
            <VIcon icon="ri-more-2-line" />

            <VMenu activator="parent">
              <VList>
                <VListItem link>
                  <template #prepend>
                    <VIcon icon="ri-download-line" />
                  </template>
                  <VListItemTitle>Download</VListItemTitle>
                </VListItem>
                <VListItem
                  link
                  @click="openAssignmentDialog(item)"
                >
                  <template #prepend>
                    <VIcon icon="ri-shield-user-line" />
                  </template>
                  <VListItemTitle>Kelola Jabatan</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </IconBtn>
        </template>

        <!-- Pagination -->
        <template #bottom>
          <VDivider />

          <div class="d-flex justify-end flex-wrap gap-x-6 px-2 py-1">
            <div class="d-flex align-center gap-x-2 text-medium-emphasis text-base">
              Rows Per Page:
              <VSelect
                v-model="itemsPerPage"
                class="per-page-select"
                variant="plain"
                :items="[10, 20, 25, 50, 100]"
              />
            </div>

            <p class="d-flex align-center text-base text-high-emphasis me-2 mb-0">
              {{ paginationMeta({ page, itemsPerPage }, totalUsers) }}
            </p>

            <div class="d-flex gap-x-2 align-center me-2">
              <VBtn
                class="flip-in-rtl"
                icon="ri-arrow-left-s-line"
                variant="text"
                density="comfortable"
                color="high-emphasis"
                :disabled="page <= 1"
                @click="page <= 1 ? page = 1 : page--"
              />

              <VBtn
                class="flip-in-rtl"
                icon="ri-arrow-right-s-line"
                density="comfortable"
                variant="text"
                color="high-emphasis"
                :disabled="page >= Math.ceil(totalUsers / itemsPerPage)"
                @click="page >= Math.ceil(totalUsers / itemsPerPage) ? page = Math.ceil(totalUsers / itemsPerPage) : page++ "
              />
            </div>
          </div>
        </template>
      </VDataTableServer>
      <!-- SECTION -->
    </VCard>
    <!-- 👉 Add New User -->
    <AddNewUserDrawer
      v-model:is-drawer-open="isAddNewUserDrawerVisible"
      @user-data="addNewUser"
    />

    <!-- 👉 Assignment Dialog -->
    <VDialog
      v-model="isAssignmentDialogVisible"
      max-width="600"
    >
      <VCard v-if="editingUser">
        <VCardTitle class="d-flex align-center pa-4 pb-2">
          <VIcon
            icon="ri-shield-user-line"
            class="me-2"
            color="primary"
          />
          Kelola Jabatan: <strong class="ms-1">{{ editingUser.fullName }}</strong>
          <VSpacer />
          <IconBtn @click="isAssignmentDialogVisible = false">
            <VIcon icon="ri-close-line" />
          </IconBtn>
        </VCardTitle>
        <VDivider />
        <VCardText class="pt-4">
          <p class="text-body-2 text-medium-emphasis mb-4">
            Setiap baris = satu penugasan (Cabang + Jabatan). Tambah baris untuk penugasan di banyak cabang.
          </p>

          <div
            v-for="(assignment, idx) in editingAssignments"
            :key="idx"
            class="d-flex gap-3 mb-3 align-center"
          >
            <!-- Branch -->
            <VSelect
              v-model="assignment.branch_id"
              :items="availableBranches"
              item-title="name"
              item-value="id"
              label="Cabang / Toko"
              density="compact"
              hide-details
              style="min-width: 180px"
            />
            <!-- Role -->
            <VSelect
              v-model="assignment.role_name"
              :items="availableRoles"
              label="Jabatan"
              density="compact"
              hide-details
              style="min-width: 160px"
            />
            <!-- Delete row -->
            <IconBtn
              color="error"
              @click="removeAssignmentRow(idx)"
            >
              <VIcon
                icon="ri-delete-bin-7-line"
                size="18"
              />
            </IconBtn>
          </div>

          <VBtn
            variant="tonal"
            color="primary"
            size="small"
            prepend-icon="ri-add-line"
            class="mt-2"
            @click="addAssignmentRow"
          >
            Tambah Penugasan
          </VBtn>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn
            variant="outlined"
            color="secondary"
            @click="isAssignmentDialogVisible = false"
          >
            Batal
          </VBtn>
          <VBtn
            color="primary"
            @click="saveAssignments"
          >
            Simpan
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss" scoped>
.app-user-search-filter {
  inline-size: 15.625rem;
}
</style>
