<template>
  <aside
    class="sidebar"
    :class="[
      { 'sidebar-dark': isDark },
      { collapsed: collapsed }
    ]"
  >
    <div class="brand">
      <img src="@/assets/whitelogo.png" class="logo" alt="HireableProximity" />
      <button class="collapse-btn" type="button" @click="toggleSidebar">
        <i class="bi bi-list"></i>
      </button>
    </div>

    <nav class="nav">
      <router-link
        to="/company-admin/dashboard"
        class="nav-link"
        :class="{ active: route.path.includes('/company-admin/dashboard') }"
        @mouseenter="tooltip = 'Dashboard'"
        @mouseleave="tooltip = ''"
      >
        <span class="nav-left">
          <i class="bi bi-speedometer2"></i>
          <span v-if="!collapsed">Dashboard</span>
        </span>
        <span v-if="collapsed && tooltip === 'Dashboard'" class="tooltip">Dashboard</span>
      </router-link>

    </nav>

    <div class="profile-wrapper" ref="profileRef">
      <div
        class="profile"
        @click.stop="toggleMenu"
        @mouseenter="tooltip = 'Profile'"
        @mouseleave="tooltip = ''"
      >
        <div class="avatar">{{ userInitial }}</div>

        <div v-show="!collapsed" class="profile-text">
          <div class="name">{{ userName }}</div>
          <div class="email">{{ userEmail }}</div>
        </div>

        <i v-if="!collapsed" class="bi bi-chevron-down"></i>
        <span v-if="collapsed && tooltip === 'Profile'" class="tooltip">Profile</span>
      </div>

      <transition name="fade">
        <div v-if="showMenu" class="profile-menu">
          <div class="profile-menu-head">
            <div class="avatar sm">{{ userInitial }}</div>
            <div class="profile-menu-text">
              <div class="name">{{ userName }}</div>
              <div class="email">{{ userEmail }}</div>
            </div>
          </div>

          <button type="button" @click.stop="toggleDarkMode">
            <i class="bi bi-moon"></i> Dark Mode
          </button>

          <button type="button" @click.stop="goDashboard">
            <i class="bi bi-building"></i> Company Dashboard
          </button>

          <button type="button" class="logout" @click.stop="logout">
            <i class="bi bi-box-arrow-right"></i> Logout
          </button>
        </div>
      </transition>
    </div>
  </aside>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { signOut } from "@/lib/session-auth"
import { doc, serverTimestamp, updateDoc } from "@/lib/laravel-data"
import { auth, db } from "@/lib/client-platform"

const route = useRoute()
const router = useRouter()

const collapsed = ref(false)
const showMenu = ref(false)
const tooltip = ref("")
const isDark = ref(localStorage.getItem("sidebarDark") === "true")
const profileRef = ref(null)

const userName = ref("Company Admin")
const userEmail = ref("No email")
const userInitial = computed(() => {
  const base = (userName.value || userEmail.value || "C").trim()
  return base ? base[0].toUpperCase() : "C"
})

onMounted(() => {
  userName.value = localStorage.getItem("userName") || "Company Admin"
  userEmail.value = localStorage.getItem("userEmail") || "No email"
  document.addEventListener("click", handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside)
})

function toggleSidebar() {
  collapsed.value = !collapsed.value
}

function toggleMenu() {
  showMenu.value = !showMenu.value
}

function toggleDarkMode() {
  isDark.value = !isDark.value
  localStorage.setItem("sidebarDark", isDark.value ? "true" : "false")
}

function handleClickOutside(event) {
  if (profileRef.value && !profileRef.value.contains(event.target)) {
    showMenu.value = false
  }
}

function goDashboard() {
  showMenu.value = false
  router.push("/company-admin/dashboard")
}

