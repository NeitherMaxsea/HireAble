<template>
  <section class="finance-page">
    <header class="page-header">
      <div>
        <h2>Finance Job Review</h2>
        <p>Review HR job postings before they go live to applicants.</p>
      </div>

      <button class="btn-secondary" type="button" :disabled="loading" @click="loadJobs">
        {{ loading ? "Refreshing..." : "Refresh" }}
      </button>
    </header>

    <div class="stat-grid">
      <article class="stat-card pending">
        <span>Pending Review</span>
        <strong>{{ pendingCount }}</strong>
      </article>

      <article class="stat-card approved">
        <span>Approved</span>
        <strong>{{ approvedCount }}</strong>
      </article>

      <article class="stat-card rejected">
        <span>Rejected</span>
        <strong>{{ rejectedCount }}</strong>
      </article>

      <article class="stat-card total">
        <span>Total Jobs</span>
        <strong>{{ jobs.length }}</strong>
      </article>
    </div>

    <div class="toolbar">
      <label for="approvalFilter">Filter</label>
      <select id="approvalFilter" v-model="statusFilter">
        <option value="all">All</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>
    </div>

    <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Job</th>
            <th>Company</th>
            <th>Posted By</th>
            <th>Posted Date</th>
            <th>HR Status</th>
            <th>Finance</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>

        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="center">Loading jobs...</td>
          </tr>

          <tr v-else-if="filteredJobs.length === 0">
            <td colspan="7" class="center">No job postings found for this filter.</td>
          </tr>

          <tr v-for="job in filteredJobs" :key="job.id">
            <td>
              <strong>{{ job.title || "Untitled role" }}</strong>
              <small>{{ job.type || "Type not set" }}</small>
            </td>
            <td>{{ job.companyName || "N/A" }}</td>
            <td>{{ job.postedByName || job.postedByEmail || "N/A" }}</td>
            <td>{{ formatDate(job.createdAt) }}</td>
            <td>{{ formatLabel(job.status || "pending_finance_review") }}</td>
            <td>
              <span class="badge" :class="approvalClass(job.financeApprovalStatus)">
                {{ approvalLabel(job.financeApprovalStatus) }}
              </span>
            </td>
            <td class="actions">
              <button class="btn-primary" type="button" @click="openApproval(job.id)">
                {{ job.financeApprovalStatus === "pending" ? "Review" : "View" }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { useRouter } from "vue-router"
import api from "@/services/api"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

const router = useRouter()

const jobs = ref([])
const loading = ref(false)
const errorMessage = ref("")
const statusFilter = ref("all")

let pollTimer = null

function toastError(text) {
  Toastify({ text, backgroundColor: "#dc2626" }).showToast()
}

function inferApproval(job) {
  const value = String(job?.financeApprovalStatus || "").trim().toLowerCase()
  if (value === "approved" || value === "rejected" || value === "pending") {
    return value
  }

  const status = String(job?.status || "").trim().toLowerCase()
  if (status === "open" || status === "closed") return "approved"
  if (status === "finance_rejected") return "rejected"
  return "pending"
}

function normalizeJob(job) {
  return {
    id: String(job?.id || ""),
    title: String(job?.title || ""),
    type: String(job?.type || ""),
    companyName: String(job?.companyName || ""),
    postedByName: String(job?.postedByName || ""),
    postedByEmail: String(job?.postedByEmail || ""),
    status: String(job?.status || "pending_finance_review"),
    financeApprovalStatus: inferApproval(job),
    createdAt: job?.createdAt || "",
  }
}

function formatDate(value) {
  if (!value) return "N/A"
  const parsed = Date.parse(String(value))
  if (Number.isNaN(parsed)) return "N/A"
  return new Date(parsed).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  })
}

function formatLabel(value) {
  return String(value || "")
    .replace(/_/g, " ")
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

function approvalLabel(value) {
  if (value === "approved") return "Approved"
  if (value === "rejected") return "Rejected"
  return "Pending"
}

function approvalClass(value) {
  if (value === "approved") return "approved"
  if (value === "rejected") return "rejected"
  return "pending"
}

const filteredJobs = computed(() => {
  if (statusFilter.value === "all") return jobs.value
  return jobs.value.filter((job) => job.financeApprovalStatus === statusFilter.value)
})

const pendingCount = computed(() => jobs.value.filter((job) => job.financeApprovalStatus === "pending").length)
const approvedCount = computed(() => jobs.value.filter((job) => job.financeApprovalStatus === "approved").length)
const rejectedCount = computed(() => jobs.value.filter((job) => job.financeApprovalStatus === "rejected").length)

async function loadJobs() {
  if (loading.value) return

  loading.value = true
  errorMessage.value = ""

  try {
    const params = {}
    const companyId = String(localStorage.getItem("companyId") || "").trim()
    if (companyId) {
      params.companyId = companyId
    }

    const response = await api.get("/jobs", { params })
    const rows = Array.isArray(response.data) ? response.data : []
    jobs.value = rows
      .map((job) => normalizeJob(job))
      .sort((a, b) => Date.parse(String(b.createdAt || "")) - Date.parse(String(a.createdAt || "")))
  } catch (error) {
    console.error(error)
    errorMessage.value = error?.response?.data?.message || "Failed to load jobs for finance review."
    toastError(errorMessage.value)
  } finally {
    loading.value = false
  }
}

function openApproval(jobId) {
  if (!jobId) return
  router.push(`/employer/finance/job-approval/${jobId}`)
}

onMounted(() => {
  void loadJobs()
  pollTimer = setInterval(() => {
    void loadJobs()
  }, 30000)
})

onBeforeUnmount(() => {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
})
</script>

<style scoped>
.finance-page {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.page-header h2 {
  margin: 0;
  color: #0f172a;
}

.page-header p {
  margin: 4px 0 0;
  color: #475569;
  font-size: 14px;
}

.btn-secondary,
.btn-primary {
  border: none;
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.btn-secondary {
  background: #e2e8f0;
  color: #0f172a;
}

.btn-primary {
  background: #2563eb;
  color: #ffffff;
}

.btn-secondary:disabled,
.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.stat-card {
  border-radius: 12px;
  padding: 14px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
}

.stat-card span {
  color: #64748b;
  font-size: 12px;
}

.stat-card strong {
  display: block;
  margin-top: 4px;
  font-size: 24px;
  color: #0f172a;
}

.stat-card.pending {
  border-left: 4px solid #f59e0b;
}

.stat-card.approved {
  border-left: 4px solid #16a34a;
}

.stat-card.rejected {
  border-left: 4px solid #dc2626;
}

.stat-card.total {
  border-left: 4px solid #2563eb;
}

.toolbar {
  display: flex;
  align-items: center;
  gap: 8px;
}

.toolbar label {
  font-size: 13px;
  color: #334155;
}

.toolbar select {
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 8px 10px;
}

.error {
  color: #b91c1c;
  font-size: 13px;
  margin: 0;
}

.table-wrap {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 780px;
}

th,
td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: top;
}

th {
  font-size: 12px;
  color: #64748b;
}

td strong {
  display: block;
  color: #0f172a;
}

td small {
  display: block;
  color: #64748b;
  margin-top: 2px;
}

.center {
  text-align: center;
  color: #64748b;
}

.badge {
  display: inline-flex;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
}

.badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.badge.approved {
  background: #dcfce7;
  color: #166534;
}

.badge.rejected {
  background: #fee2e2;
  color: #991b1b;
}

.actions {
  text-align: right;
}

@media (max-width: 720px) {
  .page-header {
    flex-direction: column;
  }
}
</style>
