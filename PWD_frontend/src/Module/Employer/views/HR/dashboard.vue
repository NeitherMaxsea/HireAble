<template>
  <section class="dashboard-page">
    <div class="dashboard-main">
      <header class="page-header">
        <div>
          <h2>Employer Dashboard</h2>
          <p class="subtitle">Live overview of HR activity for your company.</p>
        </div>
      </header>

      <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

      <div class="stats">
        <article class="stat-card">
          <h4>Total Applicants</h4>
          <p>{{ stats.totalApplicants }}</p>
        </article>

        <article class="stat-card">
          <h4>Active Job Posts</h4>
          <p>{{ stats.activeJobPosts }}</p>
        </article>

        <article class="stat-card">
          <h4>Interviews Scheduled</h4>
          <p>{{ stats.interviewsScheduled }}</p>
        </article>

        <article class="stat-card">
          <h4>Employees</h4>
          <p>{{ stats.employees }}</p>
        </article>
      </div>

      <div class="charts-grid">
        <article class="card chart-card">
          <div class="card-head">
            <h3>Application Trend (Last 7 Days)</h3>
            <span class="chip">Line Chart</span>
          </div>

          <div class="chart-wrap" v-if="trendPoints.length > 0">
            <svg class="trend-svg" viewBox="0 0 720 260" role="img" aria-label="Application trend over last seven days">
              <g class="grid-lines">
                <line x1="56" y1="32" x2="684" y2="32" />
                <line x1="56" y1="84" x2="684" y2="84" />
                <line x1="56" y1="136" x2="684" y2="136" />
                <line x1="56" y1="188" x2="684" y2="188" />
                <line x1="56" y1="240" x2="684" y2="240" />
              </g>

              <path :d="trendAreaPath" class="trend-area" />
              <path :d="trendLinePath" class="trend-line" />

              <g v-for="point in trendPoints" :key="point.key">
                <circle :cx="point.x" :cy="point.y" r="4.8" class="trend-point" />
                <text :x="point.x" y="252" text-anchor="middle" class="axis-label">{{ point.label }}</text>
                <text :x="point.x" :y="point.y - 10" text-anchor="middle" class="point-value">{{ point.count }}</text>
              </g>
            </svg>
          </div>

          <div v-else class="empty">No trend data yet.</div>
        </article>

        <article class="card chart-card">
          <div class="card-head">
            <h3>Application Status Distribution</h3>
            <span class="chip">Bar Chart</span>
          </div>

          <div class="chart-wrap" v-if="statusBars.length > 0">
            <svg class="status-svg" viewBox="0 0 720 260" role="img" aria-label="Application status bar chart">
              <g class="grid-lines">
                <line x1="56" y1="32" x2="684" y2="32" />
                <line x1="56" y1="84" x2="684" y2="84" />
                <line x1="56" y1="136" x2="684" y2="136" />
                <line x1="56" y1="188" x2="684" y2="188" />
                <line x1="56" y1="240" x2="684" y2="240" />
              </g>

              <g v-for="bar in statusBars" :key="bar.key">
                <rect
                  :x="bar.x"
                  :y="bar.y"
                  :width="bar.width"
                  :height="bar.height"
                  :fill="bar.color"
                  rx="8"
                />
                <text :x="bar.x + bar.width / 2" y="252" text-anchor="middle" class="axis-label">{{ bar.label }}</text>
                <text :x="bar.x + bar.width / 2" :y="bar.y - 10" text-anchor="middle" class="point-value">{{ bar.count }}</text>
              </g>
            </svg>
          </div>

          <div v-else class="empty">No status data yet.</div>
        </article>
      </div>

      <div class="card">
        <div class="card-head">
          <h3>Recent Applications</h3>
        </div>

        <table v-if="recentApplications.length > 0">
          <thead>
            <tr>
              <th>Name</th>
              <th>Position</th>
              <th>Status</th>
              <th>Applied At</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="entry in recentApplications" :key="entry.id">
              <td>{{ entry.applicantName }}</td>
              <td>{{ entry.jobTitle }}</td>
              <td>
                <span class="status-pill" :class="`status-${entry.status}`">
                  {{ toTitle(entry.status) }}
                </span>
              </td>
              <td>{{ entry.appliedAtLabel }}</td>
            </tr>
          </tbody>
        </table>

        <div v-else class="empty">
          {{ loading ? "Loading applications..." : "No applications found." }}
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import api from "@/services/api"

const INTERVIEW_STORAGE_KEY = "hrInterviewSchedules"

const loading = ref(false)
const errorMessage = ref("")
const applications = ref([])
const jobs = ref([])
const users = ref([])

let refreshTimer = null

const stats = computed(() => {
  const activeJobPosts = jobs.value.filter((row) => isActiveJob(row)).length
  const interviewsScheduled = countScheduledInterviews()
  const employees = users.value.filter((row) => isEmployeeRole(row?.role)).length

  return {
    totalApplicants: applications.value.length,
    activeJobPosts,
    interviewsScheduled,
    employees,
  }
})

