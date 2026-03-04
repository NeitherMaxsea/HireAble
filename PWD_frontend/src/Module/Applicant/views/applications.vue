<template>
  <div class="applications-page">
    <section class="table-card">
      <div class="table-head">
        <div>
          <h2>My Applications</h2>
          <p>Track the jobs you applied for and their current status.</p>
        </div>

        <div class="table-controls">
          <div class="search-box">
            <i class="bi bi-search"></i>
            <input v-model.trim="search" type="text" placeholder="Search job title or company..." />
          </div>
          <select v-model="statusFilter">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="reviewed">Reviewed</option>
            <option value="interview">Interview</option>
            <option value="accepted">Accepted</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="panel-loading">
        <div class="spinner"></div>
        <p>Loading your applications...</p>
      </div>

      <div v-else-if="filteredApplications.length === 0" class="empty-state">
        <i class="bi bi-inbox"></i>
        <h3>No applications found</h3>
        <p>
          {{ applications.length === 0
            ? "You have not applied to any job yet."
            : "No records matched your current filter." }}
        </p>
      </div>

      <div v-else class="applications-list">
        <div v-if="manageableApplications.length > 0" class="bulk-actions">
          <button class="ghost-btn" type="button" @click="toggleSelectionMode">
            {{ selectionMode ? "Done" : "Manage Applications" }}
          </button>
          <template v-if="selectionMode">
            <label class="bulk-check">
              <input
                type="checkbox"
                :checked="isAllRejectedSelected"
                @change="toggleSelectAllRejected"
              />
              <span>Select all (rejected/deleted)</span>
            </label>
            <p class="bulk-meta">{{ selectedRejectedIds.length }} selected</p>
            <button
              class="ghost-btn"
              type="button"
              :disabled="selectedRejectedIds.length === 0 || deletingSelected"
              @click="clearRejectedSelection"
            >
              Clear
            </button>
            <button
              class="danger-soft-btn"
              type="button"
              :disabled="selectedRejectedIds.length === 0 || deletingSelected"
              @click="deleteSelectedRejectedApplications"
            >
              {{ deletingSelected ? "Deleting..." : "Delete selected" }}
            </button>
          </template>
        </div>

        <article
          v-for="app in filteredApplications"
          :key="app.id"
          class="application-item"
          :class="{ active: selectedApplication?.id === app.id, deleted: app.isJobDeleted }"
          @click="selectedApplication = app"
        >
          <div class="application-main">
            <div class="title-row">
              <h3>{{ app.jobTitle || "Untitled Job" }}</h3>
              <span class="status-pill" :class="app.statusKey">{{ app.statusLabel }}</span>
            </div>
            <p class="company">{{ app.companyName || "Company" }}</p>
            <div class="meta-row">
              <span><i class="bi bi-geo-alt"></i> {{ app.location || "Location not specified" }}</span>
              <span><i class="bi bi-calendar3"></i> Applied {{ app.appliedAtLabel }}</span>
            </div>
            <p v-if="app.isJobDeleted" class="deleted-note">
              This job post was deleted by the company.
            </p>
            <div v-if="isManageableApplication(app)" class="application-actions">
              <label v-if="selectionMode" class="item-check">
                <input
                  type="checkbox"
                  :checked="isRejectedSelected(app.id)"
                  :disabled="deletingSelected"
                  @click.stop
                  @change="toggleRejectedSelection(app.id)"
                />
                <span>Select</span>
              </label>
            </div>
          </div>
        </article>
      </div>
    </section>

    <section class="details-card">
      <div v-if="!selectedApplication" class="details-empty">
        Select an application to view details.
      </div>

      <template v-else>
        <div class="details-head">
          <div>
            <p class="eyebrow">Application Details</p>
            <h3>{{ selectedApplication.jobTitle || "Untitled Job" }}</h3>
            <p class="sub">{{ selectedApplication.companyName || "Company" }}</p>
          </div>
          <span class="status-pill large" :class="selectedApplication.statusKey">
            {{ selectedApplication.statusLabel }}
          </span>
        </div>

        <div v-if="selectedApplication.isJobDeleted" class="deleted-banner">
          This job post was deleted by the company. Your application remains here as history.
        </div>

        <div class="details-grid">
          <div class="info-block">
            <h4>Application Summary</h4>
            <ul>
              <li><strong>Status:</strong> {{ selectedApplication.statusLabel }}</li>
              <li><strong>Applied At:</strong> {{ selectedApplication.appliedAtLabel }}</li>
              <li><strong>Location:</strong> {{ selectedApplication.location || "Not specified" }}</li>
              <li><strong>Exact Address:</strong> {{ selectedApplication.exactAddress || "Not specified" }}</li>
              <li><strong>Work Type:</strong> {{ selectedApplication.jobType || "Not specified" }}</li>
              <li v-if="selectedApplication.statusKey === 'rejected'">
                <strong>Rejection Reason:</strong>
                {{ selectedApplication.rejectionReason || "No rejection reason provided by HR yet." }}
              </li>
            </ul>
          </div>

          <div class="info-block">
            <h4>Job Information</h4>
            <ul>
              <li><strong>Salary:</strong> {{ selectedApplication.salary || "Negotiable" }}</li>
              <li><strong>Disability Fit:</strong> {{ selectedApplication.disabilityType || "PWD-friendly" }}</li>
              <li><strong>Vacancies:</strong> {{ selectedApplication.vacancies || "-" }}</li>
              <li><strong>Reference:</strong> {{ selectedApplication.jobId || "-" }}</li>
            </ul>
          </div>
        </div>

        <div
          v-if="selectedApplication.statusKey === 'rejected'"
          class="rejection-feedback"
        >
          <h4>Rejection Feedback</h4>
          <p>{{ selectedApplication.rejectionReason || "No rejection reason provided by HR yet." }}</p>
        </div>

        <div class="timeline-block">
          <div class="timeline-head">
            <h4>Status Timeline</h4>
            <p>Application progress updates</p>
          </div>
          <div class="timeline-list">
            <div class="timeline-item done">
              <span class="dot"></span>
              <div>
                <p>Application submitted</p>
                <small>{{ selectedApplication.appliedAtLabel }}</small>
              </div>
            </div>
            <div class="timeline-item" :class="{ done: selectedTimeline.reviewDone, failed: selectedTimeline.isRejected }">
              <span class="dot"></span>
              <div>
                <p>Under review</p>
                <small>{{ selectedTimeline.reviewText }}</small>
              </div>
            </div>
            <div
              class="timeline-item"
              :class="{
                done: selectedTimeline.initialInterviewDone,
                processing: selectedTimeline.initialInterviewProcessing,
                failed: selectedTimeline.isRejected
              }"
            >
              <span class="dot"></span>
              <div>
                <p>Initial Interview</p>
                <small>{{ selectedTimeline.initialInterviewText }}</small>
              </div>
            </div>
            <div
              class="timeline-item"
              :class="{
                done: selectedTimeline.finalInterviewDone,
                processing: selectedTimeline.finalInterviewProcessing,
                failed: selectedTimeline.isRejected
              }"
            >
              <span class="dot"></span>
              <div>
                <p>Final Interview</p>
                <small>{{ selectedTimeline.finalInterviewText }}</small>
              </div>
            </div>
          </div>
        </div>
      </template>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import api from "@/services/api"

