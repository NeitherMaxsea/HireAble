<template>
  <div class="dashboard">
    <div class="header">
      <h2>Admin Dashboard</h2>
      <p>System Overview</p>
    </div>

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

    <div class="grid">
      <div class="panel">
        <h3>Recent Applications</h3>
        <table v-if="recentApplications.length">
          <thead>
            <tr>
              <th>Applicant</th>
              <th>Job</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="app in recentApplications" :key="app.id">
              <td>{{ app.applicantLabel }}</td>
              <td>{{ app.jobTitle }}</td>
              <td :class="statusClass(app.status)">{{ app.statusLabel }}</td>
            </tr>
          </tbody>
        </table>
        <p v-else class="panel-empty">No application records yet.</p>
      </div>

      <div class="panel">
        <h3>System Activity</h3>
        <ul v-if="systemActivity.length" class="activity">
          <li v-for="item in systemActivity" :key="item.id">
            <strong>{{ item.label }}</strong>
            <span>{{ formatDateTime(item.timestampMs) }}</span>
          </li>
        </ul>
        <p v-else class="panel-empty">No activity yet.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { collection, getDocs, onSnapshot, query } from "@/lib/laravel-data"
import { db } from "@/lib/client-platform"

const stats = ref({
  totalUsers: 0,
  totalApplicants: 0,
  totalEmployers: 0,
  totalActive: 0,
  totalInactive: 0,
})

const unsubscribers = []
const usersLower = ref([])
const usersUpper = ref([])
const applications = ref([])
const jobs = ref([])

const combinedUsers = computed(() => {
  const map = new Map()
  ;[...usersLower.value, ...usersUpper.value].forEach((user) => {
    const key = String(user.uid || user.email || `${user.name}-${user.role}`).toLowerCase()
    if (!key) return
    map.set(key, user)
  })
  return Array.from(map.values())
})

const recentApplications = computed(() => {
  return [...applications.value].sort((a, b) => b.timestampMs - a.timestampMs).slice(0, 8)
})

const systemActivity = computed(() => {
  const userActivity = combinedUsers.value.flatMap((user) => {
    const items = []
    if (user.createdAtMs) {
      items.push({
        id: `user-created-${user.uid || user.email}-${user.createdAtMs}`,
        timestampMs: user.createdAtMs,
        label: `${user.name} account created`,
      })
    }
    if (user.lastLoginAtMs) {
      items.push({
        id: `user-login-${user.uid || user.email}-${user.lastLoginAtMs}`,
        timestampMs: user.lastLoginAtMs,
        label: `${user.name} signed in`,
      })
    }
    return items
  })

  const jobActivity = jobs.value.map((job) => ({
    id: `job-${job.id}-${job.timestampMs}`,
    timestampMs: job.timestampMs,
    label: `Job posted: ${job.title}`,
  }))

  const appActivity = applications.value.map((app) => ({
    id: `application-${app.id}-${app.timestampMs}`,
    timestampMs: app.timestampMs,
    label: `Application: ${app.applicantLabel} for ${app.jobTitle}`,
  }))

  return [...userActivity, ...jobActivity, ...appActivity]
    .filter((item) => item.timestampMs > 0)
    .sort((a, b) => b.timestampMs - a.timestampMs)
    .slice(0, 10)
})

onMounted(() => {
  void initDashboardData()
})

onBeforeUnmount(() => {
  unsubscribers.forEach((fn) => fn && fn())
})

async function initDashboardData() {
  const first = await fetchUsersOnce()
  setStatsFromUsers(dedupeUsers(first.list))
  usersLower.value = first.source === "users" ? first.list : []
  usersUpper.value = first.source === "Users" ? first.list : []
  subscribeUsersRealtime(first.source)
  subscribeApplications()
  subscribeJobs()
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
        if (collectionName === "Users") {
          usersUpper.value = list
        } else {
          usersLower.value = list
        }
        const merged = dedupeUsers([...usersLower.value, ...usersUpper.value])
        setStatsFromUsers(merged)
      },
      () => {
        // keep already loaded stats
      }
    )
    unsubscribers.push(unsub)
  })
}

function subscribeApplications() {
  const unsub = onSnapshot(
    collection(db, "applications"),
    (snapshot) => {
      applications.value = snapshot.docs
        .map((docSnap) => {
          const data = docSnap.data() || {}
          const status = String(data.status || "pending").toLowerCase()
          return {
            id: docSnap.id,
            timestampMs: toMillis(data.appliedAt || data.createdAt),
            applicantLabel: firstNonEmpty(
              data.applicantName,
              data.applicantEmail,
              data.userName,
              data.userEmail,
              data.email,
              "Unknown applicant"
            ),
            jobTitle: firstNonEmpty(data.jobTitle, data.title, "Unknown job"),
            status,
            statusLabel: capitalize(status),
          }
        })
        .filter((entry) => entry.timestampMs > 0)
    },
    () => {
      // keep latest available application data
    }
  )
  unsubscribers.push(unsub)
}

