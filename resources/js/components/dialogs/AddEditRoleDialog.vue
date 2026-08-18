<script setup>
import { VForm } from 'vuetify/components/VForm'

import { useSnackbarStore } from '@/stores/snackbar'

const props = defineProps({
  rolePermissions: {
    type: Object,
    required: false,
    default: () => ({
      name: '',
      permissions: [],
    }),
  },
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'update:rolePermissions',
])

const snackbar = useSnackbarStore()


// 👉 Permission List
const permissions = ref([])

const availableActions = ['Read', 'Write', 'Create', 'Delete', 'Validate', 'Approve', 'Export', 'Import', 'PIN']

const fetchModules = async () => {
  try {
    const data = await $api('/apps/modules?all=true')

    const dData = data.data || data;
    permissions.value = dData.map(module => {
      const permObj = { name: module.name }

      availableActions.forEach(action => {
        permObj[action.toLowerCase()] = false
      })
      
      return permObj
    })
    applyRolePermissions()
  } catch (error) {
    console.error(error)
  }
}

onMounted(() => {
  fetchModules()
})

const isSelectAll = ref(false)
const role = ref('')
const refPermissionForm = ref()

const checkedCount = computed(() => {
  let counter = 0
  permissions.value.forEach(permission => {
    Object.entries(permission).forEach(([key, value]) => {
      if (key !== 'name' && value)
        counter++
    })
  })
  
  return counter
})

const isIndeterminate = computed(() => checkedCount.value > 0 && checkedCount.value < permissions.value.length * 4)

// select all
watch(isSelectAll, val => {
  permissions.value = permissions.value.map(permission => {
    const updated = { ...permission }

    availableActions.forEach(action => {
      updated[action.toLowerCase()] = val
    })
    
    return updated
  })
})

// if Indeterminate is false, then set isSelectAll to false
watch(isIndeterminate, () => {
  if (!isIndeterminate.value)
    isSelectAll.value = false
})

// if all permissions are checked, then set isSelectAll to true
watch(permissions, () => {
  if (permissions.value.length && checkedCount.value === permissions.value.length * 6)
    isSelectAll.value = true
}, { deep: true })

const applyRolePermissions = () => {
  if (props.rolePermissions && props.rolePermissions.permissions?.length) {
    role.value = props.rolePermissions.name
    
    // API might return array of strings or objects. Our backend returns string names.
    const rolePerms = props.rolePermissions.permissions.map(p => p.name || p)
    
    permissions.value.forEach(permission => {
      availableActions.forEach(action => {
        permission[action.toLowerCase()] = rolePerms.includes(`${permission.name} ${action}`)
      })
    })
  } else {
    role.value = ''
    permissions.value.forEach(permission => {
      availableActions.forEach(action => {
        permission[action.toLowerCase()] = false
      })
    })
    isSelectAll.value = false
  }
}

// if rolePermissions changes, apply them
watch(() => props.rolePermissions, () => {
  applyRolePermissions()
}, { deep: true })

const onSubmit = async () => {
  const rolePermissions = {
    name: role.value,
    permissions: permissions.value.flatMap(p => {
      const perms = []

      availableActions.forEach(action => {
        if (p[action.toLowerCase()]) perms.push(`${p.name} ${action}`)
      })
      
      return perms
    }),
  }

  try {
    if (props.rolePermissions?.id) {
      await $api(`/apps/roles/${props.rolePermissions.id}`, { method: 'PUT', body: rolePermissions })
    } else {
      await $api('/apps/roles', { method: 'POST', body: rolePermissions })
    }
    emit('update:rolePermissions', rolePermissions)
    emit('update:isDialogVisible', false)
    isSelectAll.value = false
    refPermissionForm.value?.reset()

    // Reload page to refresh data for now
    window.location.reload()
  } catch(e) { 
    console.error(e)
    if (e.response && e.response._data && e.response._data.errors) {
      const errorMsgs = Object.values(e.response._data.errors).flat().join('\n')

      snackbar.show(errorMsgs, 'error')
    } else {
      snackbar.show(e.message || 'Terjadi kesalahan', 'error')
    }
  }
}

const onReset = () => {
  emit('update:isDialogVisible', false)
  isSelectAll.value = false
  refPermissionForm.value?.reset()
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 900"
    :model-value="props.isDialogVisible"
    @update:model-value="onReset"
  >
    <VCard class="pa-sm-11 pa-3">
      <!-- 👉 dialog close btn -->
      <DialogCloseBtn
        variant="text"
        size="default"
        @click="onReset"
      />

      <VCardText>
        <!-- 👉 Title -->
        <div class="text-center mb-10">
          <h4 class="text-h4 mb-2">
            {{ props.rolePermissions.name ? 'Edit' : 'Add' }} Role
          </h4>

          <p class="text-body-1">
            {{ props.rolePermissions.name ? 'Edit' : 'Add' }} Role
          </p>
        </div>

        <!-- 👉 Form -->
        <VForm ref="refPermissionForm">
          <!-- 👉 Role name -->
          <VTextField
            v-model="role"
            label="Role Name"
            placeholder="Enter Role Name"
          />

          <h5 class="text-h5 my-6">
            Role Permissions
          </h5>

          <!-- 👉 Role Permissions -->

          <VTable class="permission-table text-no-wrap">
            <!-- 👉 Admin  -->
            <tr>
              <td class="text-h6">
                Administrator Access
              </td>
              <td :colspan="availableActions.length">
                <div class="d-flex justify-end">
                  <VCheckbox
                    v-model="isSelectAll"
                    v-model:indeterminate="isIndeterminate"
                    label="Select All"
                  />
                </div>
              </td>
            </tr>

            <!-- 👉 Other permission loop -->
            <template
              v-for="permission in permissions"
              :key="permission.name"
            >
              <tr>
                <td class="text-h6">
                  {{ permission.name }}
                </td>
                <td
                  v-for="action in availableActions"
                  :key="action"
                  style="inline-size: 5.75rem;"
                >
                  <div class="d-flex justify-end">
                    <VCheckbox
                      v-model="permission[action.toLowerCase()]"
                      :label="action"
                    />
                  </div>
                </td>
              </tr>
            </template>
          </VTable>

          <!-- 👉 Actions button -->
          <div class="d-flex align-center justify-center gap-3 mt-6">
            <VBtn @click="onSubmit">
              Submit
            </VBtn>

            <VBtn
              color="secondary"
              variant="outlined"
              @click="onReset"
            >
              Cancel
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.permission-table {
  td {
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding-block: 0.625rem;

    .v-checkbox {
      min-inline-size: 4.75rem;
    }

    &:not(:first-child) {
      padding-inline: 0.75rem;
    }

    .v-label {
      white-space: nowrap;
    }
  }
}
</style>
