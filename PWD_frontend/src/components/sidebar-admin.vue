<template>
  
  <aside class="sidebar">

    <!-- Brand / System -->
    <div class="brand-box">
      <img src="@/assets/whitelogo.png" class="logo" alt="System Logo" />

      <div class="brand-text">
        <h4>Admin Panel</h4>
        <small>System Management</small>
      </div>
    </div>

    <!-- Menu -->
    <nav class="menu">
      <RouterLink v-for="item in menuItems" :key="item.to" :to="item.to" class="item">
        <i :class="item.icon"></i>
        <span>{{ item.label }}</span>
      </RouterLink>
    </nav>

    <!-- Admin Info -->
    <div class="admin-box">
      <div class="admin-info">
        <strong>{{ adminName }}</strong>
        <small>Administrator</small>
      </div>
    </div>

    <!-- Logout -->
    <div class="logout" @click="logout">
      <i class="bi bi-box-arrow-right"></i>
      <span>Logout</span>
    </div>

  </aside>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { signOut } from '@/lib/session-auth'

const router = useRouter()
const adminName = ref('System Admin')
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
  width: 260px;
  height: 100%;
  min-height: 0;
  background: #ffffff;
  border-right: 1px solid #ddd;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}

/* Brand */

.brand-box {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.logo {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.brand-text h4 {
  margin: 0;
  font-size: 15px;
}

.brand-text small {
  color: gray;
}

/* Menu */

.menu {
  flex: 1;
  padding: 12px 0;
}


.item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 22px;
  color: #333;
  text-decoration: none;
  font-size: 15px;
  border-radius: 6px;
}

.item:hover {
  background: #f3f3f3;
}

.router-link-active {
  background: #e6f0ff;
  color: #0d6efd;
  font-weight: 600;
}

/* Admin Info */

.admin-box {
  padding: 15px 20px;
  border-top: 1px solid #eee;
}

.admin-info strong {
  display: block;
}

.admin-info small {
  color: gray;
}

/* Logout */

.logout {
  padding: 14px 20px;
  border-top: 1px solid #eee;
  cursor: pointer;
  display: flex;
  gap: 12px;
}

.logout:hover {
  background: #ffecec;
  color: #d93025;
}

</style>