function subscribeJobs() {
  const unsub = onSnapshot(
    collection(db, "jobs"),
    (snapshot) => {
      jobs.value = snapshot.docs
        .map((docSnap) => {
          const data = docSnap.data() || {}
          return {
            id: docSnap.id,
            timestampMs: toMillis(data.createdAt),
            title: firstNonEmpty(data.title, "Untitled job"),
          }
        })
        .filter((entry) => entry.timestampMs > 0)
    },
    () => {
      // keep latest available job data
    }
  )
  unsubscribers.push(unsub)
}

function normalizeUsers(rawUsers) {
  return rawUsers
    .map((user) => ({
      uid: String(user.uid || user.id || "").trim(),
      email: String(user.email || "").trim().toLowerCase(),
      name: String(user.username || user.name || user.email || "Unknown User").trim(),
      role: String(user.role || "").toLowerCase(),
      createdAtMs: toMillis(user.createdAt),
      lastLoginAtMs: toMillis(user.lastLoginAt || user.lastSeenAt),
      status: user.status,
      isActive: user.isActive,
      active: user.active,
      disabledAt: user.disabledAt,
      deactivatedAt: user.deactivatedAt,
    }))
    .filter((user) => user.role === "applicant" || user.role === "employer" || user.role === "company_admin")
}

function setStatsFromUsers(users) {
  const totalApplicants = users.filter((user) => user.role === "applicant").length
  const totalEmployers = users.filter((user) => user.role === "employer" || user.role === "company_admin").length
  const totalActive = users.filter((user) => resolveStatus(user) === "active").length
  const totalInactive = users.length - totalActive
  stats.value = {
    totalUsers: users.length,
    totalApplicants,
    totalEmployers,
    totalActive,
    totalInactive,
  }
}

function resolveStatus(user) {
  const rawStatus = String(user.status || "").toLowerCase()
  const lastSeenMs = toMillis(user.lastSeenAt || user.lastLoginAtMs)
  const activeWindowMs = 15 * 60 * 1000

  if (rawStatus === "inactive" || rawStatus === "disabled" || rawStatus === "suspended") return "inactive"
  if (rawStatus === "active") return "active"
  if (typeof user.isActive === "boolean") return user.isActive ? "active" : "inactive"
  if (typeof user.active === "boolean") return user.active ? "active" : "inactive"
  if (user.disabledAt || user.deactivatedAt) return "inactive"
  if (lastSeenMs && Date.now() - lastSeenMs <= activeWindowMs) return "active"
  return "inactive"
}

function statusClass(status) {
  const normalized = String(status || "").toLowerCase()
  if (normalized === "approved") return "approved"
  if (normalized === "rejected") return "rejected"
  return "pending"
}

function formatDateTime(ms) {
  return new Date(ms).toLocaleString("en-US", {
    month: "short",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  })
}

function toMillis(value) {
  if (!value) return 0
  if (typeof value?.toMillis === "function") return value.toMillis()
  if (typeof value === "number") return value
  const parsed = Date.parse(value)
  return Number.isNaN(parsed) ? 0 : parsed
}

function firstNonEmpty(...values) {
  for (const value of values) {
    const text = String(value || "").trim()
    if (text) return text
  }
  return ""
}

function capitalize(value) {
  if (!value) return "-"
  return value.charAt(0).toUpperCase() + value.slice(1)
}

function dedupeUsers(users) {
  const map = new Map()
  users.forEach((user) => {
    const key = String(user.uid || user.email || `${user.name}-${user.role}`).toLowerCase()
    if (!key) return
    map.set(key, user)
  })
  return Array.from(map.values())
}
</script>

<style scoped>
.dashboard {
  padding: 10px;
}

.header h2 {
  margin: 0;
}

.header p {
  color: #0f766e;
  margin-bottom: 20px;
}

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
  border: 1px solid #d8ece9;
}

.card h3 {
  margin: 0;
  font-size: 15px;
  color: #0f766e;
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

.grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

.panel {
  background: white;
  padding: 20px;
  border-radius: 10px;
  border: 1px solid #d8ece9;
}

.panel h3 {
  margin-bottom: 15px;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  padding: 10px;
  border-bottom: 1px solid #eee;
  text-align: left;
}

.pending {
  color: #c2410c;
}

.approved {
  color: #15803d;
}

.rejected {
  color: #b91c1c;
}

.activity {
  list-style: none;
  padding: 0;
  margin: 0;
}

.activity li {
  padding: 8px 0;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
}

.activity strong {
  color: #0f172a;
  font-size: 13px;
}

.activity span {
  color: #64748b;
  font-size: 12px;
  white-space: nowrap;
}

.panel-empty {
  margin: 0;
  color: #64748b;
  font-size: 13px;
  padding: 8px 0;
}

@media (max-width: 980px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