const loading = ref(true)
const rawApplications = ref([])
const applications = ref([])
const jobsById = ref({})
const jobsMappingLoaded = ref(false)
const selectedApplication = ref(null)
const search = ref("")
const statusFilter = ref("all")
const interviewSchedules = ref([])
const selectedRejectedIds = ref([])
const deletingSelected = ref(false)
const selectionMode = ref(false)
let scheduleRefreshTimer = null
let applicationsRefreshTimer = null
let loadingWatchdogTimer = null
const APPLICATIONS_API_TIMEOUT_MS = 12000
const LOADING_WATCHDOG_MS = 15000

function resolveApplicantIdentity() {
  return {
    id: String(
      localStorage.getItem("userUid") ||
      localStorage.getItem("uid") ||
      localStorage.getItem("sessionUid") ||
      ""
    ).trim(),
    email: String(localStorage.getItem("userEmail") || "").trim().toLowerCase(),
  }
}

function toMillis(input) {
  if (!input) return 0
  if (typeof input?.toMillis === "function") return input.toMillis()
  if (typeof input?.seconds === "number") return input.seconds * 1000
  if (typeof input === "string") {
    const normalized = input.includes("T") ? input : input.replace(" ", "T")
    const ms = Date.parse(normalized)
    return Number.isFinite(ms) ? ms : 0
  }
  if (input instanceof Date) return input.getTime()
  return 0
}

