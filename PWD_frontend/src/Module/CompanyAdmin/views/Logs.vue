<template>
  <section class="logs-page">
    <header class="logs-header">
      <h2>Company Activity Logs</h2>
      <p>Realtime activity for employee accounts under your company.</p>
    </header>

    <div class="summary-grid">
      <div class="summary-card">
        <span>Total Events</span>
        <strong>{{ events.length }}</strong>
      </div>
      <div class="summary-card">
        <span>Active Accounts</span>
        <strong>{{ activeAccountsCount }}</strong>
      </div>
      <div class="summary-card">
        <span>Role-tagged Actions</span>
        <strong>{{ roleTaggedEventsCount }}</strong>
      </div>
      <div class="summary-card">
        <span>Admin Actions</span>
        <strong>{{ adminEventsCount }}</strong>
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

      <div v-else-if="!events.length" class="logs-empty">
        <i class="bi bi-journal-text"></i>
        <p>No activity yet for this company admin.</p>
      </div>

      <div v-else class="logs-table-wrap">
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>Source</th>
              <th>Event</th>
              <th>Account</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="event in events" :key="event.id">
              <td class="time">{{ formatDateTime(event.timestampMs) }}</td>
              <td>
                <span class="badge source" :class="event.sourceClass">{{ event.sourceLabel }}</span>
              </td>
              <td>
                <span class="badge event">{{ event.eventLabel }}</span>
              </td>
              <td class="account">{{ event.accountLabel }}</td>
              <td class="details">{{ event.details }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import api from "@/services/api"

const loading = ref(true)
const loadError = ref("")
const companyId = ref("")
const companyName = ref("")
const auditEvents = ref([])
const employeeEmails = ref(new Set())
const employeeNames = ref(new Set())
const jobTitles = ref(new Set())
let refreshTimer = null
let bootstrapDone = false

const events = computed(() => {
  return auditEvents.value
    .filter((item) => item.timestampMs > 0)
    .sort((a, b) => b.timestampMs - a.timestampMs)
    .slice(0, 250)
})

const activeAccountsCount = computed(() => {
  const seen = new Set()
  events.value.forEach((entry) => {
    const account = String(entry.accountLabel || "").trim()
    if (account) seen.add(account)
  })
  return seen.size
})

const roleTaggedEventsCount = computed(() => {
  return events.value.filter((entry) => String(entry.roleLabel || "").trim()).length
})

const adminEventsCount = computed(() => {
  return events.value.filter((entry) => String(entry.roleLabel || "").toLowerCase() === "company_admin").length
})

onMounted(async () => {
  await bootstrapScope()
  if (!companyId.value) {
    loading.value = false
    return
  }

  await loadCompanyScopeData()
  await loadCompanyLogs()
  bootstrapDone = true

  refreshTimer = window.setInterval(() => {
    void loadCompanyLogs(false)
  }, 10000)
})

onBeforeUnmount(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
})

async function bootstrapScope() {
  companyId.value = String(localStorage.getItem("companyId") || "").trim()
  companyName.value = String(localStorage.getItem("companyName") || "").trim()

  const uid = String(
    localStorage.getItem("userUid") ||
    localStorage.getItem("uid") ||
    ""
  ).trim()
  const role = String(localStorage.getItem("userRole") || "").trim().toLowerCase().replace(/[\s-]+/g, "_")

  if (role !== "company_admin") {
    loadError.value = "Access denied. Company admin only."
    return
  }

  if (companyId.value) return
  if (!uid) {
    loadError.value = "Session missing. Please login again."
    return
  }

  try {
    const res = await api.get(`/users/${uid}`)
    const user = res?.data || {}
    companyId.value = String(user?.companyId || "").trim()
    companyName.value = String(user?.companyName || "").trim()
    if (!companyId.value) {
      loadError.value = "No company assignment found."
    }
  } catch {
    if (!companyId.value) {
      loadError.value = "Unable to read company scope."
    }
  }
}

async function loadCompanyScopeData() {
  try {
    const [usersRes, jobsRes] = await Promise.all([
      api.get("/users", { params: { companyId: companyId.value } }),
      api.get("/jobs", { params: { companyId: companyId.value } }),
    ])

    const users = Array.isArray(usersRes?.data) ? usersRes.data : []
    const jobs = Array.isArray(jobsRes?.data) ? jobsRes.data : []

    const allowedRoles = new Set(["company_admin", "hr", "finance", "operation", "employer"])
    const nextEmails = new Set()
    const nextNames = new Set()
    users.forEach((u) => {
      const role = String(u?.role || "").trim().toLowerCase()
      if (!allowedRoles.has(role)) return
      const email = String(u?.email || "").trim().toLowerCase()
      const name = String(u?.name || u?.username || "").trim().toLowerCase()
      if (email) nextEmails.add(email)
      if (name) nextNames.add(name)
    })

    const nextTitles = new Set()
    jobs.forEach((j) => {
      const title = String(j?.title || j?.jobTitle || "").trim().toLowerCase()
      if (title) nextTitles.add(title)
    })

    employeeEmails.value = nextEmails
    employeeNames.value = nextNames
    jobTitles.value = nextTitles
  } catch {
    employeeEmails.value = new Set()
    employeeNames.value = new Set()
    jobTitles.value = new Set()
  }
}

