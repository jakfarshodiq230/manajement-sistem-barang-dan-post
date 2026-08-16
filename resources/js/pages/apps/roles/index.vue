<script setup>
import RoleCards from '@/views/apps/roles/RoleCards.vue'
import UserList from '@/views/apps/roles/UserList.vue'
</script>

<template>
  <Suspense>
    <VRow>
      <VCol cols="12">
        <h4 class="text-h4 mb-1">
          Roles List
        </h4>
        <p class="text-body-1 mb-0">
          A role provided access to predefined menus and features so that depending on assigned role an administrator can have access to what he need
        </p>
      </VCol>

      <!-- 👉 Roles Cards -->
      <VCol cols="12">
        <RoleCards />
      </VCol>

      <VCol cols="12">
        <h4 class="text-h4 mt-6 mb-1">
          Total users with their roles
        </h4>
        <p class="text-body-1 mb-6">
          Find all of your company's administrator accounts and their associate roles.
        </p>

        <!-- 👉 User List  -->
        <UserList />
      </VCol>
    </VRow>
    <template #fallback>
      <div>Loading roles...</div>
    </template>
  </Suspense>
</template>

<route lang="yaml">
meta:
  action: read
  subject: Roles
</route>
