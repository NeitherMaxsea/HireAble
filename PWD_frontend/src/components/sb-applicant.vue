<template>
  <aside class="sidebar" :class="{ collapsed: isCollapsed, 'mobile-open': mobileOpen }">

    <!-- LOGO -->
    <div class="brand">
      <img
        v-if="!isCollapsed"
        src="@/assets/titlelogo.png"
        alt="PWD Portal"
      />
      <img
        v-else
        src="@/assets/whitelogo.png"
        class="mini-logo"

      style="width: 40px;"
        alt="PWD Portal"
      />
    </div>

    <!-- MENU -->
    <nav class="menu">

      <router-link to="/applicant/job_list" class="menu-item" @click="closeMobile">
        <i class="bi bi-briefcase icon"></i>
        <span v-if="!isCollapsed">Job Listings</span>
      </router-link>

      <router-link to="/applicant/applications" class="menu-item" @click="closeMobile">
        <i class="bi bi-file-earmark-text icon"></i>
        <span v-if="!isCollapsed">My Applications</span>
      </router-link>

      <router-link to="/applicant/interviews" class="menu-item" @click="closeMobile">
        <i class="bi bi-calendar-check icon"></i>
        <span v-if="!isCollapsed">Interviews</span>
      </router-link>

      <router-link to="/applicant/training-progress" class="menu-item" @click="closeMobile">
        <i class="bi bi-graph-up-arrow icon"></i>
        <span v-if="!isCollapsed">Training Progress</span>
      </router-link>

      <router-link to="/applicant/notifications" class="menu-item" @click="closeMobile">
        <i class="bi bi-bell icon"></i>
        <span v-if="!isCollapsed">Notifications</span>
      </router-link>

      <router-link to="/applicant/profile" class="menu-item" @click="closeMobile">
        <i class="bi bi-person icon"></i>
        <span v-if="!isCollapsed">Profile</span>
      </router-link>

      <router-link to="/applicant/accessibility" class="menu-item" @click="closeMobile">
        <i class="bi bi-universal-access icon"></i>
        <span v-if="!isCollapsed">Accessibility</span>
      </router-link>

    </nav>

    <!-- FOOTER -->
    <div class="footer">

      <button class="collapse" @click="toggleSidebar">
        <i
          class="bi"
          :class="isCollapsed ? 'bi-layout-sidebar' : 'bi-layout-sidebar-inset'"
        ></i>
        <span v-if="!isCollapsed">
          {{ isCollapsed ? "Expand sidebar" : "Collapse sidebar" }}
        </span>
      </button>

      <button class="logout" @click="logout">
        <i class="bi bi-box-arrow-right icon"></i>
        <span v-if="!isCollapsed">Logout</span>
      </button>

    </div>

  </aside>
</template>

<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"
import { signOut } from "@/lib/session-auth"
import { doc, serverTimestamp, updateDoc } from "@/lib/laravel-data"
import { auth, db } from "@/lib/client-platform"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

defineProps({
  mobileOpen: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(["close-mobile"])
const router = useRouter()
const isCollapsed = ref(false)

const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value
}

const closeMobile = () => {
  emit("close-mobile")
}

const logout = async () => {
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

  localStorage.clear()
  emit("close-mobile")
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
</script>

<style scoped>
/* SIDEBAR BASE */
.sidebar {
  width: 260px;
  height: 100vh;
  position: sticky;
  top: 0;
  align-self: flex-start;
  flex-shrink: 0;
  overflow: hidden;
  background: #5f6f73;
  color: #f9fafb;
  display: flex;
  flex-direction: column;
  padding: 20px 14px;
  transition: width 0.25s ease;
}

/* COLLAPSED */
.sidebar.collapsed {
  width: 80px;
}

/* BRAND */
.brand {
  display: flex;
  justify-content: center;
  margin-bottom: 28px;
}

.brand img {
  width: 202px;
}

.mini-logo {
  width: 0px;
}

/* MENU */
.menu {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
  overflow-y: auto;
  padding-bottom: 8px;
}

/* MENU ITEM */
.menu-item,
.collapse,
.logout {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 16px;
  border-radius: 12px;
  color: #e5e7eb;
  text-decoration: none;
  font-size: 14px;
  background: none;
  border: none;
  cursor: pointer;
}

/* CENTER ICON WHEN COLLAPSED */
.sidebar.collapsed .menu-item,
.sidebar.collapsed .collapse,
.sidebar.collapsed .logout {
  justify-content: center;
}

/* ICON */
.icon {
  font-size: 18px;
  color: #d1d5db;
}

/* HOVER */
.menu-item:hover,
.collapse:hover {
  background: rgba(255,255,255,0.12);
}

/* ACTIVE */
.router-link-active {
  background: rgba(255,255,255,0.22);
  font-weight: 600;
}

.router-link-active .icon {
  color: #facc15;
}

/* FOOTER */
.footer {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: auto;
}

/* LOGOUT */
.logout {
  color: #fecaca;
}

.logout:hover {
  background: rgba(239,68,68,0.2);
}

@media (max-width: 1024px) {
  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    z-index: 70;
    transform: translateX(-105%);
    transition: transform 0.25s ease, width 0.25s ease;
  }

  .sidebar.mobile-open {
    transform: translateX(0);
  }
}
</style>



