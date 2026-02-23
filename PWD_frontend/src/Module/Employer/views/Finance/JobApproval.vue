<template>
  <section class="approval-page">
    <div class="page-header">
      <div>
        <h2>Job Approval Review</h2>
        <p class="subtitle">Review pending jobs and decide to approve or reject.</p>
      </div>
      <button class="btn-link" type="button" @click="goBack">Back to Finance Dashboard</button>
    </div>

    <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
    <div v-if="loading" class="card muted">Loading pending jobs...</div>

    <template v-else>
      <div v-if="pendingJobs.length === 0" class="empty">
        No pending jobs available for approval.
      </div>

      <div v-else class="job-grid">
        <div
          v-for="entry in pendingJobs"
          :key="entry.id"
          class="job-card"
        >
          <div class="job-top">
            <div class="location-map">
              <iframe
                v-if="entry.location"
                :src="getMapUrl(entry)"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
              ></iframe>
              <div v-else class="map-empty">No location</div>
              <span class="location-label">
                {{ entry.location || "Not specified" }}
              </span>
            </div>

            <span class="badge pending">
              {{ approvalLabel(entry.financeApprovalStatus) }}
            </span>
          </div>

          <div class="job-body">
            <h3>{{ entry.title || "Untitled role" }}</h3>
            <p>{{ entry.description || "No description provided." }}</p>

            <div class="job-meta">
              <span>Company: {{ entry.companyName || "N/A" }}</span>
              <span>Type: {{ entry.type || "Open" }}</span>
              <span>Salary: {{ entry.salary || "Negotiable" }}</span>
            </div>

            <div v-if="entry.photos.length" class="photo-row">
              <img
                v-for="(photo, index) in entry.photos"
                :key="`${entry.id}-${index}`"
                :src="photo"
                :alt="`Job photo ${index + 1}`"
              >
            </div>

            <div class="actions">
              <button class="view" type="button" :disabled="savingJobId === entry.id" @click="openView(entry)">
                View
              </button>
              <button class="close" type="button" :disabled="savingJobId === entry.id" @click="rejectJob(entry)">
                {{ savingJobId === entry.id ? "Saving..." : "Reject" }}
              </button>
              <button class="reopen" type="button" :disabled="savingJobId === entry.id" @click="approveJob(entry)">
                {{ savingJobId === entry.id ? "Saving..." : "Approve" }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <transition name="modal-fade">
      <div v-if="showViewModal && activeJob" class="modal-overlay">
        <div class="modal view-modal">
          <div class="view-header">
            <div>
              <h3>{{ activeJob.title || "Job Details" }}</h3>
              <p class="subtitle">Review full job information</p>
            </div>
            <button class="close-x" @click="showViewModal = false">x</button>
          </div>

          <div class="view-body">
            <div class="detail-grid">
              <div>
                <h4>Company</h4>
                <p>{{ activeJob.companyName || "N/A" }}</p>
              </div>
              <div>
                <h4>Location</h4>
                <p>{{ activeJob.location || "Not specified" }}</p>
              </div>
              <div>
                <h4>Exact Address</h4>
                <p>{{ activeJob.exactAddress || "Not specified" }}</p>
              </div>
              <div>
                <h4>Type</h4>
                <p>{{ activeJob.type || "Open" }}</p>
              </div>
              <div>
                <h4>Submitted</h4>
                <p>{{ formatDate(activeJob.createdAt) }}</p>
              </div>
            </div>

            <div class="detail-block">
              <h4>Description</h4>
              <p>{{ activeJob.description || "No description provided." }}</p>
            </div>

            <div class="detail-block">
              <h4>Map</h4>
              <iframe
                v-if="activeJob.location"
                :src="getMapUrl(activeJob)"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
              ></iframe>
              <p v-else class="map-empty">No location provided</p>
            </div>

            <div class="detail-block">
              <h4>Photos</h4>
              <div class="photo-grid">
                <img
                  v-for="(photo, index) in activeJob.photos"
                  :key="`modal-${activeJob.id}-${index}`"
                  :src="photo"
                  :alt="`Job photo ${index + 1}`"
                >
                <div v-if="!activeJob.photos.length" class="photo-placeholder">No photo</div>
              </div>
            </div>

            <div class="detail-block">
              <h4>Review Note</h4>
              <textarea
                v-model="reviewNotes[activeJob.id]"
                rows="3"
                class="note-input"
                placeholder="Add review note (required for reject)"
              ></textarea>
            </div>
          </div>

          <div class="modal-actions">
            <button class="close" type="button" :disabled="savingJobId === activeJob.id" @click="rejectJob(activeJob)">
              {{ savingJobId === activeJob.id ? "Saving..." : "Reject" }}
            </button>
            <button class="reopen" type="button" :disabled="savingJobId === activeJob.id" @click="approveJob(activeJob)">
              {{ savingJobId === activeJob.id ? "Saving..." : "Approve" }}
            </button>
            <button class="cancel" type="button" @click="showViewModal = false">Close</button>
          </div>
        </div>
      </div>
    </transition>
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

const jobs = ref([])
const loading = ref(false)
const savingJobId = ref("")
const errorMessage = ref("")
const reviewNotes = ref({})
const showViewModal = ref(false)
const activeJobId = ref("")

const routeJobId = computed(() => String(route.params.id || route.query.id || "").trim())

const pendingJobs = computed(() =>
  jobs.value.filter((entry) => inferApproval(entry) === "pending")
)

const activeJob = computed(() =>
  pendingJobs.value.find((entry) => entry.id === activeJobId.value) || null
)

function toastSuccess(text) {
  Toastify({ text, backgroundColor: "#16a34a" }).showToast()
}

function toastError(text) {
  Toastify({ text, backgroundColor: "#dc2626" }).showToast()
}

function extractJobs(payload) {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  return []
}

function inferApproval(raw) {
  const value = String(raw?.financeApprovalStatus || "").trim().toLowerCase()
  if (value === "approved" || value === "rejected" || value === "pending") return value

  const status = String(raw?.status || "").trim().toLowerCase()
  if (status === "open" || status === "closed") return "approved"
  if (status === "finance_rejected") return "rejected"
  return "pending"
}

function toArray(value) {
  if (Array.isArray(value)) return value
  if (typeof value === "string") {
    const trimmed = value.trim()
    if (!trimmed) return []
    try {
      const parsed = JSON.parse(trimmed)
      if (Array.isArray(parsed)) return parsed
    } catch {
      return [trimmed]
    }
  }
  return []
}

function collectPhotos(raw) {
  const fromArray = toArray(raw?.images).map((item) => String(item || "").trim()).filter(Boolean)
  const fromSingles = [raw?.imageUrl, raw?.image_url, raw?.imageUrl2, raw?.image_url2]
    .map((item) => String(item || "").trim())
    .filter(Boolean)
  return Array.from(new Set([...fromArray, ...fromSingles])).slice(0, 2)
}

function normalizeJob(raw) {
  return {
    id: String(raw?.id || ""),
    title: String(raw?.title || ""),
    description: String(raw?.description || ""),
    location: String(raw?.location || ""),
    exactAddress: String(raw?.exactAddress || ""),
    type: String(raw?.type || ""),
    salary: String(raw?.salary || ""),
    companyName: String(raw?.companyName || ""),
    status: String(raw?.status || "pending_finance_review"),
    financeApprovalStatus: inferApproval(raw),
    financeApprovalNote: String(raw?.financeApprovalNote || ""),
    financeRejectionReason: String(raw?.financeRejectionReason || ""),
    createdAt: raw?.createdAt || "",
    photos: collectPhotos(raw),
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

function approvalLabel(value) {
  if (value === "approved") return "Approved"
  if (value === "rejected") return "Rejected"
  return "Pending"
}

function resolveApprovalRouteWithId(id) {
  return `/employer/finance/job-approval/${id}`
}

function getMapUrl(job) {
  const location = String(job?.location || "").trim()
  if (!location) return ""
  const exactAddress = String(job?.exactAddress || "").trim()
  const query = [exactAddress, location, "Dasmarinas, Cavite"].filter(Boolean).join(", ")
  return `https://www.google.com/maps?q=${encodeURIComponent(query)}&output=embed`
}

async function loadJobs() {
  loading.value = true
  errorMessage.value = ""

  try {
    const companyId = String(localStorage.getItem("companyId") || "").trim()
    const params = { financeApprovalStatus: "pending" }
    if (companyId) params.companyId = companyId

    const response = await api.get("/jobs", { params })
    jobs.value = extractJobs(response.data).map(normalizeJob).filter((entry) => entry.id)

    const defaultId = routeJobId.value && pendingJobs.value.some((entry) => entry.id === routeJobId.value)
      ? routeJobId.value
      : (pendingJobs.value[0]?.id || "")

    activeJobId.value = defaultId
    for (const entry of pendingJobs.value) {
      if (!(entry.id in reviewNotes.value)) {
        reviewNotes.value[entry.id] = entry.financeApprovalNote || entry.financeRejectionReason || ""
      }
    }

    if (defaultId && defaultId !== routeJobId.value) {
      await router.replace(resolveApprovalRouteWithId(defaultId))
    }
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

function openView(entry) {
  activeJobId.value = entry.id
  if (!(entry.id in reviewNotes.value)) {
    reviewNotes.value[entry.id] = ""
  }
  showViewModal.value = true
  void router.replace(resolveApprovalRouteWithId(entry.id))
}

async function submitDecision(entry, decision) {
  if (!entry?.id || savingJobId.value) return

  const note = String(reviewNotes.value[entry.id] || "").trim()
  if (decision === "rejected" && !note) {
    toastError("Please provide a rejection reason.")
    return
  }

  savingJobId.value = entry.id
  try {
    const now = new Date().toISOString()
    await api.put(`/jobs/${entry.id}`, {
      financeApprovalStatus: decision,
      financeApprovalNote: note,
      financeRejectionReason: decision === "rejected" ? note : "",
      financeReviewedAt: now,
      status: decision === "approved" ? "open" : "finance_rejected",
      publishedAt: decision === "approved" ? now : "",
      ...buildReviewerMeta(),
    })

    toastSuccess(
      decision === "approved"
        ? "Job approved and published."
        : "Job rejected and returned to HR."
    )

    if (showViewModal.value && activeJobId.value === entry.id) {
      showViewModal.value = false
    }
    await loadJobs()
  } catch (error) {
    console.error(error)
    toastError(error?.response?.data?.message || "Failed to submit finance decision.")
  } finally {
    savingJobId.value = ""
  }
}

async function approveJob(entry) {
  await submitDecision(entry, "approved")
}

async function rejectJob(entry) {
  await submitDecision(entry, "rejected")
}

function goBack() {
  router.push("/employer/finance/dashboard")
}

onMounted(() => {
  void loadJobs()
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
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
  flex-wrap: wrap;
}

.subtitle {
  margin: 4px 0 0;
  color: #6b7280;
}

.btn-link {
  border: none;
  background: none;
  color: #2563eb;
  padding: 0;
  cursor: pointer;
  font-weight: 600;
}

.empty {
  background: #fff;
  border: 1px dashed #cbd5e1;
  border-radius: 12px;
  padding: 18px;
  color: #64748b;
}

.job-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 22px;
}

.job-card {
  background: white;
  border-radius: 18px;
  padding: 16px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
  transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
  position: relative;
  overflow: hidden;
  cursor: pointer;
}

.job-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
  border-color: #c7d2fe;
}

.job-top {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: -16px -16px 10px;
  padding: 12px 14px;
  border-bottom: 1px solid #e5e7eb;
  border-radius: 18px 18px 12px 12px;
  position: relative;
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.location-map {
  position: relative;
  width: 100%;
  max-width: 520px;
  height: 180px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  margin: 0 auto;
}

.location-map iframe,
.map-empty {
  width: 100%;
  height: 100%;
  border: none;
}

.map-empty {
  background: #e5e7eb;
  display: grid;
  place-items: center;
  color: #475569;
  font-weight: 600;
}

.location-label {
  position: absolute;
  left: 8px;
  bottom: 6px;
  background: rgba(255, 255, 255, 0.92);
  color: #111827;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 6px;
  border-radius: 8px;
  border: 1px solid rgba(148, 163, 184, 0.4);
  max-width: 90%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.badge {
  position: absolute;
  top: 12px;
  right: 14px;
  padding: 5px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}

.badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.job-body {
  padding: 0;
}

.job-body h3 {
  margin: 8px 0 6px;
  font-size: 18px;
  color: #0f172a;
}

.job-body p {
  margin: 0;
  font-size: 14px;
  color: #475569;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.job-meta {
  display: flex;
  gap: 14px;
  margin: 12px 0 10px;
  color: #475569;
  font-size: 13px;
  flex-wrap: wrap;
}

.photo-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-bottom: 10px;
}

.photo-row img {
  width: 100%;
  height: 80px;
  object-fit: cover;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.note-input {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 8px 10px;
  resize: vertical;
  font-family: inherit;
  margin-bottom: 10px;
}

.note-input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.actions {
  display: flex;
  gap: 8px;
  margin-top: 6px;
  flex-wrap: wrap;
}

.actions button {
  padding: 8px 12px;
  border: none;
  border-radius: 12px;
  font-size: 12px;
  letter-spacing: 0.2px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 6px 14px rgba(15, 23, 42, 0.15);
  transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
}

.actions button:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 20px rgba(15, 23, 42, 0.2);
  filter: brightness(1.03);
}

.actions button:active {
  transform: translateY(0);
  box-shadow: 0 6px 12px rgba(15, 23, 42, 0.18);
}

.view {
  background: #e5e7eb;
  color: #111827;
}

.close {
  background: #dc2626;
  color: #fff;
}

.reopen {
  background: #16a34a;
  color: #fff;
}

.actions button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  display: grid;
  place-items: center;
  z-index: 40;
  padding: 16px;
}

.modal {
  width: min(860px, 100%);
  max-height: 90vh;
  overflow-y: auto;
  background: #fff;
  border-radius: 14px;
  padding: 16px;
}

.view-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
}

.close-x {
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 8px;
  background: #f1f5f9;
  cursor: pointer;
  font-weight: 700;
}

.view-body {
  margin-top: 12px;
  display: grid;
  gap: 12px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 10px;
}

.detail-grid > div,
.detail-block {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px;
}

.detail-grid h4,
.detail-block h4 {
  margin: 0 0 6px;
  color: #475569;
  font-size: 13px;
}

.detail-grid p,
.detail-block p {
  margin: 0;
}

.detail-block iframe {
  width: 100%;
  min-height: 180px;
  border: none;
  border-radius: 10px;
}

.photo-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.photo-grid img {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}

.photo-placeholder {
  background: #e5e7eb;
  border-radius: 10px;
  min-height: 150px;
  display: grid;
  place-items: center;
  color: #64748b;
}

.modal-actions {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.cancel {
  background: #e5e7eb;
  color: #111827;
  border: none;
  border-radius: 10px;
  padding: 9px 12px;
  font-weight: 600;
  cursor: pointer;
}

.error {
  color: #b91c1c;
  margin: 0;
}

.muted {
  color: #64748b;
}

.card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
}
</style>