function formatDateLabel(value) {
  const ms = toMillis(value)
  if (!ms) return "Unknown date"
  try {
    return new Date(ms).toLocaleString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "numeric",
      minute: "2-digit",
    })
  } catch {
    return "Unknown date"
  }
}

function normalizeStatus(rawStatus) {
  const s = String(rawStatus || "pending").trim().toLowerCase()
  if (["accepted", "hired"].includes(s)) return { key: "accepted", label: "Accepted" }
  if (["rejected", "declined"].includes(s)) return { key: "rejected", label: "Rejected" }
  if (["interview", "for interview", "interview_scheduled", "interviewed"].includes(s)) return { key: "interview", label: "Interview" }
  if (["reviewed", "screening", "shortlisted"].includes(s)) return { key: "reviewed", label: "Reviewed" }
  return { key: "pending", label: "Pending" }
}

function normalizeScheduleStatus(rawStatus) {
  return String(rawStatus || "scheduled").trim().toLowerCase()
}

function toTitle(value) {
  const raw = String(value || "").trim().toLowerCase()
  if (!raw) return "Unknown"
  return `${raw.charAt(0).toUpperCase()}${raw.slice(1)}`
}

function startLoadingWatchdog() {
  stopLoadingWatchdog()
  loadingWatchdogTimer = setTimeout(() => {
    loading.value = false
  }, LOADING_WATCHDOG_MS)
}

function stopLoadingWatchdog() {
  if (loadingWatchdogTimer) {
    clearTimeout(loadingWatchdogTimer)
    loadingWatchdogTimer = null
  }
}

function withTimeout(promise, timeoutMs = APPLICATIONS_API_TIMEOUT_MS, message = "Request timed out") {
  let timeoutId = null
  const timeoutPromise = new Promise((_, reject) => {
    timeoutId = setTimeout(() => {
      reject(new Error(message))
    }, timeoutMs)
  })

  return Promise.race([promise, timeoutPromise]).finally(() => {
    if (timeoutId) clearTimeout(timeoutId)
  })
}

async function loadInterviewSchedules(identity) {
  const params = {}
  if (identity?.id) params.applicantId = identity.id
  if (identity?.email) params.applicantEmail = identity.email

  if (!params.applicantId && !params.applicantEmail) {
    interviewSchedules.value = []
    return
  }

  try {
    const res = await withTimeout(
      api.get("/interview-schedules", { params }),
      APPLICATIONS_API_TIMEOUT_MS,
      "Interview schedules request timed out."
    )
    interviewSchedules.value = Array.isArray(res?.data) ? res.data : []
  } catch {
    interviewSchedules.value = []
  }
}

