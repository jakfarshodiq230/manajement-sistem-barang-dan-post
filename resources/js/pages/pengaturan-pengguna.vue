<script setup>
import { useRouter } from 'vue-router'
import { onMounted } from 'vue'

definePage({
  meta: {
    public: true,
  },
})

const router = useRouter()

onMounted(() => {
  router.replace('/apps/pengaturan-pengguna')
})
</script>

<template>
  <div class="d-flex justify-center align-center py-16">
    <VProgressCircular indeterminate color="primary" size="48" />
  </div>
</template>
