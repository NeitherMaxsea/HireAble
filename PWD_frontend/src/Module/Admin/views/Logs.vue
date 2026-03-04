<template>
  <section class="logs-page">
    <header class="logs-header">
      <h2>System Logs</h2>
      <p>Realtime activity feed.</p>
    </header>

    <div class="filters">
      <label>
        <span>From</span>
        <input v-model="fromDate" type="date" />
      </label>
      <label>
        <span>To</span>
        <input v-model="toDate" type="date" />
      </label>
      <label>
        <span>Source</span>
        <select v-model="sourceFilter">
          <option value="all">All</option>
          <option value="users">Users</option>
          <option value="jobs">Jobs</option>
          <option value="applications">Applications</option>
        </select>
      </label>
      <label>
        <span>Role</span>
        <select v-model="roleFilter">
          <option value="all">All</option>
          <option value="admin">Admin</option>
          <option value="company_admin">Company Admin</option>
          <option value="hr">HR</option>
          <option value="finance">Finance</option>
          <option value="employer">Employer</option>
          <option value="applicant">Applicant</option>
        </select>
      </label>
      <button type="button" class="clear-btn" @click="clearFilters">Clear</button>
    </div>

    <div class="summary-grid">
      <div class="summary-card">
        <span>Total Events</span>
        <strong>{{ filteredEvents.length }}</strong>
      </div>
      <div class="summary-card">
        <span>User Events</span>
        <strong>{{ userEventCount }}</strong>
      </div>
      <div class="summary-card">
        <span>Job Events</span>
        <strong>{{ jobEventCount }}</strong>
      </div>
      <div class="summary-card">
        <span>Application Events</span>
        <strong>{{ applicationEventCount }}</strong>
      </div>
    </div>

    <div class="logs-card">
      <div v-if="loading" class="logs-empty">
        <i class="bi bi-hourglass-split"></i>
        <p>Loading logs...</p>
      </div>

      <div v-else-if="loadError && !events.length" class="logs-empty">
        <i class="bi bi-exclamation-triangle"></i>
        <p>{{ loadError }}</p>
      </div>

      <div v-else-if="!filteredEvents.length" class="logs-empty">
        <i class="bi bi-journal-text"></i>
        <p>No logs match the current filters.</p>
      </div>

      <div v-else class="logs-table-wrap">
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>Source</th>
              <th>Event</th>
              <th>Role</th>
              <th>Account</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="event in pagedEvents" :key="event.id">
              <td class="time">{{ formatDateTime(event.timestampMs) }}</td>
              <td>
                <span class="badge source" :class="event.sourceClass">{{ event.sourceLabel }}</span>
              </td>
              <td>
                <span class="badge event">{{ event.eventLabel }}</span>
              </td>
              <td>
                <span class="badge role">{{ roleLabel(event.role) }}</span>
              </td>
              <td class="account">{{ event.accountLabel }}</td>
              <td class="details">{{ event.details }}</td>
            </tr>
          </tbody>
        </table>

        <div v-if="totalPages > 1" class="load-more-wrap">
          <button type="button" class="load-more" :disabled="currentPage === 1" @click="goPrevPage">Previous</button>
          <span class="page-indicator">Page {{ currentPage }} of {{ totalPages }}</span>
          <button type="button" class="load-more" :disabled="currentPage === totalPages" @click="goNextPage">Next</button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue"
import api from "@/services/api"

const loading = ref(true)
const loadError = ref("")
const events = ref([])
const fromDate = ref("")
const toDate = ref("")
const sourceFilter = ref("all")
const roleFilter = ref("all")
const PAGE_SIZE = 10
const currentPage = ref(1)
const userRoleByEmail = ref({})
let refreshTimer = null

const filteredEvents = computed(() => {
  const minMs = parseDateStart(fromDate.value)
  const maxMs = parseDateEnd(toDate.value)
  return events.value
    .filter((item) => item.timestampMs > 0)
    .filter((item) => {
    const sourceMatch = sourceFilter.value === "all" || item.sourceClass === sourceFilter.value
    const roleMatch = roleFilter.value === "all" || item.role === roleFilter.value
    const minMatch = !minMs || item.timestampMs >= minMs
    const maxMatch = !maxMs || item.timestampMs <= maxMs
    return sourceMatch && roleMatch && minMatch && maxMatch
  })
    .sort((a, b) => b.timestampMs - a.timestampMs)
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredEvents.value.length / PAGE_SIZE)))
const pagedEvents = computed(() => {
  const page = Math.min(Math.max(currentPage.value, 1), totalPages.value)
  const start = (page - 1) * PAGE_SIZE
  return filteredEvents.value.slice(start, start + PAGE_SIZE)
})
const userEventCount = computed(() => filteredEvents.value.filter((event) => event.sourceClass === "users").length)
const jobEventCount = computed(() => filteredEvents.value.filter((event) => event.sourceClass === "jobs").length)
const applicationEventCount = computed(() => filteredEvents.value.filter((event) => event.sourceClass === "applications").length)