const selectedTimeline = computed(() => {
  const app = selectedApplication.value
  if (!app) {
    return {
      reviewDone: false,
      reviewText: "Waiting for HR review",
      initialInterviewDone: false,
      initialInterviewProcessing: false,
      initialInterviewText: "No initial interview schedule yet",
      finalInterviewDone: false,
      finalInterviewProcessing: false,
      finalInterviewText: "No final interview schedule yet",
    }
  }

  const statusKey = String(app.statusKey || "pending").toLowerCase()
  const isRejected = statusKey === "rejected"
  const reviewDone = statusKey !== "pending"

  const related = interviewSchedules.value.filter(
    (row) => String(row?.applicationId || "").trim() === String(app.id || "").trim()
  )

  const resolveInterviewStage = (type) => {
    const stageRows = related.filter(
      (row) => String(row?.interviewType || "").trim().toLowerCase() === type
    )
    const statuses = stageRows.map((row) => normalizeScheduleStatus(row?.scheduleStatus))
    const hasCompleted = statuses.includes("completed")
    const hasProcessing = statuses.some((status) => ["scheduled", "reschedule_requested"].includes(status))
    const hasCancelledOnly = statuses.length > 0 && statuses.every((status) => status === "cancelled")

    if (isRejected) {
      return {
        done: false,
        processing: false,
        text: "Not applicable due to rejection",
      }
    }
    if (hasCompleted) {
      return {
        done: true,
        processing: false,
        text: `Passed ${type} interview`,
      }
    }
    if (hasProcessing) {
      return {
        done: false,
        processing: true,
        text: `${toTitle(type)} interview is being processed`,
      }
    }
    if (hasCancelledOnly) {
      return {
        done: false,
        processing: false,
        text: `${toTitle(type)} interview was cancelled`,
      }
    }
    return {
      done: false,
      processing: false,
      text: `No ${type} interview schedule yet`,
    }
  }

  const initialInterview = resolveInterviewStage("initial")
  const finalInterview = resolveInterviewStage("final")

  return {
    isRejected,
    reviewDone,
    reviewText: isRejected
      ? "Application rejected by HR"
      : (reviewDone ? "Reviewed by HR" : "Waiting for HR review"),
    initialInterviewDone: initialInterview.done,
    initialInterviewProcessing: initialInterview.processing,
    initialInterviewText: initialInterview.text,
    finalInterviewDone: finalInterview.done,
    finalInterviewProcessing: finalInterview.processing,
    finalInterviewText: finalInterview.text,
  }
})

function rebuildApplications() {
  const rows = rawApplications.value.map((rawDoc) => {
    const raw = rawDoc.raw || {}
    const resolvedRejectionReason = String(
      raw.rejectionReason ||
      raw.rejection_reason ||
      raw.reviewNote ||
      raw.review_note ||
      raw.notes ||
      raw.note ||
      ""
    ).trim()
    const normalized = normalizeStatus(raw.status)
    const jobId = String(raw.jobId || "").trim()
    const linkedJob = jobsById.value[jobId] || {}
    const isDeleted = Boolean(jobId) && jobsMappingLoaded.value && !linkedJob?.id
    const barangay = String(
      raw.location ||
      raw.jobLocation ||
      linkedJob.location ||
      linkedJob.barangay ||
      linkedJob.exactAddress ||
      linkedJob.exact_address ||
      ""
    ).trim()
    const exactAddress = String(
      raw.exactAddress ||
      raw.exact_address ||
      linkedJob.exactAddress ||
      linkedJob.exact_address ||
      ""
    ).trim()

    return {
      id: String(raw.id || rawDoc.id || ""),
      jobId,
      jobTitle: String(raw.jobTitle || raw.title || linkedJob.title || "Untitled Job").trim(),
      companyName: String(raw.companyName || raw.company || linkedJob.companyName || linkedJob.company || linkedJob.department || "").trim(),
      location: barangay,
      exactAddress,
      jobType: String(raw.type || raw.jobType || linkedJob.type || "").trim(),
      salary: String(raw.salary || raw.salaryRange || linkedJob.salary || linkedJob.salaryRange || "").trim(),
      vacancies: String(raw.vacancies || raw.slots || linkedJob.vacancies || linkedJob.slots || "").trim(),
      disabilityType: String(raw.disabilityType || raw.disability || linkedJob.disabilityType || linkedJob.disability || "").trim(),
      isJobDeleted: isDeleted,
      statusKey: normalized.key,
      statusLabel: normalized.label,
      rejectionReason: resolvedRejectionReason,
      appliedAtRaw: raw.appliedAt || raw.createdAt || raw.updatedAt || null,
      appliedAtLabel: formatDateLabel(raw.appliedAt || raw.createdAt || raw.updatedAt || null),
      appliedAtMillis: toMillis(raw.appliedAt || raw.createdAt || raw.updatedAt || null),
    }
  }).sort((a, b) => b.appliedAtMillis - a.appliedAtMillis)

  applications.value = rows
  const validRejectedIds = new Set(
    rows
      .filter((row) => row.statusKey === "rejected" || row.isJobDeleted)
      .map((row) => row.id)
  )
  selectedRejectedIds.value = selectedRejectedIds.value.filter((id) => validRejectedIds.has(id))
  if (!selectedApplication.value || !rows.find((r) => r.id === selectedApplication.value.id)) {
    selectedApplication.value = rows[0] || null
  } else {
    selectedApplication.value = rows.find((r) => r.id === selectedApplication.value.id) || selectedApplication.value
  }
}

