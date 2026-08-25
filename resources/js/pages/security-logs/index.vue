<script setup>
import SecurityDashboard from '@/pages/dashboards/security.vue'

definePage({
  meta: {
    action: 'read',
    subject: 'Log Keamanan',
  },
})
</script>

<template>
  <SecurityDashboard />
</template>
