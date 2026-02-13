<template>
  <div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar-area">
      <Sidebar />
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content">

      <!-- TOP NAVBAR -->
      <Navbar />

      <!-- PAGE CONTENT -->
      <main class="page-content">
        <transition name="page-fade" mode="out-in">
          <router-view />
        </transition>
      </main>

    </div>

  </div>
</template>

<script setup>
import Sidebar from "@/components/sb-employer.vue"
import Navbar from "@/components/nv-employer.vue"
</script>

<style scoped>
/* ROOT */
.layout {
  display: grid;
  grid-template-columns: auto 1fr;
  height: 100vh;
}



/* MAIN CONTENT */
.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  background: #f5f7fb;
}

/* PAGE CONTENT */
.page-content {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
}

/* FADE */
.page-fade-enter-active {
  transition: opacity 0.25s ease;
}
.page-fade-leave-active {
  transition: opacity 0.2s ease;
}
.page-fade-enter-from,
.page-fade-leave-to {
  opacity: 0;
}

@media (max-width: 1024px) {
  .layout {
    grid-template-columns: 1fr;
  }
}



</style>
