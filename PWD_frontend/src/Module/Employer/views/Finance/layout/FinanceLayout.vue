<template>
  <div class="layout">
    <aside class="sidebar-area">
      <FinanceSidebar />
    </aside>

    <div class="main-content">
      <FinanceNavbar />

      <main class="page-content">
        <transition name="page-fade" mode="out-in">
          <router-view />
        </transition>
      </main>
    </div>
  </div>
</template>

<script setup>
import FinanceSidebar from "@/components/sb-employer-finance.vue"
import FinanceNavbar from "@/components/nv-employer-finance.vue"
</script>

<style scoped>
.layout {
  display: grid;
  grid-template-columns: auto 1fr;
  height: 100vh;
  overflow: hidden;
}

.sidebar-area {
  height: 100vh;
  overflow: hidden;
}

.main-content {
  min-width: 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
  background: #f5f7fb;
  overflow: hidden;
}

.page-content {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
}

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
