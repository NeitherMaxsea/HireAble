<template>
  <div class="company-layout">
    <aside class="sidebar" :class="{ open: sidebarOpen, collapsed: collapsed }">
      <div class="brand-panel">
        <div class="logo-orb">
          <i class="bi bi-buildings"></i>
        </div>
        <div>
          <h3>{{ companyName }}</h3>
          <p>Company Console</p>
          <small>{{ adminName }}</small>
        </div>
      </div>

      <nav class="menu">
        <router-link to="/company-admin/dashboard" class="menu-item" @click="sidebarOpen = false">
          <i class="bi bi-grid-1x2"></i>
          <span>Dashboard</span>
        </router-link>
        <router-link to="/company-admin/employers-management" class="menu-item" @click="sidebarOpen = false">
          <i class="bi bi-people"></i>
          <span>Employers Management</span>
        </router-link>
        <router-link to="/company-admin/logs" class="menu-item" @click="sidebarOpen = false">
          <i class="bi bi-journal-text"></i>
          <span>Logs</span>
        </router-link>
      </nav>

      <div class="side-foot">
        <button class="logout-btn" @click="logout">
          <i class="bi bi-box-arrow-right"></i>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <div class="main-area">
      <header class="topbar">
        <button class="menu-toggle" @click="handleToggleSidebar" aria-label="Toggle menu">
          <i class="bi bi-list"></i>
        </button>
        <div class="top-title">
          <h4>{{ pageTitle }}</h4>
          <small>{{ dateLabel }}</small>
        </div>
        <div class="admin-chip">
          <i class="bi bi-shield-check"></i>
          <span>Company Admin</span>
        </div>
      </header>

      <main class="content">
        <router-view />
      </main>
    </div>

    <div v-if="sidebarOpen" class="overlay" @click="sidebarOpen = false"></div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { signOut } from "@/lib/session-auth"
import { addDoc, collection, doc, serverTimestamp, updateDoc } from "@/lib/laravel-data"
import { auth, db } from "@/lib/client-platform"

const route = useRoute()
const router = useRouter()
const companyName = ref(localStorage.getItem("companyName") || "My Company")
const adminName = ref(localStorage.getItem("userName") || localStorage.getItem("userEmail") || "Company Admin")
const sidebarOpen = ref(false)
const collapsed = ref(false)
const now = ref(new Date())
let timer = null

const pageTitle = computed(() => {
  if (route.path.includes("/employers-management")) return "Employers Management"
  if (route.path.includes("/add-employee")) return "Add Employee"
  if (route.path.includes("/logs")) return "Logs"
  return "Dashboard"
})  

const dateLabel = computed(() =>
  now.value.toLocaleString("en-US", {
    weekday: "short",
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit"
  })
)

onMounted(() => {
  timer = setInterval(() => {
    now.value = new Date()
  }, 1000)
})

// close overlay when resizing to desktop
window.addEventListener("resize", () => {
  if (window.innerWidth > 1024) {
    sidebarOpen.value = false
  }
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})

async function logout() {
  const uid = auth.currentUser?.uid
  const email = auth.currentUser?.email || localStorage.getItem("userEmail") || ""
  const localCompanyId = localStorage.getItem("companyId") || ""
  const localCompanyName = localStorage.getItem("companyName") || ""
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
        // continue
      }
    }
  }

  if (uid && localCompanyId) {
    try {
      await addDoc(collection(db, "activity_logs"), {
        event: "Company Admin Logout",
        details: "Company admin signed out",
        actorUid: uid,
        actorEmail: String(email),
        companyId: String(localCompanyId),
        companyName: String(localCompanyName),
        targetUid: String(uid),
        targetEmail: String(email),
        createdAt: serverTimestamp()
      })
    } catch {
      // no-op
    }
  }

  await signOut()
  localStorage.clear()
  router.push("/login")
}

function handleToggleSidebar() {
  if (window.innerWidth <= 1024) {
    sidebarOpen.value = !sidebarOpen.value
  } else {
    collapsed.value = !collapsed.value
  }
}
</script>

<style>

.company-layout {
  --company-bg: #f4f6fb;
  --company-card: #ffffff;
  --company-ink: #0f172a;
  --company-muted: #64748b;
  --company-brand: #0b5fff;
  --company-brand-dark: #0042bd;
  display: grid;
  grid-template-columns: auto 1fr;
  min-height: 100vh;
  background:
    radial-gradient(900px 420px at 8% -8%, #dbeafe 0%, rgba(219, 234, 254, 0) 65%),
    radial-gradient(1000px 480px at 110% 10%, #fde68a 0%, rgba(253, 230, 138, 0) 55%),
    var(--company-bg);
}

.sidebar {
  width: 280px;
  background: linear-gradient(180deg, #06132d 0%, #0b234f 100%);
  color: #e2e8f0;
  padding: 22px;
  display: flex;
  flex-direction: column;
  gap: 22px;
  position: relative;
  z-index: 30;
  box-shadow: 10px 0 40px rgba(2, 6, 23, 0.12);
  transition: width 0.28s ease, transform 0.25s ease;
}

.sidebar.collapsed {
  width: 80px;
}

.brand-panel {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.logo-orb {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: linear-gradient(135deg, #60a5fa, #a5b4fc);
  color: #06204a;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.brand-panel h3 {
  margin: 0;
  font-size: 18px;
  letter-spacing: 0.2px;
}

.brand-panel p {
  margin: 5px 0 0;
  color: #bfdbfe;
  font-size: 13px;
}

.brand-panel small {
  display: block;
  margin-top: 4px;
  color: #e2e8f0;
  font-size: 12px;
  opacity: 0.9;
}

.menu {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: #dbeafe;
  padding: 12px 13px;
  border-radius: 12px;
  border: 1px solid transparent;
  transition: 0.2s ease;
}

.menu-item.router-link-active,
.menu-item:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(255, 255, 255, 0.18);
}

.side-foot {
  margin-top: auto;
}

.logout-btn {
  width: 100%;
  border: none;
  background: linear-gradient(135deg, #ef4444, #b91c1c);
  color: #fff;
  border-radius: 12px;
  padding: 11px 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.main-area {
  /* Grid places this automatically in column 2 */
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.topbar {
  height: 72px;
  padding: 0 20px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.menu-toggle {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: 1px solid #dbe3f4;
  background: #fff;
  color: #0f172a;
  font-size: 20px;
  display: none;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.top-title {
  min-width: 0;
}

.top-title h4 {
  margin: 0;
  color: var(--company-ink);
  font-size: 20px;
}

.top-title small {
  color: var(--company-muted);
  font-size: 12px;
}

.admin-chip {
  margin-left: auto;
  background: #fff;
  border: 1px solid #dbe3f4;
  border-radius: 999px;
  padding: 8px 12px;
  font-size: 12px;
  color: #1e3a8a;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.content {
  flex: 1;
  padding: 0 20px 20px;
}

.overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.35);
  z-index: 20;
}

@media (max-width: 1024px) {
  .company-layout {
    grid-template-columns: 1fr;
  }

  .sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    transform: translateX(-105%);
    transition: transform 0.25s ease;
    width: 280px;
    box-shadow: 10px 0 40px rgba(2, 6, 23, 0.25);
  }

  .sidebar.open {
    transform: translateX(0);
  }

  .main-area {
    margin-left: 0;
  }

  .menu-toggle {
    display: inline-flex;
  }
}
</style>
