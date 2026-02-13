<template>
  <aside
    class="sidebar"
    :class="[
      { 'sidebar-dark': isDark },
      { collapsed: collapsed }
    ]"
  >

    <!-- BRAND -->
    <div class="brand">
      <img src="@/assets/whitelogo.png" class="logo" />

      <button class="collapse-btn" @click="toggleSidebar">
        <i class="bi bi-list"></i>
      </button>
    </div>

    <!-- NAV -->
    <nav class="nav">

      <!-- DASHBOARD -->
      <router-link
        to="/employer/HR/dashboard"
        class="nav-link"
        :class="{ active: route.path.includes('/dashboard') }"
        @mouseenter="tooltip='Dashboard'"
        @mouseleave="tooltip=''"
      >
        <span class="nav-left">
          <i class="bi bi-speedometer2"></i>
          <span v-if="!collapsed">Dashboard</span>
        </span>

        <span v-if="collapsed && tooltip==='Dashboard'" class="tooltip">
          Dashboard
        </span>
      </router-link>

      <!-- JOB MANAGEMENT -->
      <div
        class="nav-group"
        @mouseenter="tooltip='Job Management'"
        @mouseleave="tooltip=''"
      >

        <div
          class="nav-parent"
          :class="{ active: route.path.includes('/job-management') }"
          @click="handleJobClick"
        >
          <span class="nav-left">
            <i class="bi bi-briefcase"></i>
            <span v-if="!collapsed">Job Management</span>
          </span>

          <span v-if="!collapsed">{{ showJobs ? "▾" : "▸" }}</span>
        </div>

        <!-- TOOLTIP -->
        <span v-if="collapsed && tooltip==='Job Management'" class="tooltip">
          Job Management
        </span>

        <!-- DROPDOWN -->
        <transition name="slide">
          <div v-show="showJobs && !collapsed" class="nav-children">

            <router-link
              to="/employer/HR/job-management/job-post"
              class="nav-child"
              :class="{ active: route.path.includes('/job-post') }"
            >
              <i class="bi bi-plus-square"></i>
              Job Post
            </router-link>

            <router-link
              to="/employer/HR/job-management/job-list"
              class="nav-child"
              :class="{ active: route.path.includes('/job-list') }"
            >
              <i class="bi bi-list-task"></i>
              Job List
            </router-link>

          </div>
        </transition>

      </div>

      <!-- EMPLOYER RECORDS -->
      <router-link
        to="/employer/HR/employer-records"
        class="nav-link"
        :class="{ active: route.path.includes('/employer-records') }"
        @mouseenter="tooltip='Employer Records'"
        @mouseleave="tooltip=''"
      >
        <span class="nav-left">
          <i class="bi bi-people"></i>
          <span v-if="!collapsed">Employer Records</span>
        </span>

        <span v-if="collapsed && tooltip==='Employer Records'" class="tooltip">
          Employer Records
        </span>
      </router-link>

      <!-- ATTENDANCE MANAGEMENT -->
      <router-link
        to="/employer/HR/attendance-management"
        class="nav-link"
        :class="{ active: route.path.includes('/attendance-management') }"
        @mouseenter="tooltip='Attendance Management'"
        @mouseleave="tooltip=''"
      >
        <span class="nav-left">
          <i class="bi bi-calendar-check"></i>
          <span v-if="!collapsed">Attendance Management</span>
        </span>

        <span v-if="collapsed && tooltip==='Attendance Management'" class="tooltip">
          Attendance Management
        </span>
      </router-link>

    </nav>

    <!-- PROFILE -->
    <div class="profile-wrapper" ref="profileRef">

      <div
        class="profile"
        @click.stop="toggleMenu"
        @mouseenter="tooltip='Profile'"
        @mouseleave="tooltip=''"
      >
        <div class="avatar">{{ userInitial }}</div>

        <div v-show="!collapsed" class="profile-text">
          <div class="name">{{ userName }}</div>
          <div class="email">{{ userEmail }}</div>
        </div>

        <i v-if="!collapsed" class="bi bi-chevron-down"></i>

        <span v-if="collapsed && tooltip==='Profile'" class="tooltip">
          Profile
        </span>
      </div>

      <!-- DROPDOWN -->
      <transition name="fade">
        <div v-if="showMenu" class="profile-menu">
          <div class="profile-menu-head">
            <div class="avatar sm">{{ userInitial }}</div>
            <div class="profile-menu-text">
              <div class="name">{{ userName }}</div>
              <div class="email">{{ userEmail }}</div>
            </div>
          </div>

          <button @click.stop="toggleDarkMode">
            <i class="bi bi-moon"></i> Dark Mode
          </button>

          <button @click.stop="goProfile">
            <i class="bi bi-person"></i> Profile
          </button>

          <button>
            <i class="bi bi-gear"></i> Settings
          </button>

          <button class="logout" @click.stop="logout">
            <i class="bi bi-box-arrow-right"></i> Logout
          </button>

        </div>
      </transition>

    </div>

  </aside>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from "vue"
