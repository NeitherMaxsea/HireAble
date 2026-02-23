<template>
  <section class="content">
    <div class="page-header">
      <div>
        <h2>Application Status Tracking</h2>
        <p class="subtitle">
          Track applicant progress and update statuses: Pending, Interviewed, Accepted, Rejected.
        </p>
      </div>
      <button class="refresh-btn" :disabled="loading" @click="loadApplications">
        {{ loading ? "Refreshing..." : "Refresh" }}
      </button>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <p class="label">Total</p>
        <p class="value">{{ totals.total }}</p>
      </div>
      <div class="stat-card pending">
        <p class="label">Pending</p>
        <p class="value">{{ totals.pending }}</p>
      </div>
      <div class="stat-card interviewed">
        <p class="label">Interviewed</p>
        <p class="value">{{ totals.interviewed }}</p>
      </div>
      <div class="stat-card accepted">
        <p class="label">Accepted</p>
        <p class="value">{{ totals.accepted }}</p>
      </div>
      <div class="stat-card rejected">
        <p class="label">Rejected</p>
        <p class="value">{{ totals.rejected }}</p>
      </div>
    </div>

    <div class="filters">
      <input
        v-model.trim="search"
        type="text"
        class="search"
        placeholder="Search by applicant, email, or job title"
      />

      <select v-model="statusFilter" class="status-filter">
        <option value="all">All statuses</option>
        <option value="pending">Pending</option>
        <option value="interviewed">Interviewed</option>
        <option value="accepted">Accepted</option>
        <option value="rejected">Rejected</option>
      </select>
    </div>

    <div class="card">
      <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

      <table v-if="filteredApplicants.length > 0">
        <thead>
          <tr>
            <th>Applicant</th>
            <th>Email</th>
            <th>Job Title</th>
            <th>Applied At</th>
            <th>Status</th>
            <th>Update</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="entry in filteredApplicants" :key="entry.id">
            <td>
              <strong>{{ entry.applicantName }}</strong><br />
              <small>ID: {{ entry.applicantId }}</small>
            </td>
            <td>{{ entry.applicantEmail }}</td>
            <td>{{ entry.jobTitle }}</td>
            <td>{{ entry.appliedAtLabel }}</td>
            <td>
              <span class="status-pill" :class="`status-${entry.status}`">
                {{ toTitle(entry.status) }}
              </span>
            </td>
            <td>
              <select
                class="update-select"
                :value="entry.status"
                :disabled="busyId === entry.id"
                @change="onStatusChange(entry, $event)"
              >
                <option value="pending">Pending</option>
                <option value="interviewed">Interviewed</option>
                <option value="accepted">Accepted</option>
                <option value="rejected">Rejected</option>
              </select>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty">
        {{ loading ? "Loading applications..." : "No applications found." }}
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import api from "@/services/api"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

const applicants = ref([])
const search = ref("")
const statusFilter = ref("all")
const loading = ref(false)
const busyId = ref("")
const errorMessage = ref("")

const allowedStatuses = new Set(["pending", "interviewed", "accepted", "rejected"])

const toTitle = (value) => {
  const text = String(value || "").trim().toLowerCase()
  if (!text) return "Unknown"
  return `${text.charAt(0).toUpperCase()}${text.slice(1)}`
}

const formatDateTime = (value) => {
  if (!value) return "-"
  const date = new Date(String(value))
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleString()
}

const normalizeStatus = (value) => {
  const status = String(value || "pending").trim().toLowerCase()
  if (allowedStatuses.has(status)) return status
  return "pending"
}

const notify = (text, color = "#2563eb") => {
  Toastify({
    text,
    backgroundColor: color,
    duration: 2600,
    gravity: "top",
    position: "right",
    close: true,
  }).showToast()
}

const filteredApplicants = computed(() => {
  const needle = search.value.toLowerCase()

  return applicants.value.filter((entry) => {
    const matchesStatus =
      statusFilter.value === "all" || entry.status === statusFilter.value
    if (!matchesStatus) return false

    if (!needle) return true

    return (
      entry.applicantName.toLowerCase().includes(needle) ||
      entry.applicantEmail.toLowerCase().includes(needle) ||
      entry.jobTitle.toLowerCase().includes(needle)
    )
  })
})