async function logout() {
  const uid = auth.currentUser?.uid
  if (uid) {
    const payload = {
      status: "inactive",
      isActive: false,
      lastLogoutAt: serverTimestamp(),
      lastSeenAt: serverTimestamp(),
      updatedAt: serverTimestamp()
    }
    try {
      await updateDoc(doc(db, "users", uid), payload)
    } catch {
      try {
        await updateDoc(doc(db, "Users", uid), payload)
      } catch {
        // continue logout flow
      }
    }
  }

  await signOut(auth)
  const darkMode = localStorage.getItem("sidebarDark")
  localStorage.clear()
  if (darkMode !== null) {
    localStorage.setItem("sidebarDark", darkMode)
  }
  router.push("/login")
}
</script>

<style scoped>
.sidebar {
  width: 270px;
  height: 100vh;
  background: #ffffff;
  padding: 22px;
  display: flex;
  flex-direction: column;
  transition: width 0.3s ease, padding 0.3s ease;
}

.sidebar * {
  transition: background-color 0.25s ease, color 0.25s ease, opacity 0.2s ease, transform 0.2s ease;
}

.sidebar-dark {
  background: #0f172a;
  color: #e5e7eb;
}

.sidebar.collapsed {
  width: 80px;
  padding: 22px 10px;
}

.brand {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 30px;
}

.logo {
  width: 80px;
}

.sidebar.collapsed .logo {
  display: none;
}

.collapse-btn {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: none;
  background: none;
  color: inherit;
  font-size: 22px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.collapse-btn:hover {
  background: rgba(0, 0, 0, 0.05);
}

.nav-link {
  position: relative;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  border-radius: 10px;
  text-decoration: none;
  color: inherit;
  margin-bottom: 6px;
}

.nav-left {
  display: flex;
  gap: 10px;
  align-items: center;
}

.active {
  background: #fef3c7;
  color: #92400e;
}

.sidebar-dark .active {
  background: #334155;
  color: #fbbf24;
}

.nav-link:hover {
  background: #f3f4f6;
}

.sidebar-dark .nav-link:hover {
  background: #1e293b;
}

.sidebar.collapsed .nav-left span {
  opacity: 0;
  transform: translateX(-10px);
  pointer-events: none;
}

.sidebar.collapsed .nav-link,
.sidebar.collapsed .profile {
  justify-content: center;
}

.tooltip {
  position: absolute;
  left: 90px;
  top: 50%;
  transform: translateY(-50%);
  background: #020617;
  color: #ffffff;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 12px;
  white-space: nowrap;
  pointer-events: none;
}

.profile-wrapper {
  margin-top: auto;
  position: relative;
}

.profile {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 6px;
  border-radius: 50px;
  cursor: pointer;
}

.profile:hover {
  background: rgba(0, 0, 0, 0.05);
}

.sidebar-dark .profile:hover {
  background: #1e293b;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #f59e0b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  flex-shrink: 0;
}

.avatar.sm {
  width: 32px;
  height: 32px;
  font-size: 12px;
}

.profile-text .name,
.profile-text .email {
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 150px;
}

.profile-menu {
  position: absolute;
  bottom: 56px;
  left: 0;
  min-width: 220px;
  background: #ffffff;
  border-radius: 10px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.sidebar-dark .profile-menu {
  background: #020617;
}

.profile-menu-head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-bottom: 1px solid #e5e7eb;
}

.sidebar-dark .profile-menu-head {
  border-bottom-color: #1f2937;
}

.profile-menu-text .name {
  font-weight: 600;
}

.profile-menu-text .email {
  font-size: 12px;
  color: #6b7280;
}

.sidebar-dark .profile-menu-text .email {
  color: #94a3b8;
}

.profile-menu button {
  width: 100%;
  border: none;
  background: none;
  color: inherit;
  text-align: left;
  cursor: pointer;
  padding: 12px;
}

.profile-menu button:hover {
  background: #f3f4f6;
}

.sidebar-dark .profile-menu button:hover {
  background: #1e293b;
}

.logout {
  color: #dc2626;
}

.fade-enter-active {
  transition: all 0.2s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
</style>