import { useRoute, useRouter } from "vue-router"
import { signOut } from "@/lib/session-auth"
import { doc, serverTimestamp, updateDoc } from "@/lib/laravel-data"
import { auth, db } from "@/lib/client-platform"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

const route = useRoute()
const router = useRouter()

const collapsed = ref(false)
const showJobs = ref(false)
const showMenu = ref(false)
const tooltip = ref("")

const userName = ref("Loading...")
const userEmail = ref("Loading...")
const userInitial = computed(() => {
  const base = (userName.value || userEmail.value || "U").trim()
  return base ? base[0].toUpperCase() : "U"
})

const isDark = ref(localStorage.getItem("sidebarDark") !== "false")
const profileRef = ref(null)

onMounted(() => {
  const storedName = localStorage.getItem("userName")
  const storedEmail = localStorage.getItem("userEmail")

  userName.value = storedName || "User"
  userEmail.value = storedEmail || "No email"

  document.addEventListener("click", handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside)
})

function toggleSidebar() {
  collapsed.value = !collapsed.value
}

function handleJobClick() {
  if (collapsed.value) {
    collapsed.value = false
    showJobs.value = true
  } else {
    showJobs.value = !showJobs.value
  }
}

function toggleMenu() {
  showMenu.value = !showMenu.value
}

function toggleDarkMode() {
  isDark.value = !isDark.value
  localStorage.setItem("sidebarDark", isDark.value)
}

function handleClickOutside(e) {
  if (profileRef.value && !profileRef.value.contains(e.target)) {
    showMenu.value = false
  }
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
        // continue logout even if Laravel write fails
      }
    }
  }

  try {
    await signOut(auth)
  } catch {
    // continue local logout even if auth signout request fails
  }

  const darkMode = localStorage.getItem("sidebarDark")
  localStorage.clear()
  if (darkMode !== null) {
    localStorage.setItem("sidebarDark", darkMode)
  }
  await router.replace({ path: "/login", query: { force: "1" } })
  Toastify({
    text: "Your account has been logged out.",
    gravity: "top",
    position: "right",
    duration: 3000,
    close: true,
    stopOnFocus: true,
    style: {
      background: "#0f172a"
    }
  }).showToast()
}

function goProfile() {
  showMenu.value = false
  router.push("/employer/HR/profile")
}
</script>

<style scoped>