const recentApplications = computed(() =>
  applications.value
    .slice()
    .sort((a, b) => b.appliedAtMillis - a.appliedAtMillis)
    .slice(0, 8)
)

const trendBuckets = computed(() => {
  const labels = []
  const countsMap = new Map()

  for (let offset = 6; offset >= 0; offset -= 1) {
    const day = new Date()
    day.setHours(0, 0, 0, 0)
    day.setDate(day.getDate() - offset)

    const key = day.toISOString().slice(0, 10)
    const label = day.toLocaleDateString("en-US", { month: "short", day: "numeric" })

    labels.push({ key, label })
    countsMap.set(key, 0)
  }

  applications.value.forEach((entry) => {
    const millis = Number(entry.appliedAtMillis || 0)
    if (!millis) return

    const key = new Date(millis).toISOString().slice(0, 10)
    if (countsMap.has(key)) {
      countsMap.set(key, Number(countsMap.get(key) || 0) + 1)
    }
  })

  return labels.map((item) => ({
    ...item,
    count: Number(countsMap.get(item.key) || 0),
  }))
})

const trendMax = computed(() => {
  const maxValue = Math.max(...trendBuckets.value.map((item) => item.count), 0)
  return maxValue > 0 ? maxValue : 1
})

const trendPoints = computed(() => {
  const width = 628
  const left = 56
  const top = 32
  const plotHeight = 208
  const safeDenominator = Math.max(trendBuckets.value.length - 1, 1)

  return trendBuckets.value.map((item, index) => {
    const x = left + (width * index) / safeDenominator
    const y = top + plotHeight - (item.count / trendMax.value) * plotHeight

    return {
      key: item.key,
      label: item.label,
      count: item.count,
      x,
      y,
    }
  })
})

const trendLinePath = computed(() => {
  if (trendPoints.value.length === 0) return ""

  const [first, ...rest] = trendPoints.value
  return [`M ${first.x} ${first.y}`, ...rest.map((point) => `L ${point.x} ${point.y}`)].join(" ")
})

const trendAreaPath = computed(() => {
  if (trendPoints.value.length === 0) return ""

  const [first, ...rest] = trendPoints.value
  const baseY = 240

  return [
    `M ${first.x} ${baseY}`,
    `L ${first.x} ${first.y}`,
    ...rest.map((point) => `L ${point.x} ${point.y}`),
    `L ${trendPoints.value[trendPoints.value.length - 1].x} ${baseY}`,
    "Z",
  ].join(" ")
})

const statusCounts = computed(() => {
  const counts = {
    pending: 0,
    interviewed: 0,
    accepted: 0,
    rejected: 0,
  }

  applications.value.forEach((entry) => {
    if (entry.status in counts) {
      counts[entry.status] += 1
    }
  })

  return counts
})

const statusBars = computed(() => {
  const statusRows = [
    { key: "pending", label: "Pending", color: "#f59e0b" },
    { key: "interviewed", label: "Interviewed", color: "#3b82f6" },
    { key: "accepted", label: "Accepted", color: "#10b981" },
    { key: "rejected", label: "Rejected", color: "#ef4444" },
  ]

  const maxCount = Math.max(...statusRows.map((row) => statusCounts.value[row.key] || 0), 0) || 1
  const left = 88
  const top = 32
  const plotHeight = 208
  const barWidth = 88
  const gap = 62

  return statusRows.map((row, index) => {
    const count = Number(statusCounts.value[row.key] || 0)
    const height = (count / maxCount) * plotHeight
    const x = left + index * (barWidth + gap)
    const y = top + plotHeight - height

    return {
      ...row,
      count,
      x,
      y,
      width: barWidth,
      height,
    }
  })
})