onMounted(async () => {
  await Promise.all([loadUserRoles(), loadLogs()])
  refreshTimer = window.setInterval(() => {
    void loadLogs(false)
  }, 10000)
})

onBeforeUnmount(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
})

watch([fromDate, toDate, sourceFilter, roleFilter], () => {
  currentPage.value = 1
})

async function loadLogs(showLoading = true) {
  if (showLoading) loading.value = true
  try {
    const response = await api.get("/logs")
    const rows = Array.isArray(response?.data) ? response.data : []
    events.value = rows
      .map((row, idx) => mapBackendLogRow(row, idx))
      .filter((item) => item.timestampMs > 0)
    loadError.value = ""
  } catch (err) {
    console.error(err)
    if (!events.value.length) {
      loadError.value = err?.response?.data?.message || "Cannot load logs."
    }
  } finally {
    if (showLoading) loading.value = false
  }
}

function mapBackendLogRow(row, index) {
  const sourceClass = normalizeSource(row?.source)
  const eventLabel = toEventLabel(row?.eventType)
  const timestampMs = toMillis(row?.createdAt || row?.updatedAt)
  const sourceLabelMap = {
    users: "Users",
    jobs: "Jobs",
    applications: "Applications",
  }
  const role = inferRole(row)
  return {
    id: String(row?.id || `log-${index}`),
    timestampMs,
    sourceClass,
    sourceLabel: sourceLabelMap[sourceClass] || "System",
    eventLabel,
    role,
    accountLabel: String(row?.account || "System"),
    details: String(row?.details || "-"),
  }
}

async function loadUserRoles() {
  try {
    const response = await api.get("/users")
    const rows = Array.isArray(response?.data) ? response.data : []
    const nextMap = {}
    rows.forEach((row) => {
      const email = String(row?.email || "").trim().toLowerCase()
      const role = normalizeRole(String(row?.role || "").trim().toLowerCase())
      if (email && role) {
        nextMap[email] = role
      }
    })
    userRoleByEmail.value = nextMap
  } catch {
    userRoleByEmail.value = {}
  }
}

function normalizeSource(value) {
  const source = String(value || "").trim().toLowerCase()
  if (source.includes("user")) return "users"
  if (source.includes("job")) return "jobs"
  if (source.includes("application")) return "applications"
  return source || "system"
}

function toEventLabel(value) {
  const raw = String(value || "").trim()
  if (!raw) return "System Event"
  return raw
    .replace(/[_-]+/g, " ")
    .replace(/\s+/g, " ")
    .trim()
    .replace(/\b\w/g, (ch) => ch.toUpperCase())
}

function normalizeRole(value) {
  const role = String(value || "").trim().toLowerCase()
  if (!role) return "unknown"
  if (role.includes("company") && role.includes("admin")) return "company_admin"
  if (role === "hr" || role.includes("human resource")) return "hr"
  if (role.includes("finance")) return "finance"
  if (role.includes("operation")) return "operation"
  if (role === "employer") return "employer"
  if (role === "applicant") return "applicant"
  if (role === "admin") return "admin"
  return role
}

function inferRole(row) {
  const account = String(row?.account || "").trim().toLowerCase()
  const details = String(row?.details || "").trim().toLowerCase()
  const event = String(row?.eventType || "").trim().toLowerCase()
  const source = String(row?.source || "").trim().toLowerCase()
  const bucket = `${account} ${details} ${event} ${source}`

  if (bucket.includes("company admin") || bucket.includes("company_admin")) return "company_admin"
  if (/\bhr\b/.test(bucket) || bucket.includes("human resource")) return "hr"
  if (bucket.includes("finance")) return "finance"
  if (bucket.includes("operation")) return "operation"
  if (bucket.includes("applicant")) return "applicant"
  if (bucket.includes("employer")) return "employer"
  if (bucket.includes("admin")) return "admin"

  const emailMatch = account.match(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i)
  const email = String(emailMatch?.[0] || "").toLowerCase()
  if (email && userRoleByEmail.value[email]) {
    return normalizeRole(userRoleByEmail.value[email])
  }

  return "unknown"
}