/* SIDEBAR BASE */
.sidebar {
  width: 270px;
  height: 100vh;
  background: #ffffff;
  color: #1f2937;
  padding: 22px;
  display: flex;
  flex-direction: column;
  will-change: width, padding;
  transition:
    width 0.5s cubic-bezier(0.22, 1, 0.36, 1),
    padding 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

/* ALL CONTENT FADE + SLIDE - SYNC WITH SIDEBAR TIMING */
.sidebar * {
  transition:
    opacity 0.5s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.5s cubic-bezier(0.22, 1, 0.36, 1),
    background-color 0.5s cubic-bezier(0.22, 1, 0.36, 1),
    color 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

/* TEXT NORMAL STATE */
.sidebar .nav-left span,
.sidebar .profile-text,
.sidebar .nav-child {
  opacity: 1;
  transform: translateX(0);
}

/* COLLAPSED TEXT STATE */
.sidebar.collapsed .nav-left span,
.sidebar.collapsed .profile-text,
.sidebar.collapsed .nav-child {
  opacity: 0;
  transform: translateX(-12px);
  pointer-events: none;
}

/* ICONS STAY CENTERED */
.sidebar.collapsed .nav-left {
  justify-content: center;
  transition: justify-content 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

/* PROFILE ROW FIX */
.profile {
  height: 48px;
  transition: justify-content 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

/* DROPDOWN ANIMATION SMOOTHER */
.slide-enter-active,
.fade-enter-active {
  transition: all 0.25s ease;
}

.slide-enter-from,
.fade-enter-from {
  opacity: 0;
  transform: translateY(6px);
}

.sidebar-dark {
  background: #0f172a;
  color: #e5e7eb;
}

.sidebar.collapsed {
  width: 80px;
  padding: 22px 10px;
}

.logo {
  width: 80px;
  transition: width 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.sidebar.collapsed .logo {
  width: 42px;
}

/* BRAND */
.brand {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 30px;
}

/* BURGER */
.collapse-btn {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: none;
  border: none;
  position: relative;
  left:10px;
  font-size: 22px;
  cursor: pointer;
  color: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
}
.collapse-btn:hover {
  background: rgba(0,0,0,0.05);
}

/* NAV */
.nav-link,
.nav-parent {
  position: relative;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  border-radius: 10px;
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  margin-bottom: 4px;
  transition: justify-content 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

/* ACTIVE */
.active {
  background: #3b82f6;
  color: #ffffff;
  font-weight: 600;
}

.sidebar-dark .active {
  background: #3b82f6;
  color: #ffffff;
  font-weight: 600;
}

/* ICON + TEXT */
.nav-left {
  display: flex;
  gap: 10px;
  align-items: center;
  transition: justify-content 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

/* CHILD */
.nav-children {
  padding-left: 16px;
}

.nav-child {
  display: block;
  padding: 8px 14px;
  border-radius: 8px;
  text-decoration: none;
  color: inherit;
}

/* HOVER */
.nav-link:hover,
.nav-parent:hover,
.nav-child:hover {
  background: #f3f4f6;
}

.sidebar-dark .nav-link:hover,
.sidebar-dark .nav-parent:hover,
.sidebar-dark .nav-child:hover {
  background: #1e293b;
}

/* CENTER WHEN COLLAPSED */
.sidebar.collapsed .nav-link,
.sidebar.collapsed .nav-parent,
.sidebar.collapsed .profile {
  justify-content: center;
  transition: justify-content 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.sidebar.collapsed .nav-left {
  justify-content: center;
}

/* TOOLTIP */
.tooltip {
  position: absolute;
  left: 90px;
  top: 50%;
  transform: translateY(-50%);
  background: #020617;
  color: white;
  padding: 6px 10px;
  border-radius: 6px;
  font-size: 12px;
  white-space: nowrap;
  pointer-events: none;
}

/* PROFILE */
.profile-wrapper {
  margin-top: auto;
  position: relative;
  transition: all 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

/* PROFILE ROW */
.profile {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 6px;
  border-radius: 50px;
  cursor: pointer;
  max-width: 100%;
  overflow: hidden;
}

.profile:hover {
  background: rgba(0,0,0,0.05);
}

.sidebar-dark .profile:hover {
  background: #1e293b;
}

/* AVATAR */
.avatar {
  width: 36px;
  height: 36px;
  aspect-ratio: 1 / 1;
  border-radius: 50%;
  background: #f59e0b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  flex-shrink: 0;
}

/* TEXT WRAPPER */
.profile-text {
  transition: 
    opacity 0.25s ease,
    transform 0.25s ease,
    width 0.25s ease;
}

/* NAME + EMAIL */
.profile-text .name,
.profile-text .email {
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 150px;
}

/* DROPDOWN */
.profile-menu {
  position: absolute;
  bottom: 55px;
  left: 0;
  background: white;
  min-width: 200px;
  border-radius: 10px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.sidebar-dark .profile-menu {
  background: #020617;
}

.profile-menu-head{
  display:flex;
  align-items:center;
  gap:10px;
  padding:12px;
  border-bottom:1px solid #e5e7eb;
}

.sidebar-dark .profile-menu-head{
  border-bottom:1px solid #1f2937;
}

.avatar.sm{
  width:32px;
  height:32px;
  font-size:12px;
}

.profile-menu-text .name{
  font-weight:600;
}

.profile-menu-text .email{
  font-size:12px;
  color:#6b7280;
}

.sidebar-dark .profile-menu-text .email{
  color:#94a3b8;
}

.profile-menu button {
  width: 100%;
  padding: 12px;
  border: none;
  background: none;
  color: inherit;
  text-align: left;
  cursor: pointer;
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

/* ANIMATION */
.slide-enter-active,
.fade-enter-active {
  transition: all 0.25s ease;
}

.slide-enter-from,
.fade-enter-from {
  opacity: 0;
  transform: translateY(6px);
}


/* FIX OBLONG PROFILE */
.profile {
  height: 48px;
}

.sidebar.collapsed .profile {
  justify-content: center;
}

</style>



