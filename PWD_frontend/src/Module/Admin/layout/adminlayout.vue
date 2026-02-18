<template>
  <div class="admin-layout">
    <div class="body">
      <SidebarAdmin />
      <section class="main-shell">
        <header class="topbar">
          <div class="title-wrap">
            <h1>Admin Console</h1>
            <p>System control center</p>
          </div>
          <div class="meta-wrap">
            <span class="date-chip">{{ currentDateLabel }}</span>
            <div class="admin-chip">
              <i class="bi bi-person-circle"></i>
              <span>{{ adminName }}</span>
            </div>
          </div>
        </header>
        <main class="content">
          <router-view />
        </main>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue"
import SidebarAdmin from '@/components/sidebar-admin.vue'

const adminName = computed(() => {
  return (
    String(localStorage.getItem("userName") || "").trim() ||
    String(localStorage.getItem("userEmail") || "").trim() ||
    "System Admin"
  )
})

const currentDateLabel = computed(() => {
  return new Date().toLocaleDateString("en-US", {
    weekday: "short",
    month: "short",
    day: "2-digit",
    year: "numeric",
  })
})
</script>

<style scoped>
.admin-layout {
  height: 100vh;
  display: flex;
  flex-direction: column;
  --admin-primary: #0d9488;
  --admin-primary-strong: #0f766e;
  --admin-surface: #f5fbfa;
  --admin-line: #cce5e2;
}

.body {
  flex: 1;
  display: flex;
  min-height: 0;
}

.main-shell {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, #eefaf8 0%, #f7fbfb 56%, #f9fcfc 100%);
}

.topbar {
  min-height: 76px;
  border-bottom: 1px solid var(--admin-line);
  background: linear-gradient(120deg, rgba(13, 148, 136, 0.12) 0%, rgba(13, 148, 136, 0.04) 45%, rgba(255, 255, 255, 0.75) 100%);
  padding: 14px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.title-wrap h1 {
  margin: 0;
  font-size: 20px;
  color: #134e4a;
}

.title-wrap p {
  margin: 4px 0 0;
  color: #0f766e;
  font-size: 13px;
}

.meta-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}

.date-chip {
  border: 1px solid rgba(15, 118, 110, 0.25);
  background: rgba(255, 255, 255, 0.85);
  color: #115e59;
  border-radius: 999px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.admin-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border-radius: 999px;
  background: var(--admin-primary);
  color: #ffffff;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.content {
  flex: 1;
  padding: 20px;
  background: transparent;
  min-height: 0;
  overflow-y: auto;
}

@media (max-width: 860px) {
  .topbar {
    align-items: flex-start;
    flex-direction: column;
  }

  .meta-wrap {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