const totals = computed(() => {
  const base = {
    total: applicants.value.length,
    pending: 0,
    interviewed: 0,
    accepted: 0,
    rejected: 0,
  }

  applicants.value.forEach((entry) => {
    if (entry.status in base) base[entry.status] += 1
  })

  return base
})

function resolveListParams() {
  const companyId = String(localStorage.getItem("companyId") || "").trim()
  const postedByUid = String(
    localStorage.getItem("userUid") ||
      localStorage.getItem("uid") ||
      ""
  ).trim()

  if (companyId) return { companyId }
  if (postedByUid) return { postedByUid }
  return {}
}

async function loadApplications() {
  loading.value = true
  errorMessage.value = ""

  try {
    const params = resolveListParams()
    const response = await api.get("/applications", { params })
    const rows = Array.isArray(response?.data) ? response.data : []

    applicants.value = rows
      .map((row) => {
        const status = normalizeStatus(row?.status)
        return {
          id: String(row?.id || ""),
          jobTitle: String(row?.jobTitle || "Untitled Job"),
          applicantId: String(row?.applicantId || "N/A"),
          applicantName: String(row?.applicantName || "Applicant"),
          applicantEmail: String(row?.applicantEmail || "No email"),
          status,
          appliedAt: row?.appliedAt || row?.createdAt || null,
          appliedAtLabel: formatDateTime(row?.appliedAt || row?.createdAt || null),
        }
      })
      .sort((a, b) => new Date(b.appliedAt || 0) - new Date(a.appliedAt || 0))
  } catch (err) {
    console.error(err)
    errorMessage.value = err?.response?.data?.message || "Failed to load applications."
  } finally {
    loading.value = false
  }
}

async function onStatusChange(entry, event) {
  if (!entry?.id) return
  const nextStatus = normalizeStatus(event?.target?.value)
  if (!allowedStatuses.has(nextStatus) || nextStatus === entry.status) return

  busyId.value = entry.id
  try {
    await api.put(`/applications/${entry.id}`, { status: nextStatus })
    applicants.value = applicants.value.map((item) =>
      item.id === entry.id ? { ...item, status: nextStatus } : item
    )
    notify(`Status updated to ${toTitle(nextStatus)}.`, "#16a34a")
  } catch (err) {
    console.error(err)
    notify(err?.response?.data?.message || "Failed to update status.", "#dc2626")
    if (event?.target) event.target.value = entry.status
  } finally {
    busyId.value = ""
  }
}

onMounted(() => {
  void loadApplications()
})
</script>

<style scoped>
.content {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 12px;
}

.subtitle {
  font-size: 13px;
  color: #64748b;
}

.refresh-btn {
  border: none;
  border-radius: 10px;
  padding: 9px 14px;
  background: #2563eb;
  color: #ffffff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.refresh-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 10px;
}

.stat-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
}

.stat-card .label {
  margin: 0;
  font-size: 12px;
  color: #6b7280;
}

.stat-card .value {
  margin: 4px 0 0;
  font-size: 24px;
  font-weight: 700;
  color: #111827;
}

.stat-card.pending {
  border-color: #fcd34d;
}

.stat-card.interviewed {
  border-color: #93c5fd;
}

.stat-card.accepted {
  border-color: #86efac;
}

.stat-card.rejected {
  border-color: #fca5a5;
}

.filters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.search,
.status-filter,
.update-select {
  border: 1px solid #d1d5db;
  border-radius: 10px;
  padding: 9px 12px;
  background: #ffffff;
  font-size: 14px;
}

.search {
  width: min(420px, 100%);
  flex: 1 1 320px;
}

.status-filter {
  min-width: 170px;
}

.update-select {
  min-width: 140px;
}

.card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  border-bottom: 1px solid #e5e7eb;
  text-align: left;
  vertical-align: middle;
  padding: 11px 10px;
  font-size: 14px;
}

th {
  color: #6b7280;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

small {
  color: #64748b;
  font-size: 12px;
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

.error {
  margin: 0 0 10px;
  color: #b91c1c;
  font-size: 13px;
}

.empty {
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  font-style: italic;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .refresh-btn {
    width: 100%;
  }
}
</style>