async function loadJobsForMapping() {
  try {
    const response = await withTimeout(
      api.get("/jobs"),
      APPLICATIONS_API_TIMEOUT_MS,
      "Jobs mapping request timed out."
    )
    const rows = Array.isArray(response?.data) ? response.data : []
    const next = {}
    rows.forEach((raw, idx) => {
      const id = String(raw?.id || raw?.jobId || idx).trim()
      if (!id) return
      next[id] = raw
    })
    jobsById.value = next
    jobsMappingLoaded.value = true
  } catch {
    jobsMappingLoaded.value = false
  }
}

async function loadApplicationsFromApi(identity) {
  if (loading.value) {
    startLoadingWatchdog()
  }

  if (!identity?.id && !identity?.email) {
    rawApplications.value = []
    applications.value = []
    selectedApplication.value = null
    loading.value = false
    stopLoadingWatchdog()
    return
  }

  const params = {}
  if (identity?.id) params.applicantId = identity.id
  if (identity?.email) params.applicantEmail = identity.email

  try {
    const response = await withTimeout(
      api.get("/applications", { params }),
      APPLICATIONS_API_TIMEOUT_MS,
      "Applications request timed out."
    )
    const rows = Array.isArray(response?.data) ? response.data : []
    rawApplications.value = rows.map((row) => ({
      id: String(row?.id || ""),
      raw: row || {},
    }))
    rebuildApplications()
    // Load job mapping after primary list render to avoid blocking the page.
    void loadJobsForMapping().then(() => {
      rebuildApplications()
    })
  } catch {
    rawApplications.value = []
    applications.value = []
    selectedApplication.value = null
  } finally {
    loading.value = false
    stopLoadingWatchdog()
  }
}

function isManageableApplication(app) {
  return String(app?.statusKey || "").toLowerCase() === "rejected" || Boolean(app?.isJobDeleted)
}

const manageableApplications = computed(() =>
  filteredApplications.value.filter((app) => isManageableApplication(app))
)

const isAllRejectedSelected = computed(() => {
  if (manageableApplications.value.length === 0) return false
  return manageableApplications.value.every((app) => selectedRejectedIds.value.includes(app.id))
})

function isRejectedSelected(applicationId) {
  return selectedRejectedIds.value.includes(String(applicationId || ""))
}

function toggleRejectedSelection(applicationId) {
  const id = String(applicationId || "").trim()
  if (!id) return
  if (selectedRejectedIds.value.includes(id)) {
    selectedRejectedIds.value = selectedRejectedIds.value.filter((item) => item !== id)
  } else {
    selectedRejectedIds.value = [...selectedRejectedIds.value, id]
  }
}

function toggleSelectAllRejected() {
  if (isAllRejectedSelected.value) {
    selectedRejectedIds.value = selectedRejectedIds.value.filter(
      (id) => !manageableApplications.value.some((app) => app.id === id)
    )
    return
  }
  const merge = new Set(selectedRejectedIds.value)
  manageableApplications.value.forEach((app) => merge.add(app.id))
  selectedRejectedIds.value = Array.from(merge)
}

function clearRejectedSelection() {
  selectedRejectedIds.value = []
}

function toggleSelectionMode() {
  selectionMode.value = !selectionMode.value
  if (!selectionMode.value) {
    clearRejectedSelection()
  }
}

async function deleteSelectedRejectedApplications() {
  if (deletingSelected.value) return
  const idsToDelete = selectedRejectedIds.value.filter((id) =>
    applications.value.some((app) => app.id === id && isManageableApplication(app))
  )
  if (!idsToDelete.length) return

  deletingSelected.value = true
  try {
    await Promise.all(idsToDelete.map((id) => api.delete(`/applications/${id}`)))
    selectedRejectedIds.value = []
    selectionMode.value = false
    const identity = resolveApplicantIdentity()
    await loadApplicationsFromApi(identity)
    await loadInterviewSchedules(identity)
  } catch {
    // keep silent fallback; failed deletions will remain in list
  } finally {
    deletingSelected.value = false
  }
}