function resolveListParams() {
  const companyId = String(localStorage.getItem("companyId") || "").trim()
  const postedByUid = String(localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim()

  if (companyId) return { companyId }
  if (postedByUid) return { postedByUid }
  return {}
}

function toMillis(value) {
  if (!value) return 0
  if (typeof value === "number" && Number.isFinite(value)) return value

  const text = String(value).trim()
  if (!text) return 0

  const direct = Date.parse(text)
  if (!Number.isNaN(direct)) return direct

  const mysqlLike = Date.parse(text.replace(" ", "T"))
  if (!Number.isNaN(mysqlLike)) return mysqlLike

  return 0
}

function toTitle(value) {
  const text = String(value || "").trim().toLowerCase()
  if (!text) return "Unknown"
  return `${text.charAt(0).toUpperCase()}${text.slice(1)}`
}

function normalizeApplication(row) {
  const status = String(row?.status || "pending").trim().toLowerCase()
  const appliedAtRaw = row?.appliedAt || row?.createdAt || row?.updatedAt
  const appliedAtMillis = toMillis(appliedAtRaw)

  return {
    id: String(row?.id || ""),
    applicantName: String(row?.applicantName || "Applicant"),
    jobTitle: String(row?.jobTitle || "Untitled Job"),
    status,
    appliedAtMillis,
    appliedAtLabel: formatDateTime(appliedAtRaw),
  }
}

function normalizeJob(row) {
  return {
    id: String(row?.id || ""),
    title: String(row?.title || ""),
    status: String(row?.status || "").trim().toLowerCase(),
    financeApprovalStatus: String(row?.financeApprovalStatus || "").trim().toLowerCase(),
  }
}

function isEmployeeRole(role) {
  const value = String(role || "").trim().toLowerCase()
  return value === "hr" || value === "finance" || value === "operation"
}

function isActiveJob(job) {
  const status = String(job?.status || "").trim().toLowerCase()
  const financeApproval = String(job?.financeApprovalStatus || "").trim().toLowerCase()

  if (financeApproval === "rejected") return false
  if (status === "closed" || status === "inactive" || status === "rejected") return false

  return true
}

function formatDateTime(value) {
  const parsed = toMillis(value)
  if (!parsed) return "-"

  return new Date(parsed).toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  })
}

function countScheduledInterviews() {
  try {
    const raw = localStorage.getItem(INTERVIEW_STORAGE_KEY)
    const parsed = JSON.parse(raw || "[]")
    if (!Array.isArray(parsed)) return 0

    const applicationIds = new Set(applications.value.map((row) => row.id))

    return parsed.filter((row) => {
      const status = String(row?.scheduleStatus || "").trim().toLowerCase()
      const applicationId = String(row?.applicationId || "")
      if (status !== "scheduled") return false
      if (applicationIds.size === 0) return true
      return applicationIds.has(applicationId)
    }).length
  } catch {
    return 0
  }
}

async function loadDashboardData() {
  if (loading.value) return

  loading.value = true
  errorMessage.value = ""

  try {
    const params = resolveListParams()
    const [applicationsRes, jobsRes, usersRes] = await Promise.all([
      api.get("/applications", { params }),
      api.get("/jobs", { params }),
      api.get("/users", { params: params.companyId ? { companyId: params.companyId } : {} }),
    ])

    const applicationRows = Array.isArray(applicationsRes?.data) ? applicationsRes.data : []
    const jobRows = Array.isArray(jobsRes?.data) ? jobsRes.data : []
    const userRows = Array.isArray(usersRes?.data) ? usersRes.data : []

    applications.value = applicationRows.map((row) => normalizeApplication(row))
    jobs.value = jobRows.map((row) => normalizeJob(row))
    users.value = userRows
  } catch (error) {
    console.error(error)
    errorMessage.value = error?.response?.data?.message || "Failed to load dashboard data."
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadDashboardData()
  refreshTimer = setInterval(() => {
    void loadDashboardData()
  }, 30000)
})

onBeforeUnmount(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
})
</script>

<style scoped>
.dashboard-page {
  min-height: 100%;
}

.dashboard-main {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.page-header h2 {
  margin: 0;
}

.subtitle {
  margin: 4px 0 0;
  font-size: 13px;
  color: #64748b;
}

.error {
  margin: 0;
  color: #b91c1c;
  font-size: 13px;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 12px;
}

.stat-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px;
}

.stat-card h4 {
  margin: 0;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
}

.stat-card p {
  font-size: 30px;
  margin: 8px 0 0;
  font-weight: 700;
  color: #0f172a;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
}

.chart-card {
  padding-bottom: 10px;
}

.card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.card-head h3 {
  margin: 0;
}

.chip {
  border: 1px solid #cbd5e1;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 11px;
  font-weight: 700;
  color: #334155;
  background: #f8fafc;
}

.chart-wrap {
  margin-top: 10px;
}

.trend-svg,
.status-svg {
  width: 100%;
  height: auto;
  display: block;
}

.grid-lines line {
  stroke: #e2e8f0;
  stroke-width: 1;
}

.trend-area {
  fill: rgba(37, 99, 235, 0.12);
}

.trend-line {
  fill: none;
  stroke: #2563eb;
  stroke-width: 3;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.trend-point {
  fill: #ffffff;
  stroke: #2563eb;
  stroke-width: 3;
}

.axis-label {
  font-size: 12px;
  fill: #64748b;
  font-weight: 600;
}

.point-value {
  font-size: 12px;
  fill: #0f172a;
  font-weight: 700;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 12px;
}

th,
td {
  padding: 10px;
  border-bottom: 1px solid #e5e7eb;
  text-align: left;
  font-size: 14px;
}

th {
  color: #6b7280;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.35px;
}

.status-pill {
  display: inline-flex;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-interviewed {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-accepted {
  background: #dcfce7;
  color: #166534;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.empty {
  min-height: 96px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  font-style: italic;
}

@media (max-width: 1100px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
}
</style>
