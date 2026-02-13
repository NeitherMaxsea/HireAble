<template>
  <div class="admin-layout">

    <!-- BODY -->
    <div class="body">

      <!-- SIDEBAR -->
      <SidebarAdmin />

      <!-- PAGE CONTENT -->
      <main class="content">
        <router-view />
      </main>

    </div>

  </div>
</template>

<script setup>
import SidebarAdmin from '@/components/sidebar-admin.vue'
</script>

<style scoped>
.admin-layout {
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.body {
  flex: 1;
  display: flex;
  min-height: 0;
}

.content {
  flex: 1;
  padding: 20px;
  background: #f8f9fa;
  min-height: 0;
  overflow-y: auto;
}
</style>