onMounted(() => {
  const identity = resolveApplicantIdentity()
  void loadInterviewSchedules(identity)
  loading.value = true
  startLoadingWatchdog()
  void loadApplicationsFromApi(identity).then(() => {
    rebuildApplications()
  })

  scheduleRefreshTimer = setInterval(() => {
    void loadInterviewSchedules(identity)
  }, 5000)
  applicationsRefreshTimer = setInterval(() => {
    void loadApplicationsFromApi(identity)
  }, 5000)
})

onBeforeUnmount(() => {
  if (scheduleRefreshTimer) {
    clearInterval(scheduleRefreshTimer)
  }
  if (applicationsRefreshTimer) {
    clearInterval(applicationsRefreshTimer)
  }
  stopLoadingWatchdog()
})

const filteredApplications = computed(() => {
  const q = search.value.trim().toLowerCase()
  return applications.value.filter((app) => {
    const statusMatch = statusFilter.value === "all" || app.statusKey === statusFilter.value
    const searchMatch =
      !q ||
      [app.jobTitle, app.companyName, app.location, app.statusLabel]
        .filter(Boolean)
        .join(" ")
        .toLowerCase()
        .includes(q)
    return statusMatch && searchMatch
  })
})

</script>

<style scoped>
.applications-page {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

.table-card,
.details-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
}

.table-card {
  overflow: hidden;
}

.table-head {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  padding: 16px;
  border-bottom: 1px solid #eef2f7;
  flex-wrap: wrap;
}

.table-head h2 {
  margin: 0;
  color: #0f172a;
  font-size: 18px;
}

.table-head p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 12px;
}

.table-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.search-box {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 8px;
  align-items: center;
  min-height: 40px;
  border: 1px solid #dbe4ee;
  border-radius: 10px;
  padding: 0 10px;
  background: #f8fafc;
  min-width: 260px;
}

.search-box i {
  color: #64748b;
  font-size: 13px;
}

.search-box input {
  border: 0;
  outline: 0;
  background: transparent;
  font-size: 13px;
  color: #0f172a;
}

.table-controls select {
  min-height: 40px;
  border: 1px solid #dbe4ee;
  border-radius: 10px;
  padding: 0 12px;
  background: #fff;
  color: #334155;
  font-size: 13px;
}

.panel-loading {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #64748b;
}

