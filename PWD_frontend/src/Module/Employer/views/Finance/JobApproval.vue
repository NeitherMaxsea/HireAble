<template>
  <section class="approval-page">
    <header class="page-header">
      <button class="btn-link" type="button" @click="goBack">Back to Finance Dashboard</button>
      <h2>Job Approval Review</h2>
    </header>

    <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

    <div v-if="loading" class="card muted">Loading job details...</div>

    <div v-else-if="job" class="grid">
      <article class="card">
        <h3>{{ job.title || "Untitled role" }}</h3>
        <p class="summary">{{ job.description || "No description provided." }}</p>

        <dl class="details">
          <div>
            <dt>Company</dt>
            <dd>{{ job.companyName || "N/A" }}</dd>
          </div>
          <div>
            <dt>Location</dt>
            <dd>{{ job.location || "N/A" }}</dd>
          </div>
          <div>
            <dt>Type</dt>
            <dd>{{ job.type || "N/A" }}</dd>
          </div>
          <div>
            <dt>Salary</dt>
            <dd>{{ job.salary || "Not specified" }}</dd>
          </div>
          <div>
            <dt>Posted By</dt>
            <dd>{{ job.postedByName || job.postedByEmail || "N/A" }}</dd>
          </div>
          <div>
            <dt>Submitted</dt>
            <dd>{{ formatDate(job.createdAt) }}</dd>
          </div>
          <div>
            <dt>Current Finance Status</dt>
            <dd>
              <span class="badge" :class="approvalClass(job.financeApprovalStatus)">
                {{ approvalLabel(job.financeApprovalStatus) }}
              </span>
            </dd>
          </div>
          <div>
            <dt>Current HR Status</dt>
            <dd>{{ formatLabel(job.status || "pending_finance_review") }}</dd>
          </div>
        </dl>

        <div v-if="job.qualifications" class="detail-block">
          <h4>Qualifications</h4>
          <p>{{ job.qualifications }}</p>
        </div>

        <div v-if="job.financeApprovalNote || job.financeRejectionReason" class="detail-block">
          <h4>Latest Finance Note</h4>
          <p>{{ job.financeApprovalNote || job.financeRejectionReason }}</p>
        </div>
      </article>

      <article class="card">
        <h3>Finance Decision</h3>
        <p class="muted">Add a review note, then approve or reject this posting.</p>

        <label for="note">Review note</label>
        <textarea
          id="note"
          v-model="reviewNote"
          rows="6"
          placeholder="Add your finance review notes here"
        ></textarea>

        <div class="actions">
          <button class="btn-reject" type="button" :disabled="saving" @click="rejectJob">
            {{ saving ? "Saving..." : "Reject" }}
          </button>
          <button class="btn-approve" type="button" :disabled="saving" @click="approveJob">
            {{ saving ? "Saving..." : "Approve" }}
          </button>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import api from "@/services/api"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

const route = useRoute()
const router = useRouter()

const job = ref(null)
const reviewNote = ref("")
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref("")

const jobId = computed(() => String(route.params.id || "").trim())

function toastSuccess(text) {
  Toastify({ text, backgroundColor: "#16a34a" }).showToast()
}

function toastError(text) {
  Toastify({ text, backgroundColor: "#dc2626" }).showToast()
}

function inferApproval(raw) {
  const value = String(raw?.financeApprovalStatus || "").trim().toLowerCase()
  if (value === "approved" || value === "rejected" || value === "pending") {
    return value
  }

  const status = String(raw?.status || "").trim().toLowerCase()
  if (status === "open" || status === "closed") return "approved"
  if (status === "finance_rejected") return "rejected"
  return "pending"
}