function roleLabel(role) {
  const normalized = normalizeRole(role)
  if (normalized === "company_admin") return "Company Admin"
  if (normalized === "hr") return "HR"
  if (normalized === "finance") return "Finance"
  if (normalized === "operation") return "Operation"
  if (normalized === "employer") return "Employer"
  if (normalized === "applicant") return "Applicant"
  if (normalized === "admin") return "Admin"
  return "Unknown"
}

function goPrevPage() {
  currentPage.value = Math.max(1, currentPage.value - 1)
}

function goNextPage() {
  currentPage.value = Math.min(totalPages.value, currentPage.value + 1)
}

function toMillis(value) {
  if (!value) return 0
  if (typeof value === "number") return value
  const parsed = Date.parse(String(value))
  return Number.isNaN(parsed) ? 0 : parsed
}

function formatDateTime(ms) {
  return new Date(ms).toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit"
  })
}

function parseDateStart(value) {
  if (!value) return 0
  const parsed = new Date(`${value}T00:00:00`)
  return Number.isNaN(parsed.getTime()) ? 0 : parsed.getTime()
}

function parseDateEnd(value) {
  if (!value) return 0
  const parsed = new Date(`${value}T23:59:59.999`)
  return Number.isNaN(parsed.getTime()) ? 0 : parsed.getTime()
}

function clearFilters() {
  fromDate.value = ""
  toDate.value = ""
  sourceFilter.value = "all"
  roleFilter.value = "all"
  currentPage.value = 1
}
</script>

<style scoped>
.logs-page {
  padding: 10px;
}

.logs-header h2 {
  margin: 0;
}

.logs-header p {
  color: #0f766e;
  margin: 8px 0 14px;
}

.filters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 14px;
  align-items: end;
}

.filters label {
  display: grid;
  gap: 4px;
}

.filters span {
  font-size: 12px;
  color: #0f766e;
}

.filters input,
.filters select {
  border: 1px solid #cce7e3;
  background: #f6fcfb;
  color: #1f2937;
  border-radius: 8px;
  padding: 7px 10px;
  min-width: 150px;
}

.clear-btn {
  border: 1px solid #94d5cd;
  border-radius: 8px;
  background: #ecf9f7;
  color: #0f766e;
  font-weight: 600;
  padding: 8px 12px;
  cursor: pointer;
}

.summary-grid {
  margin-bottom: 14px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 10px;
}

.summary-card {
  background: #ffffff;
  border: 1px solid #d8ece9;
  border-radius: 10px;
  padding: 12px;
}

.summary-card span {
  color: #0f766e;
  font-size: 12px;
}

.summary-card strong {
  display: block;
  margin-top: 4px;
  font-size: 20px;
  color: #0f3b39;
}

.logs-card {
  background: #fff;
  border-radius: 10px;
  padding: 18px;
  border: 1px solid #d8ece9;
}

.logs-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #6b7280;
  min-height: 180px;
  text-align: center;
}

.logs-empty i {
  font-size: 2rem;
  color: #0d9488;
}

.logs-empty p {
  margin: 0;
}

.logs-table-wrap {
  overflow: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
}

th,
td {
  text-align: left;
  padding: 12px 10px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
}

th {
  font-size: 12px;
  color: #0f766e;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  background: #f2fbfa;
}

.time {
  white-space: nowrap;
  color: #334155;
}

.details {
  color: #0f172a;
}

.account {
  color: #334155;
  max-width: 220px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
}

.badge.source.users {
  background: #ccfbf1;
  color: #115e59;
}

.badge.source.jobs {
  background: #dcfce7;
  color: #166534;
}

.badge.source.applications {
  background: #ffedd5;
  color: #9a3412;
}

.badge.event {
  background: #e2e8f0;
  color: #334155;
}

.badge.role {
  background: #ecfeff;
  color: #155e75;
}

.load-more-wrap {
  margin-top: 12px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
}

.load-more {
  border: 1px solid #8cd4cc;
  background: #ecf9f7;
  color: #0f766e;
  border-radius: 8px;
  padding: 8px 12px;
  font-weight: 600;
  cursor: pointer;
}

.load-more:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-indicator {
  font-size: 13px;
  color: #0f3b39;
  font-weight: 600;
}

@media (max-width: 760px) {
  .filters input,
  .filters select {
    min-width: 130px;
  }
}
</style>


