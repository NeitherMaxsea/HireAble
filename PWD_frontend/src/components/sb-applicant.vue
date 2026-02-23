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

      <button class="logout" @click="showLogoutConfirm = true">
        <i class="bi bi-box-arrow-right icon"></i>
        <span v-if="!isCollapsed">Logout</span>
      </button>

    </div>

    <div v-if="showLogoutConfirm" class="logout-modal-backdrop" @click.self="showLogoutConfirm = false">
      <div class="logout-modal">
        <h3>Log out?</h3>
        <p>Are you sure you want to log out?</p>
        <div class="logout-modal-actions">
          <button class="logout-modal-btn cancel" @click="showLogoutConfirm = false" :disabled="logoutLoading">
            No
          </button>
          <button class="logout-modal-btn confirm" @click="logout" :disabled="logoutLoading">
            <span v-if="logoutLoading">Logging out...</span>
            <span v-else>Yes</span>
          </button>
        </div>
      </div>
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
const showLogoutConfirm = ref(false)
const logoutLoading = ref(false)

const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value
}

const closeMobile = () => {
  emit("close-mobile")
}

const logout = async () => {
  logoutLoading.value = true
  try {
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
    showLogoutConfirm.value = false
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
  } finally {
    logoutLoading.value = false
  }
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

.logout-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(2px);
  z-index: 2000;
  display: grid;
  place-items: center;
  padding: 16px;
}

.logout-modal {
  width: min(360px, 100%);
  background: #ffffff;
  color: #0f172a;
  border-radius: 14px;
  border: 1px solid #dbe4ee;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.22);
  padding: 16px;
}

.logout-modal h3 {
  margin: 0;
  font-size: 1.05rem;
}

.logout-modal p {
  margin: 8px 0 0;
  color: #475569;
  line-height: 1.5;
  font-size: 0.9rem;
}

.logout-modal-actions {
  margin-top: 14px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.logout-modal-btn {
  min-height: 38px;
  padding: 8px 12px;
  border-radius: 10px;
  border: 1px solid transparent;
  font-weight: 700;
  cursor: pointer;
}

.logout-modal-btn.cancel {
  background: #ffffff;
  border-color: #dbe4ee;
  color: #334155;
}

.logout-modal-btn.confirm {
  background: #b91c1c;
  color: #ffffff;
}

.logout-modal-btn:disabled {
  opacity: 0.75;
  cursor: not-allowed;
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



