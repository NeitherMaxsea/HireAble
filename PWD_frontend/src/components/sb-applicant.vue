<template>
  <aside class="sidebar" :class="{ collapsed: isCollapsed, 'mobile-open': mobileOpen }">

    <!-- APPLICANT HEADER -->
    <div class="brand">
      <template v-if="!isCollapsed">
        <p class="applicant-name">{{ applicantName }}</p>
        <p class="applicant-email">{{ applicantEmail }}</p>
      </template>
      <div v-else class="mini-user-badge" :title="applicantName">
        {{ applicantInitial }}
      </div>
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

      <router-link to="/applicant/profile" class="menu-item" @click="closeMobile">
        <i class="bi bi-person icon"></i>
        <span v-if="!isCollapsed">Profile</span>
      </router-link>

      <router-link to="/applicant/messages" class="menu-item" @click="closeMobile">
        <i class="bi bi-chat-dots icon"></i>
        <span v-if="!isCollapsed">Messages</span>
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
import { computed, ref } from "vue"
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

const applicantName = computed(() =>
  String(localStorage.getItem("userName") || localStorage.getItem("name") || "Applicant").trim() || "Applicant"
)

const applicantEmail = computed(() =>
  String(localStorage.getItem("userEmail") || "No email").trim() || "No email"
)

const applicantInitial = computed(() => {
  const base = applicantName.value || applicantEmail.value || "A"
  return String(base).trim().charAt(0).toUpperCase() || "A"
})

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
  background:
    radial-gradient(circle at 12% 8%, rgba(34, 197, 94, 0.22), transparent 42%),
    radial-gradient(circle at 88% 0%, rgba(244, 196, 31, 0.12), transparent 34%),
    linear-gradient(180deg, #0f4f25 0%, #0d3f1f 56%, #0b351a 100%);
  color: #f8fafc;
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
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  gap: 4px;
  margin: 2px 4px 18px;
  padding: 14px 14px 12px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.12);
  background: rgba(255,255,255,0.06);
}

.applicant-name {
  margin: 0;
  color: #ffffff;
  font-size: 1.02rem;
  font-weight: 800;
  line-height: 1.15;
  word-break: break-word;
}

.applicant-email {
  margin: 0;
  color: rgba(229, 231, 235, 0.92);
  font-size: 0.72rem;
  line-height: 1.25;
  word-break: break-word;
}

.mini-user-badge {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #ffffff;
  font-weight: 800;
  font-size: 16px;
  box-shadow: 0 8px 18px rgba(22, 163, 74, 0.28);
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
  color: #eaf4ee;
  text-decoration: none;
  font-size: 14px;
  background: none;
  border: none;
  cursor: pointer;
  transition: background-color .18s ease, border-color .18s ease, color .18s ease, transform .18s ease;
  border: 1px solid transparent;
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
  color: #dce8df;
}

/* HOVER */
.menu-item:hover,
.collapse:hover {
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.14), rgba(255,255,255,0.06));
  border-color: rgba(134, 239, 172, 0.24);
  color: #ffffff;
  transform: translateY(-1px);
}

.menu-item:hover .icon,
.collapse:hover .icon {
  color: #f0fdf4;
}

/* ACTIVE */
.router-link-active {
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.20), rgba(255,255,255,0.08));
  font-weight: 600;
  border: 1px solid rgba(187, 247, 208, 0.45);
  box-shadow: inset 0 0 0 1px rgba(255,255,255,0.04);
}

.router-link-active .icon {
  color: #f4c41f;
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
  color: #fee2e2;
}

.logout:hover {
  background: linear-gradient(135deg, rgba(239,68,68,0.20), rgba(255,255,255,0.05));
  border-color: rgba(254, 202, 202, 0.24);
  color: #fff7f7;
  transform: translateY(-1px);
}

.logout:hover .icon {
  color: #ffe4e6;
}

.logout-modal-backdrop {
  position: fixed;
  inset: 0;
  background:
    radial-gradient(circle at 20% 10%, rgba(34, 197, 94, 0.14), transparent 34%),
    rgba(15, 23, 42, 0.46);
  backdrop-filter: blur(6px);
  z-index: 2000;
  display: grid;
  place-items: center;
  padding: 16px;
}

.logout-modal {
  width: min(420px, 100%);
  background: linear-gradient(180deg, #ffffff 0%, #fbfefc 100%);
  color: #0f172a;
  border-radius: 18px;
  border: 1px solid #d9e8dc;
  box-shadow: 0 26px 58px rgba(15, 23, 42, 0.22);
  padding: 0;
  overflow: hidden;
}

.logout-modal h3 {
  margin: 0;
  font-size: 1.1rem;
  color: #0f172a;
}

.logout-modal p {
  margin: 8px 0 0;
  color: #475569;
  line-height: 1.5;
  font-size: 0.9rem;
}

.logout-modal > h3,
.logout-modal > p,
.logout-modal-actions {
  padding-left: 18px;
  padding-right: 18px;
}

.logout-modal > h3 {
  padding-top: 16px;
}

.logout-modal > p {
  margin-top: 6px;
}

.logout-modal-actions {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding-bottom: 16px;
  border-top: 1px solid #eef4ef;
  padding-top: 14px;
  background: rgba(248, 250, 252, 0.7);
}

.logout-modal-btn {
  min-height: 40px;
  padding: 8px 14px;
  border-radius: 12px;
  border: 1px solid transparent;
  font-weight: 700;
  cursor: pointer;
  transition: all .18s ease;
}

.logout-modal-btn.cancel {
  background: #ffffff;
  border-color: #dce7de;
  color: #334155;
}

.logout-modal-btn.cancel:hover:not(:disabled) {
  background: #f5faf6;
  border-color: #bbf7d0;
  color: #166534;
}

.logout-modal-btn.confirm {
  background: linear-gradient(135deg, #b91c1c, #dc2626);
  color: #ffffff;
  box-shadow: 0 8px 16px rgba(220, 38, 38, 0.18);
}

.logout-modal-btn.confirm:hover:not(:disabled) {
  filter: brightness(1.03);
  transform: translateY(-1px);
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