.spinner {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  border: 3px solid #dbe4ee;
  border-top-color: #16a34a;
  animation: spin .75s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.empty-state {
  min-height: 220px;
  display: grid;
  place-items: center;
  text-align: center;
  color: #64748b;
  padding: 20px;
}

.empty-state i {
  font-size: 28px;
  color: #94a3b8;
}

.empty-state h3 {
  margin: 10px 0 4px;
  color: #0f172a;
  font-size: 16px;
}

.empty-state p {
  margin: 0;
  max-width: 440px;
  font-size: 13px;
}

.applications-list {
  display: grid;
  gap: 10px;
  padding: 14px 16px 16px;
}

.bulk-actions {
  display: flex;
  justify-content: flex-start;
  gap: 10px;
  align-items: center;
  border: 1px solid #e5e7eb;
  background: #f8fafc;
  border-radius: 12px;
  padding: 10px 12px;
  flex-wrap: wrap;
}

.bulk-check {
  display: inline-flex;
  gap: 8px;
  align-items: center;
  color: #334155;
  font-size: 13px;
  font-weight: 600;
}

.bulk-meta {
  margin: 0;
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
}

.application-item {
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 12px;
  display: block;
  cursor: pointer;
  background: #fff;
  transition: .15s ease;
}

.application-item:hover {
  background: #f8fafc;
}

.application-item.active {
  border-color: #bbf7d0;
  background: #f0fdf4;
}

.application-item.deleted {
  opacity: 0.62;
  border-style: dashed;
}

.application-item.deleted.active {
  opacity: 0.8;
}

.application-actions {
  margin-top: 10px;
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
}

.item-check {
  display: inline-flex;
  gap: 6px;
  align-items: center;
  color: #475569;
  font-size: 12px;
  font-weight: 600;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  justify-content: space-between;
  flex-wrap: wrap;
}

.title-row h3 {
  margin: 0;
  color: #0f172a;
  font-size: 15px;
}

.company {
  margin: 4px 0 0;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
}

.meta-row {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  margin-top: 8px;
  color: #64748b;
  font-size: 12px;
}

.meta-row span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.deleted-note {
  margin: 8px 0 0;
  color: #b45309;
  font-size: 12px;
  font-weight: 600;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 5px 10px;
  font-size: 11px;
  font-weight: 700;
}

.status-pill.large {
  font-size: 12px;
  padding: 7px 12px;
}

.status-pill.pending { background: #fef3c7; color: #92400e; }
.status-pill.reviewed { background: #dbeafe; color: #1d4ed8; }
.status-pill.interview { background: #ede9fe; color: #6d28d9; }
.status-pill.accepted { background: #dcfce7; color: #166534; }
.status-pill.rejected { background: #fee2e2; color: #b91c1c; }

.details-card {
  padding: 16px;
}

.details-empty {
  min-height: 180px;
  display: grid;
  place-items: center;
  color: #94a3b8;
  font-size: 13px;
}

.details-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  border-bottom: 1px solid #eef2f7;
  padding-bottom: 12px;
}

.eyebrow {
  margin: 0 0 4px;
  font-size: 11px;
  color: #64748b;
  font-weight: 700;
  letter-spacing: .05em;
  text-transform: uppercase;
}

.details-head h3 {
  margin: 0;
  color: #0f172a;
  font-size: 18px;
}

.sub {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.details-grid {
  margin-top: 14px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.deleted-banner {
  margin-top: 12px;
  border: 1px solid #fcd34d;
  background: #fffbeb;
  color: #92400e;
  border-radius: 12px;
  padding: 10px 12px;
  font-size: 13px;
  font-weight: 600;
}

.info-block {
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 14px;
  background: #fff;
}

.info-block h4 {
  margin: 0 0 10px;
  color: #0f172a;
  font-size: 14px;
}

.info-block ul {
  margin: 0;
  padding-left: 18px;
  color: #475569;
  font-size: 13px;
  line-height: 1.6;
}

.timeline-block {
  margin-top: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 14px;
}

.rejection-feedback {
  margin-top: 12px;
  border: 1px solid #fecaca;
  background: #fff1f2;
  border-radius: 14px;
  padding: 14px;
}

.rejection-feedback h4 {
  margin: 0 0 8px;
  font-size: 14px;
  color: #991b1b;
}

.rejection-feedback p {
  margin: 0;
  color: #7f1d1d;
  font-size: 13px;
  line-height: 1.55;
  white-space: pre-wrap;
}

.ghost-btn {
  border: 1px solid #d1d5db;
  background: #ffffff;
  color: #334155;
  border-radius: 8px;
  padding: 7px 10px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.ghost-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.danger-soft-btn {
  border: 1px solid #fca5a5;
  background: #fff1f2;
  color: #b91c1c;
  border-radius: 8px;
  padding: 7px 10px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.danger-soft-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.timeline-head h4 {
  margin: 0;
  font-size: 14px;
  color: #0f172a;
}

.timeline-head p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 12px;
}

.timeline-list {
  margin-top: 12px;
  display: grid;
  gap: 10px;
}

.timeline-item {
  display: grid;
  grid-template-columns: 16px 1fr;
  gap: 8px;
  align-items: start;
}

.timeline-item .dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  margin-top: 4px;
  background: #cbd5e1;
}

.timeline-item.done .dot {
  background: #22c55e;
}

.timeline-item.processing .dot {
  background: #f59e0b;
}

.timeline-item.failed .dot {
  background: #ef4444;
}

.timeline-item.failed p {
  color: #991b1b;
}

.timeline-item.failed small {
  color: #b91c1c;
}

.timeline-item p {
  margin: 0;
  color: #0f172a;
  font-size: 13px;
  font-weight: 600;
}

.timeline-item small {
  color: #64748b;
  font-size: 11px;
}

@media (max-width: 900px) {
  .details-grid {
    grid-template-columns: 1fr;
  }

  .table-head {
    flex-direction: column;
    align-items: stretch;
  }

  .table-controls {
    width: 100%;
  }

  .search-box {
    min-width: 0;
    width: 100%;
  }

  .table-controls select {
    width: 100%;
  }
}

@media (max-width: 700px) {
  .application-item {
    display: block;
  }

  .bulk-actions {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
