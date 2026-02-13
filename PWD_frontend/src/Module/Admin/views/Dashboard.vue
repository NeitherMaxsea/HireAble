<template>
  <div class="dashboard">

    <!-- Page Title -->
    <div class="header">
      <h2>Admin Dashboard</h2>
      <p>System Overview</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats">

      <div class="card">
        <h3>Total Users</h3>
        <p class="number">{{ stats.totalUsers }}</p>
      </div>

      <div class="card">
        <h3>Total Applicants</h3>
        <p class="number">{{ stats.totalApplicants }}</p>
      </div>

      <div class="card">
        <h3>Total Employers</h3>
        <p class="number">{{ stats.totalEmployers }}</p>
      </div>

      <div class="card">
        <h3>Total Active</h3>
        <p class="number active-number">{{ stats.totalActive }}</p>
      </div>

      <div class="card">
        <h3>Total Inactive</h3>
        <p class="number inactive-number">{{ stats.totalInactive }}</p>
      </div>

    </div>

    <!-- Content Grid -->
    <div class="grid">

      <!-- Recent Applications -->
      <div class="panel">
        <h3>Recent Applications</h3>

        <table>
          <thead>
            <tr>
              <th>Applicant</th>
              <th>Job</th>
              <th>Status</th>
            </tr>
          </thead>

          <tbody>
            <tr>
              <td>Juan Dela Cruz</td>
              <td>Data Encoder</td>
              <td class="pending">Pending</td>
            </tr>

            <tr>
              <td>Maria Santos</td>
              <td>Office Clerk</td>
              <td class="approved">Approved</td>
            </tr>

            <tr>
              <td>Pedro Reyes</td>
              <td>Web Assistant</td>
              <td class="rejected">Rejected</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- System Activity -->
      <div class="panel">
        <h3>System Activity</h3>

        <ul class="activity">
          <li>New employer registered</li>
          <li>Job posting approved</li>
          <li>New applicant created account</li>
          <li>Application status updated</li>
        </ul>

      </div>

    </div>

  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue"
import { collection, getDocs, onSnapshot, query } from "@/lib/laravel-data"
import { db } from "@/lib/client-platform"

const stats = ref({
  totalUsers: 0,
  totalApplicants: 0,
  totalEmployers: 0,
  totalActive: 0,
  totalInactive: 0
})

const unsubscribers = []

onMounted(() => {
  void initDashboardStats()
})

onBeforeUnmount(() => {
  unsubscribers.forEach((fn) => fn && fn())
})

async function initDashboardStats() {
  const first = await fetchUsersOnce()
  setStatsFromUsers(first.list)
  subscribeUsersRealtime(first.source)
}

async function fetchUsersOnce() {
  const candidates = ["users", "Users"]

  for (const collectionName of candidates) {
    try {
      const snap = await getDocs(query(collection(db, collectionName)))
      const list = normalizeUsers(snap.docs.map((docSnap) => docSnap.data()))
      if (list.length) {
        return { list, source: collectionName }
      }
    } catch {
      // fall through
    }
  }

  return { list: [], source: "users" }
}

function subscribeUsersRealtime(source) {
  const targets = source === "Users" ? ["Users", "users"] : ["users", "Users"]
  targets.forEach((collectionName) => {
    const unsub = onSnapshot(
      query(collection(db, collectionName)),
      (snapshot) => {
        const list = normalizeUsers(snapshot.docs.map((docSnap) => docSnap.data()))
        if (list.length) {
          setStatsFromUsers(list)
        }
      },
      () => {
        // keep already loaded stats
      }
    )
    unsubscribers.push(unsub)
  })
}

function normalizeUsers(rawUsers) {
  return rawUsers.filter((user) => {
    const role = String(user.role || "").toLowerCase()
    return role === "applicant" || role === "employer" || role === "company_admin"
  })
}

function setStatsFromUsers(users) {
  const totalApplicants = users.filter(
    (user) => String(user.role || "").toLowerCase() === "applicant"
  ).length

  const totalEmployers = users.filter(
    (user) => {
      const role = String(user.role || "").toLowerCase()
      return role === "employer" || role === "company_admin"
    }
  ).length

  const totalActive = users.filter((user) => resolveStatus(user) === "active").length
  const totalInactive = users.length - totalActive

  stats.value = {
    totalUsers: users.length,
    totalApplicants,
    totalEmployers,
    totalActive,
    totalInactive
  }
}

function resolveStatus(user) {
  const rawStatus = String(user.status || "").toLowerCase()
  const lastSeenMs = toMillis(user.lastSeenAt || user.lastLoginAt)
  const activeWindowMs = 15 * 60 * 1000

  if (rawStatus === "inactive" || rawStatus === "disabled" || rawStatus === "suspended") {
    return "inactive"
  }
  if (rawStatus === "active") {
    return "active"
  }
  if (typeof user.isActive === "boolean") {
    return user.isActive ? "active" : "inactive"
  }
  if (typeof user.active === "boolean") {
    return user.active ? "active" : "inactive"
  }
  if (user.disabledAt || user.deactivatedAt) {
    return "inactive"
  }
  if (lastSeenMs && Date.now() - lastSeenMs <= activeWindowMs) {
    return "active"
  }
  return "inactive"
}

function toMillis(value) {
  if (!value) return 0
  if (typeof value?.toMillis === "function") return value.toMillis()
  if (typeof value === "number") return value
  const parsed = Date.parse(value)
  return Number.isNaN(parsed) ? 0 : parsed
}
</script>

<style scoped>

.dashboard {
  padding: 10px;
}

/* Header */

.header h2 {
  margin: 0;
}

.header p {
  color: gray;
  margin-bottom: 20px;
}

/* Stats */

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.card {
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 0 5px rgba(0,0,0,0.05);
}

.card h3 {
  margin: 0;
  font-size: 15px;
  color: gray;
}

.number {
  font-size: 32px;
  font-weight: bold;
  margin-top: 10px;
}

.active-number {
  color: #15803d;
}

.inactive-number {
  color: #b91c1c;
}

/* Grid */

.grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

/* Panel */

.panel {
  background: white;
  padding: 20px;
  border-radius: 10px;
}

.panel h3 {
  margin-bottom: 15px;
}

/* Table */

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 10px;
  border-bottom: 1px solid #eee;
  text-align: left;
}

.pending { color: orange; }
.approved { color: green; }
.rejected { color: red; }

/* Activity */

.activity {
  list-style: none;
  padding: 0;
}

.activity li {
  padding: 10px 0;
  border-bottom: 1px solid #eee;
}

</style>