function normalizeJob(raw) {
  return {
    id: String(raw?.id || ""),
    title: String(raw?.title || ""),
    description: String(raw?.description || ""),
    qualifications: String(raw?.qualifications || ""),
    location: String(raw?.location || ""),
    type: String(raw?.type || ""),
    salary: String(raw?.salary || ""),
    companyName: String(raw?.companyName || ""),
    postedByName: String(raw?.postedByName || ""),
    postedByEmail: String(raw?.postedByEmail || ""),
    status: String(raw?.status || "pending_finance_review"),
    financeApprovalStatus: inferApproval(raw),
    financeApprovalNote: String(raw?.financeApprovalNote || ""),
    financeRejectionReason: String(raw?.financeRejectionReason || ""),
    createdAt: raw?.createdAt || "",
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

async function loadJob() {
  if (!jobId.value) {
    errorMessage.value = "Missing job reference."
    return
  }

  loading.value = true
  errorMessage.value = ""

  try {
    const response = await api.get(`/jobs/${jobId.value}`)
    job.value = normalizeJob(response.data || {})
    reviewNote.value = job.value.financeApprovalNote || job.value.financeRejectionReason || ""
  } catch (error) {
    console.error(error)
    errorMessage.value = error?.response?.data?.message || "Failed to load job details."
    toastError(errorMessage.value)
  } finally {
    loading.value = false
  }
}

function buildReviewerMeta() {
  return {
    financeReviewedByUid: String(localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim(),
    financeReviewedByName: String(localStorage.getItem("userName") || "Finance Reviewer").trim(),
    financeReviewedByEmail: String(localStorage.getItem("userEmail") || "").trim(),
  }
}

async function submitDecision(decision) {
  if (!job.value?.id || saving.value) return

  saving.value = true

  try {
    const now = new Date().toISOString()
    const note = reviewNote.value.trim()

    const payload = {
      financeApprovalStatus: decision,
      financeApprovalNote: note,
      financeRejectionReason: decision === "rejected" ? note : "",
      financeReviewedAt: now,
      status: decision === "approved" ? "open" : "finance_rejected",
      publishedAt: decision === "approved" ? now : "",
      ...buildReviewerMeta(),
    }

    await api.put(`/jobs/${job.value.id}`, payload)

    toastSuccess(
      decision === "approved"
        ? "Job approved and published."
        : "Job rejected and returned to HR."
    )

    await loadJob()
  } catch (error) {
    console.error(error)
    toastError(error?.response?.data?.message || "Failed to submit finance decision.")
  } finally {
    saving.value = false
  }
}

async function approveJob() {
  await submitDecision("approved")
}

async function rejectJob() {
  if (!reviewNote.value.trim()) {
    toastError("Please provide a rejection reason.")
    return
  }
  await submitDecision("rejected")
}

function goBack() {
  router.push("/employer/finance/dashboard")
}

onMounted(() => {
  void loadJob()
})
</script>

<style scoped>
.approval-page {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.page-header {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.page-header h2 {
  margin: 0;
}

.btn-link {
  width: fit-content;
  border: none;
  background: none;
  color: #2563eb;
  padding: 0;
  cursor: pointer;
  font-weight: 600;
}

.grid {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 16px;
}

.card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
}

.card h3 {
  margin: 0 0 10px;
}

.summary {
  margin: 0 0 14px;
  color: #334155;
}

.details {
  margin: 0;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.details div {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px;
}

.details dt {
  font-size: 12px;
  color: #64748b;
  margin-bottom: 4px;
}

.details dd {
  margin: 0;
  color: #0f172a;
}

.detail-block {
  margin-top: 14px;
}

.detail-block h4 {
  margin: 0 0 6px;
  font-size: 13px;
  color: #475569;
}

.detail-block p {
  margin: 0;
  color: #1e293b;
}

label {
  display: block;
  font-size: 13px;
  color: #334155;
  margin-bottom: 6px;
}

textarea {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 10px;
  resize: vertical;
  font-family: inherit;
}

textarea:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 14px;
}

.btn-approve,
.btn-reject {
  border: none;
  border-radius: 10px;
  padding: 10px 14px;
  font-weight: 600;
  cursor: pointer;
}

.btn-approve {
  background: #16a34a;
  color: #ffffff;
}

.btn-reject {
  background: #dc2626;
  color: #ffffff;
}

.btn-approve:disabled,
.btn-reject:disabled {
  opacity: 0.7;
  cursor: not-allowed;
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

.muted {
  color: #64748b;
}

.error {
  color: #b91c1c;
  margin: 0;
}

@media (max-width: 1024px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
