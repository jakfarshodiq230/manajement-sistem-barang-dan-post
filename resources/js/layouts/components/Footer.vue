<template>
  <div class="h-100 d-flex align-center justify-space-between text-medium-emphasis">
    <!-- 👉 Footer: left content -->
    <div class="d-flex align-center text-base">
      &copy;
      {{ new Date().getFullYear() }} - 
      By <span class="text-primary ms-1 font-weight-medium">Ms.POS</span>
    </div>
    <!-- 👉 Footer: right content -->
    <span class="d-md-flex gap-x-4 text-primary d-none">
      <RouterLink
        to="/panduan-sistem"
      >Panduan Sistem</RouterLink>
      <a
        href="#"
        target="_blank"
        rel="noopener noreferrer"
      >Bantuan</a>
    </span>
  </div>
</template>
