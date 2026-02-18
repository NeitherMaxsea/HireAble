<template>
  <aside class="sidebar">
    <div class="brand-box">
      <img src="@/assets/whitelogo.png" class="logo" alt="System Logo" />
      <div class="brand-text">
        <h4>Admin Panel</h4>
        <small>System center</small>
      </div>
    </div>

    <nav class="menu">
      <RouterLink v-for="item in menuItems" :key="item.to" :to="item.to" class="item">
        <i :class="item.icon"></i>
        <span>{{ item.label }}</span>
      </RouterLink>
    </nav>

    <div class="admin-box">
      <div class="admin-info">
        <strong>{{ adminName }}</strong>
        <small>Administrator</small>
      </div>
    </div>

    <div class="logout" @click="logout">
      <i class="bi bi-box-arrow-right"></i>
      <span>Logout</span>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { signOut } from '@/lib/session-auth'

const router = useRouter()
const adminName = computed(() => {
  return (
    String(localStorage.getItem("userName") || "").trim() ||
    String(localStorage.getItem("userEmail") || "").trim() ||
    "System Admin"
  )
})
const currentRole = computed(() =>
  String(localStorage.getItem("userRole") || "").trim().toLowerCase().replace(/-/g, "_")
)
const isCompanyAdmin = computed(() => currentRole.value === "company_admin")
const basePath = computed(() => (isCompanyAdmin.value ? "/company-admin" : "/admin"))
const menuItems = computed(() => {
  const items = [
    { to: `${basePath.value}/dashboard`, icon: "bi bi-grid", label: "Dashboard" },
    {
      to: `${basePath.value}/${isCompanyAdmin.value ? "add-employee" : "users"}`,
      icon: "bi bi-people",
      label: isCompanyAdmin.value ? "Add Employee" : "Users",
    },
  ]

  if (!isCompanyAdmin.value) {
    items.push({
      to: `${basePath.value}/company-management`,
      icon: "bi bi-building",
      label: "Company Management",
    })
  }

  items.push({ to: `${basePath.value}/logs`, icon: "bi bi-journal-text", label: "Logs" })
  return items
})

async function logout() {
  await signOut()
  router.push('/login')
}
</script>

<style scoped>
.sidebar {
  --admin-primary: #0d9488;
  --admin-primary-strong: #0f766e;
  --admin-bg: #f4fbfa;
  --admin-border: #cde8e5;
  --admin-text: #123937;
  width: 270px;
  flex-shrink: 0;
  height: 100%;
  min-height: 0;
  background: linear-gradient(170deg, #ecfaf8 0%, #f6fcfc 45%, #ffffff 100%);
  border-right: 1px solid var(--admin-border);
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.8);
}

.brand-box {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 18px 16px;
  border-bottom: 1px solid var(--admin-border);
}

.logo {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  object-fit: cover;
  border: 1px solid var(--admin-border);
}

.brand-text h4 {
  margin: 0;
  font-size: 15px;
  color: #0f3b39;
}

.brand-text small {
  color: #0f766e;
}

.menu {
  flex: 1;
  padding: 10px;
}

.item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 11px 12px;
  color: var(--admin-text);
  text-decoration: none;
  font-size: 14px;
  border-radius: 10px;
  border: 1px solid transparent;
  margin-bottom: 6px;
  transition: all 0.2s ease;
}

.item:hover {
  background: rgba(13, 148, 136, 0.09);
  border-color: rgba(13, 148, 136, 0.18);
}

.router-link-active {
  background: rgba(13, 148, 136, 0.14);
  border-color: rgba(13, 148, 136, 0.24);
  color: var(--admin-primary-strong);
  font-weight: 600;
}

.admin-box {
  padding: 14px 16px;
  border-top: 1px solid var(--admin-border);
}

.admin-info strong {
  display: block;
  color: #0f3b39;
  font-size: 13px;
  word-break: break-word;
}

.admin-info small {
  color: #11706a;
  font-size: 12px;
}

.logout {
  padding: 13px 16px;
  border-top: 1px solid var(--admin-border);
  cursor: pointer;
  display: flex;
  gap: 10px;
  color: #7f1d1d;
  font-weight: 600;
  transition: all 0.2s ease;
}

.logout:hover {
  background: #fee2e2;
  color: #b91c1c;
}
</style>
