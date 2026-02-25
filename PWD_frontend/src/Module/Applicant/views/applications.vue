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
        <article
          v-for="app in filteredApplications"
          :key="app.id"
          class="application-item"
          :class="{ active: selectedApplication?.id === app.id }"
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

        <div class="details-grid">
          <div class="info-block">
            <h4>Application Summary</h4>
            <ul>
              <li><strong>Status:</strong> {{ selectedApplication.statusLabel }}</li>
              <li><strong>Applied At:</strong> {{ selectedApplication.appliedAtLabel }}</li>
              <li><strong>Location:</strong> {{ selectedApplication.location || "Not specified" }}</li>
              <li><strong>Exact Address:</strong> {{ selectedApplication.exactAddress || "Not specified" }}</li>
              <li><strong>Work Type:</strong> {{ selectedApplication.jobType || "Not specified" }}</li>
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
            <div class="timeline-item" :class="{ done: selectedTimeline.reviewDone }">
              <span class="dot"></span>
              <div>
                <p>Under review</p>
                <small>{{ selectedTimeline.reviewText }}</small>
              </div>
            </div>
            <div
              class="timeline-item"
              :class="{ done: selectedTimeline.initialInterviewDone }"
            >
              <span class="dot"></span>
              <div>
                <p>Initial Interview</p>
                <small>{{ selectedTimeline.initialInterviewText }}</small>
              </div>
            </div>
            <div
              class="timeline-item"
              :class="{ done: selectedTimeline.finalInterviewDone }"
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
import { db } from "@/lib/client-platform"
import { collection, onSnapshot, query, where } from "@/lib/laravel-data"

const loading = ref(true)
const rawApplications = ref([])
const applications = ref([])
const jobsById = ref({})
const selectedApplication = ref(null)
const search = ref("")
const statusFilter = ref("all")
const interviewSchedules = ref([])
let unsubscribeApplications = null
let unsubscribeJobs = null
let scheduleRefreshTimer = null

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
  if (["interview", "for interview", "interview_scheduled"].includes(s)) return { key: "interview", label: "Interview" }
  if (["reviewed", "screening", "shortlisted"].includes(s)) return { key: "reviewed", label: "Reviewed" }
  return { key: "pending", label: "Pending" }
}

function normalizeScheduleStatus(rawStatus) {
  return String(rawStatus || "scheduled").trim().toLowerCase()
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
    const res = await api.get("/interview-schedules", { params })
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
      initialInterviewText: "No initial interview schedule yet",
      finalInterviewDone: false,
      finalInterviewText: "No final interview schedule yet",
    }
  }

  const statusKey = String(app.statusKey || "pending").toLowerCase()
  const reviewDone = statusKey !== "pending"

  const related = interviewSchedules.value.filter((row) => String(row?.applicationId || "").trim() === String(app.id || "").trim())
  const activeInitial = related.some((row) => {
    const type = String(row?.interviewType || "initial").trim().toLowerCase()
    const status = normalizeScheduleStatus(row?.scheduleStatus)
    return type === "initial" && status !== "cancelled"
  })
  const activeFinal = related.some((row) => {
    const type = String(row?.interviewType || "").trim().toLowerCase()
    const status = normalizeScheduleStatus(row?.scheduleStatus)
    return type === "final" && status !== "cancelled"
  })

  return {
    reviewDone,
    reviewText: reviewDone ? "Reviewed by HR" : "Waiting for HR review",
    initialInterviewDone: activeInitial,
    initialInterviewText: activeInitial ? "Initial interview schedule is set" : "No initial interview schedule yet",
    finalInterviewDone: activeFinal,
    finalInterviewText: activeFinal ? "Final interview schedule is set" : "No final interview schedule yet",
  }
})

function rebuildApplications() {
  const rows = rawApplications.value.map((rawDoc) => {
    const raw = rawDoc.raw || {}
    const normalized = normalizeStatus(raw.status)
    const jobId = String(raw.jobId || "").trim()
    const linkedJob = jobsById.value[jobId] || {}
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
      statusKey: normalized.key,
      statusLabel: normalized.label,
      appliedAtRaw: raw.appliedAt || raw.createdAt || raw.updatedAt || null,
      appliedAtLabel: formatDateLabel(raw.appliedAt || raw.createdAt || raw.updatedAt || null),
      appliedAtMillis: toMillis(raw.appliedAt || raw.createdAt || raw.updatedAt || null),
    }
  }).sort((a, b) => b.appliedAtMillis - a.appliedAtMillis)

  applications.value = rows
  if (!selectedApplication.value || !rows.find((r) => r.id === selectedApplication.value.id)) {
    selectedApplication.value = rows[0] || null
  } else {
    selectedApplication.value = rows.find((r) => r.id === selectedApplication.value.id) || selectedApplication.value
  }
}

onMounted(() => {
  const identity = resolveApplicantIdentity()
  void loadInterviewSchedules(identity)
  scheduleRefreshTimer = setInterval(() => {
    void loadInterviewSchedules(identity)
  }, 5000)

  unsubscribeJobs = onSnapshot(
    collection(db, "jobs"),
    (snapshot) => {
      const next = {}
      snapshot.docs.forEach((docSnap) => {
        const raw = docSnap.data() || {}
        const id = String(raw.id || docSnap.id || "").trim()
        if (!id) return
        next[id] = raw
      })
      jobsById.value = next
      rebuildApplications()
    },
    () => {
      jobsById.value = {}
      rebuildApplications()
    }
  )

  let target = null

  if (identity.id) {
    target = query(collection(db, "applications"), where("applicantId", "==", identity.id))
  } else if (identity.email) {
    target = query(collection(db, "applications"), where("applicantEmail", "==", identity.email))
  }

  if (!target) {
    loading.value = false
    applications.value = []
    return
  }

  unsubscribeApplications = onSnapshot(
    target,
    (snapshot) => {
      rawApplications.value = snapshot.docs.map((docSnap) => ({
        id: String(docSnap.id || ""),
        raw: docSnap.data() || {},
      }))
      rebuildApplications()
      loading.value = false
    },
    () => {
      rawApplications.value = []
      applications.value = []
      selectedApplication.value = null
      loading.value = false
    }
  )
})

onBeforeUnmount(() => {
  if (scheduleRefreshTimer) {
    clearInterval(scheduleRefreshTimer)
  }
  if (typeof unsubscribeJobs === "function") {
    unsubscribeJobs()
  }
  if (typeof unsubscribeApplications === "function") {
    unsubscribeApplications()
  }
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
}
</style>