async function loadCompanyLogs(showLoading = true) {
  if (showLoading) loading.value = true
  try {
    const res = await api.get("/logs")
    const rows = Array.isArray(res?.data) ? res.data : []
    auditEvents.value = rows
      .filter((row) => belongsToCompany(row))
      .map((row, idx) => mapLogRow(row, idx))
      .filter((item) => item.timestampMs > 0)
    loadError.value = ""
  } catch (err) {
    console.error(err)
    if (!auditEvents.value.length) {
      loadError.value = err?.response?.data?.message || "Cannot load logs."
    }
  } finally {
    if (showLoading) loading.value = false
  }
}

function belongsToCompany(row) {
  const source = String(row?.source || "").trim().toLowerCase()
  const account = String(row?.account || "").trim().toLowerCase()
  const details = String(row?.details || "").trim().toLowerCase()
  const eventType = String(row?.eventType || "").trim().toLowerCase()
  const blob = `${source} ${account} ${details} ${eventType}`

  if (companyId.value && blob.includes(String(companyId.value).toLowerCase())) return true
  if (companyName.value && blob.includes(String(companyName.value).toLowerCase())) return true
  if (employeeEmails.value.has(account)) return true
  if (employeeNames.value.has(account)) return true

  if (source.includes("job")) {
    for (const title of jobTitles.value) {
      if (title && details.includes(title)) return true
    }
  }

  return false
}

function mapLogRow(row, index) {
  const sourceClass = normalizeSource(row?.source)
  const sourceLabelMap = {
    employees: "Users",
    jobs: "Jobs",
    applications: "Applications",
    admin: "Company Logs",
  }
  const role = inferRole(row)
  const details = String(row?.details || "").trim()

  return {
    id: String(row?.id || `company-log-${index}`),
    timestampMs: toMillis(row?.createdAt || row?.updatedAt),
    sourceLabel: sourceLabelMap[sourceClass] || "Company Logs",
    sourceClass,
    eventLabel: toEventLabel(row?.eventType),
    roleLabel: role,
    accountLabel: String(row?.account || "Unknown"),
    details: details || "-",
  }
}

function normalizeSource(value) {
  const source = String(value || "").trim().toLowerCase()
  if (source.includes("user")) return "employees"
  if (source.includes("job")) return "jobs"
  if (source.includes("application")) return "applications"
  return "admin"
}

function inferRole(row) {
  const blob = `${String(row?.account || "").toLowerCase()} ${String(row?.details || "").toLowerCase()} ${String(row?.eventType || "").toLowerCase()}`
  if (blob.includes("company_admin") || blob.includes("company admin")) return "company_admin"
  if (/\bhr\b/.test(blob) || blob.includes("human resource")) return "hr"
  if (blob.includes("finance")) return "finance"
  if (blob.includes("operation")) return "operation"
  if (blob.includes("employer")) return "employer"
  if (blob.includes("applicant")) return "applicant"
  return ""
}

function toEventLabel(value) {
  const raw = String(value || "").trim()
  if (!raw) return "Action"
  return raw
    .replace(/[_-]+/g, " ")
    .replace(/\s+/g, " ")
    .trim()
    .replace(/\b\w/g, (ch) => ch.toUpperCase())
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

if (bootstrapDone) {
  // no-op, keeps linter calm for top-level guards in some setups
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
  color: #6b7280;
  margin: 8px 0 20px;
}

.summary-grid {
  margin-bottom: 14px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 10px;
}

.summary-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px;
}

.summary-card span {
  color: #64748b;
  font-size: 12px;
}

.summary-card strong {
  display: block;
  margin-top: 4px;
  font-size: 20px;
  color: #0f172a;
}

.logs-card {
  background: #fff;
  border-radius: 10px;
  padding: 18px;
  box-shadow: 0 0 5px rgba(0, 0, 0, 0.05);
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
  color: #0d6efd;
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
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  background: #f8fafc;
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

.badge.source.employees {
  background: #dbeafe;
  color: #1d4ed8;
}

.badge.source.jobs {
  background: #dcfce7;
  color: #166534;
}

.badge.source.applications {
  background: #fff7ed;
  color: #9a3412;
}

.badge.source.admin {
  background: #ede9fe;
  color: #5b21b6;
}

.badge.event {
  background: #e2e8f0;
  color: #334155;
}
</style>
