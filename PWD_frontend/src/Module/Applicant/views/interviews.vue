<template>
  <div class="interviews-page">
    <aside class="schedule-panel">
      <div class="panel-head">
        <h2>Interviews</h2>
        <p>{{ interviews.length }} scheduled items</p>
      </div>

      <div class="filter-row">
        <button
          v-for="item in filters"
          :key="item"
          type="button"
          class="filter-btn"
          :class="{ active: activeFilter === item }"
          @click="activeFilter = item"
        >
          {{ item }}
        </button>
      </div>

      <div class="schedule-list">
        <button
          v-for="item in filteredInterviews"
          :key="item.id"
          type="button"
          class="schedule-item"
          :class="{ active: selectedInterview?.id === item.id }"
          @click="selectInterview(item)"
        >
          <div class="schedule-date">
            <span class="day">{{ item.day }}</span>
            <span class="month">{{ item.month }}</span>
          </div>
          <div class="schedule-copy">
            <div class="schedule-top">
              <strong>{{ item.jobTitle }}</strong>
              <span class="status" :class="item.statusKey">{{ item.status }}</span>
            </div>
            <p>{{ item.company }}</p>
            <small>{{ item.time }} • {{ item.mode }}</small>
          </div>
        </button>

        <div v-if="filteredInterviews.length === 0" class="empty">
          No interviews in this filter.
        </div>
      </div>
    </aside>

    <section class="detail-panel">
      <div v-if="!selectedInterview" class="empty-center">
        Select an interview schedule to view details.
      </div>

      <template v-else>
        <div class="detail-head">
          <div>
            <p class="eyebrow">Interview Details</p>
            <h3>{{ selectedInterview.jobTitle }}</h3>
            <p class="sub">{{ selectedInterview.company }}</p>
          </div>
          <span class="status-pill" :class="selectedInterview.statusKey">
            {{ selectedInterview.status }}
          </span>
        </div>

        <div class="detail-grid">
          <div class="info-card">
            <h4>Schedule</h4>
            <ul>
              <li><strong>Date:</strong> {{ selectedInterview.fullDate }}</li>
              <li><strong>Time:</strong> {{ selectedInterview.time }}</li>
              <li><strong>Mode:</strong> {{ selectedInterview.mode }}</li>
              <li><strong>Duration:</strong> {{ selectedInterview.duration }}</li>
            </ul>
          </div>

          <div class="info-card">
            <h4>Location / Link</h4>
            <ul>
              <li><strong>Venue:</strong> {{ selectedInterview.venue }}</li>
              <li><strong>Contact:</strong> {{ selectedInterview.contactPerson }}</li>
              <li><strong>Email:</strong> {{ selectedInterview.contactEmail }}</li>
            </ul>
            <a
              v-if="selectedInterview.meetingLink"
              class="action-link"
              :href="selectedInterview.meetingLink"
              target="_blank"
              rel="noopener noreferrer"
            >
              Open meeting link
            </a>
          </div>
        </div>

        <div class="timeline-card">
          <h4>Preparation Checklist</h4>
          <div class="checklist">
            <label v-for="(task, idx) in selectedInterview.checklist" :key="`${selectedInterview.id}-${idx}`">
              <input type="checkbox" v-model="task.done" />
              <span>{{ task.label }}</span>
            </label>
          </div>
        </div>

        <div class="notes-card">
          <div class="notes-head">
            <h4>Interview Notes</h4>
            <span>{{ selectedInterview.notes.length }} updates</span>
          </div>
          <div class="notes-list">
            <div v-for="note in selectedInterview.notes" :key="note.id" class="note-item">
              <div class="note-dot"></div>
              <div class="note-copy">
                <p>{{ note.text }}</p>
                <small>{{ note.time }}</small>
              </div>
            </div>
          </div>
        </div>

        <div class="actions-row">
          <button type="button" class="primary-btn" @click="markConfirmed">
            Confirm Schedule
          </button>
          <button type="button" class="ghost-btn" @click="openRescheduleModal">
            Request Reschedule
          </button>
        </div>
      </template>
    </section>

    <transition name="fade">
      <div v-if="showRescheduleModal" class="modal-overlay" @click.self="closeRescheduleModal">
        <div class="modal-card">
          <div class="modal-head">
            <h4>Request Reschedule</h4>
            <button type="button" class="modal-close" @click="closeRescheduleModal">x</button>
          </div>
          <div class="modal-body">
            <p class="modal-caption">
              Please provide a reason so HR can review your request.
            </p>
            <textarea
              v-model.trim="rescheduleReason"
              rows="4"
              class="modal-textarea"
              placeholder="Enter your reason for rescheduling"
            ></textarea>
          </div>
          <div class="modal-actions">
            <button type="button" class="modal-btn ghost" :disabled="rescheduleSubmitting" @click="closeRescheduleModal">
              Cancel
            </button>
            <button type="button" class="modal-btn primary" :disabled="rescheduleSubmitting" @click="requestReschedule">
              {{ rescheduleSubmitting ? "Submitting..." : "Submit Request" }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import api from "@/services/api"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

const filters = ["All", "Upcoming", "Completed", "Rescheduled"]
const activeFilter = ref("All")
const interviews = ref([])
const selectedInterviewId = ref("")
const showRescheduleModal = ref(false)
const rescheduleReason = ref("")
const rescheduleSubmitting = ref(false)
let refreshTimer = null

const filteredInterviews = computed(() => {
  if (activeFilter.value === "All") return interviews.value
  return interviews.value.filter((item) => item.status === activeFilter.value)
})

const selectedInterview = computed(() => {
  return interviews.value.find((item) => item.id === selectedInterviewId.value) || filteredInterviews.value[0] || null
})

function resolveApplicantIdentity() {
  return {
    id: String(
      localStorage.getItem("userUid") ||
      localStorage.getItem("uid") ||
      localStorage.getItem("sessionUid") ||
      ""
    ).trim(),
    email: String(localStorage.getItem("userEmail") || "").trim().toLowerCase(),
    name: String(localStorage.getItem("userName") || localStorage.getItem("name") || "").trim().toLowerCase(),
  }
}

function normalizeStatus(rawStatus) {
  const status = String(rawStatus || "").trim().toLowerCase()
  if (status === "completed") return { label: "Completed", key: "completed" }
  if (status === "cancelled" || status === "reschedule_requested") return { label: "Rescheduled", key: "rescheduled" }
  return { label: "Upcoming", key: "upcoming" }
}

function formatDateDetails(value) {
  const date = new Date(String(value))
  if (Number.isNaN(date.getTime())) {
    return {
      day: "--",
      month: "---",
      fullDate: "Unknown date",
      time: "Unknown time",
    }
  }

  return {
    day: date.toLocaleDateString("en-US", { day: "2-digit" }),
    month: date.toLocaleDateString("en-US", { month: "short" }).toUpperCase(),
    fullDate: date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    }),
    time: date.toLocaleTimeString("en-US", {
      hour: "numeric",
      minute: "2-digit",
    }),
  }
}

function toFriendlyMode(rawMode) {
  const mode = String(rawMode || "").trim().toLowerCase()
  if (mode === "online") return "Online"
  if (mode === "in-person") return "On-site"
  return mode ? `${mode.charAt(0).toUpperCase()}${mode.slice(1)}` : "On-site"
}

function buildChecklist(rawMode) {
  if (String(rawMode || "").trim().toLowerCase() === "online") {
    return [
      { label: "Check internet and camera", done: false },
      { label: "Open meeting link before start time", done: false },
      { label: "Prepare valid ID", done: false },
    ]
  }

  return [
    { label: "Prepare valid ID and resume", done: false },
    { label: "Confirm route to interview location", done: false },
    { label: "Arrive 10-15 minutes early", done: false },
  ]
}

function toReadableDateTime(value) {
  const date = new Date(String(value))
  if (Number.isNaN(date.getTime())) return "Now"
  return date.toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
  })
}

function toInterviewModel(entry) {
  const schedule = formatDateDetails(entry.scheduledAt)
  const modeRaw = String(entry.mode || "").trim().toLowerCase()
  const status = normalizeStatus(entry.scheduleStatus)
  const meetingLink = modeRaw === "online" ? String(entry.locationOrLink || "").trim() : ""

  const notes = [
    {
      id: `n-create-${entry.id}`,
      text: "Interview invitation sent by HR.",
      time: toReadableDateTime(entry.createdAt || entry.scheduledAt),
    },
  ]

  if (meetingLink) {
    notes.push({
      id: `n-link-${entry.id}`,
      text: "Meeting link attached for online interview.",
      time: toReadableDateTime(entry.createdAt || entry.scheduledAt),
    })
  }

  if (String(entry.scheduleStatus || "").trim().toLowerCase() === "completed") {
    notes.unshift({
      id: `n-complete-${entry.id}`,
      text: "Interview marked as completed by HR.",
      time: toReadableDateTime(entry.updatedAt || entry.scheduledAt),
    })
  }

  if (String(entry.scheduleStatus || "").trim().toLowerCase() === "cancelled") {
    notes.unshift({
      id: `n-cancel-${entry.id}`,
      text: "Interview schedule changed by HR.",
      time: toReadableDateTime(entry.updatedAt || entry.scheduledAt),
    })
  }

  return {
    id: String(entry.id || `int-${Date.now()}`),
    day: schedule.day,
    month: schedule.month,
    fullDate: schedule.fullDate,
    time: schedule.time,
    duration: entry.interviewType === "final" ? "1 hour" : "45 minutes",
    mode: toFriendlyMode(entry.mode),
    venue: modeRaw === "online" ? "Online Meeting" : String(entry.locationOrLink || "Office").trim(),
    meetingLink,
    company: String(entry.companyName || entry.company || "Hiring Company").trim(),
    jobTitle: String(entry.jobTitle || "Interview").trim(),
    status: status.label,
    statusKey: status.key,
    contactPerson: String(entry.interviewer || "HR Recruitment Team").trim(),
    contactEmail: "hr@hireable.local",
    checklist: buildChecklist(entry.mode),
    notes,
    scheduledAtMillis: Date.parse(String(entry.scheduledAt || "")) || 0,
  }
}

function entryBelongsToApplicant(entry, identity, applicationIds = new Set()) {
  const applicationId = String(entry?.applicationId || "").trim()
  const applicantId = String(entry?.applicantId || "").trim()
  const applicantEmail = String(entry?.applicantEmail || "").trim().toLowerCase()
  const applicantName = String(entry?.applicantName || "").trim().toLowerCase()

  if (applicationId && applicationIds.has(applicationId)) return true
  if (identity.id && applicantId && applicantId === identity.id) return true
  if (identity.email && applicantEmail && applicantEmail === identity.email) return true
  if (identity.name && applicantName && applicantName === identity.name) return true
  return false
}

async function loadApplicantApplicationIds(identity) {
  const params = {}
  if (identity.id) {
    params.applicantId = identity.id
  } else if (identity.email) {
    params.applicantEmail = identity.email
  } else {
    return new Set()
  }

  try {
    const res = await api.get("/applications", { params })
    const rows = Array.isArray(res?.data) ? res.data : []
    return new Set(rows.map((row) => String(row?.id || "").trim()).filter(Boolean))
  } catch {
    return new Set()
  }
}

async function loadInterviews() {
  const identity = resolveApplicantIdentity()
  if (!identity.id && !identity.email) {
    interviews.value = []
    selectedInterviewId.value = ""
    return
  }
  const applicationIds = await loadApplicantApplicationIds(identity)

  let rows = []
  const params = {}
  if (identity.id) params.applicantId = identity.id
  if (identity.email) params.applicantEmail = identity.email

  try {
    const res = await api.get("/interview-schedules", { params })
    rows = Array.isArray(res?.data) ? res.data : []
  } catch {
    rows = []
  }

  const mapped = rows
    .filter((entry) => entryBelongsToApplicant(entry, identity, applicationIds))
    .map((entry) => toInterviewModel(entry))
    .sort((a, b) => a.scheduledAtMillis - b.scheduledAtMillis)

  interviews.value = mapped

  if (!mapped.some((item) => item.id === selectedInterviewId.value)) {
    selectedInterviewId.value = mapped[0]?.id || ""
  }
}

function selectInterview(item) {
  selectedInterviewId.value = item.id
}

function toast(text, background = "#0f172a") {
  Toastify({
    text,
    duration: 2500,
    gravity: "top",
    position: "right",
    stopOnFocus: true,
    style: { background },
  }).showToast()
}

function markConfirmed() {
  if (!selectedInterview.value) return
  api
    .put(`/interview-schedules/${selectedInterview.value.id}`, {
      applicantResponse: "agreed",
      applicantResponseAt: new Date().toISOString(),
      scheduleStatus: "scheduled",
    })
    .then(async () => {
      await loadInterviews()
      toast("Interview schedule confirmed.", "#15803d")
    })
    .catch((err) => {
      console.error(err)
      toast(err?.response?.data?.message || "Failed to confirm attendance.", "#dc2626")
    })
}

function openRescheduleModal() {
  if (!selectedInterview.value) return
  rescheduleReason.value = ""
  showRescheduleModal.value = true
}

function closeRescheduleModal() {
  if (rescheduleSubmitting.value) return
  showRescheduleModal.value = false
  rescheduleReason.value = ""
}

function requestReschedule() {
  if (!selectedInterview.value) return
  const trimmedReason = String(rescheduleReason.value || "").trim()
  if (!trimmedReason) {
    toast("Please provide a reason for reschedule request.", "#dc2626")
    return
  }

  rescheduleSubmitting.value = true
  api
    .put(`/interview-schedules/${selectedInterview.value.id}`, {
      applicantResponse: "reschedule_requested",
      applicantResponseAt: new Date().toISOString(),
      scheduleStatus: "reschedule_requested",
      applicantResponseNote: trimmedReason,
    })
    .then(async () => {
      await loadInterviews()
      closeRescheduleModal()
      toast("Reschedule request sent.", "#f59e0b")
    })
    .catch((err) => {
      console.error(err)
      toast(err?.response?.data?.message || "Failed to request reschedule.", "#dc2626")
    })
    .finally(() => {
      rescheduleSubmitting.value = false
    })
}

onMounted(() => {
  void loadInterviews()
  refreshTimer = window.setInterval(() => {
    void loadInterviews()
  }, 5000)
})

onBeforeUnmount(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
  }
})
</script>

<style scoped>
.interviews-page {
  display: grid;
  grid-template-columns: 360px 1fr;
  gap: 16px;
  min-height: calc(100vh - 170px);
}

.schedule-panel,
.detail-panel {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
}

.schedule-panel {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.panel-head {
  padding: 16px;
  border-bottom: 1px solid #eef2f7;
}

.panel-head h2 {
  margin: 0;
  font-size: 18px;
  color: #0f172a;
}

.panel-head p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 12px;
}

.filter-row {
  padding: 12px 14px 0;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.filter-btn {
  border: 1px solid #dbe4ee;
  background: #fff;
  color: #475569;
  border-radius: 999px;
  padding: 7px 12px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.filter-btn.active {
  background: #ecfdf5;
  border-color: #bbf7d0;
  color: #166534;
}

.schedule-list {
  padding: 12px 8px 10px;
  overflow-y: auto;
}

.schedule-item {
  width: 100%;
  display: grid;
  grid-template-columns: 58px 1fr;
  gap: 10px;
  align-items: center;
  text-align: left;
  border: 1px solid transparent;
  background: #fff;
  border-radius: 14px;
  padding: 10px;
  cursor: pointer;
  margin-bottom: 8px;
}

.schedule-item:hover {
  background: #f8fafc;
}

.schedule-item.active {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.schedule-date {
  border-radius: 12px;
  background: #0f172a;
  color: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 56px;
}

.schedule-date .day {
  font-size: 16px;
  font-weight: 800;
  line-height: 1;
}

.schedule-date .month {
  font-size: 10px;
  opacity: .9;
  margin-top: 3px;
  letter-spacing: .06em;
}

.schedule-copy {
  min-width: 0;
}

.schedule-top {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: space-between;
}

.schedule-top strong {
  font-size: 13px;
  color: #0f172a;
}

.schedule-copy p {
  margin: 4px 0 2px;
  font-size: 12px;
  color: #475569;
}

.schedule-copy small {
  color: #64748b;
  font-size: 11px;
}

.status {
  font-size: 10px;
  font-weight: 700;
  border-radius: 999px;
  padding: 4px 8px;
  white-space: nowrap;
}

.status.upcoming { background: #dcfce7; color: #166534; }
.status.completed { background: #dbeafe; color: #1d4ed8; }
.status.rescheduled { background: #fef3c7; color: #92400e; }

.detail-panel {
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.empty,
.empty-center {
  color: #94a3b8;
  font-size: 13px;
}

.empty-center {
  margin: auto;
}

.detail-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  border-bottom: 1px solid #eef2f7;
  padding-bottom: 12px;
}

.eyebrow {
  margin: 0 0 4px;
  font-size: 11px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .06em;
  font-weight: 700;
}

.detail-head h3 {
  margin: 0;
  color: #0f172a;
  font-size: 20px;
}

.sub {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.status-pill {
  border-radius: 999px;
  padding: 7px 12px;
  font-weight: 700;
  font-size: 12px;
}

.status-pill.upcoming { background: #dcfce7; color: #166534; }
.status-pill.completed { background: #dbeafe; color: #1d4ed8; }
.status-pill.rescheduled { background: #fef3c7; color: #92400e; }

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.info-card,
.timeline-card,
.notes-card {
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  background: #fff;
  padding: 14px;
}

.info-card h4,
.timeline-card h4,
.notes-card h4 {
  margin: 0 0 10px;
  color: #0f172a;
  font-size: 14px;
}

.info-card ul {
  margin: 0;
  padding-left: 18px;
  color: #475569;
  font-size: 13px;
  line-height: 1.6;
}

.action-link {
  display: inline-block;
  margin-top: 10px;
  color: #15803d;
  font-weight: 700;
  text-decoration: none;
}

.checklist {
  display: grid;
  gap: 8px;
}

.checklist label {
  display: flex;
  gap: 8px;
  align-items: center;
  font-size: 13px;
  color: #334155;
}

.checklist input {
  accent-color: #16a34a;
}

.notes-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.notes-head span {
  color: #94a3b8;
  font-size: 11px;
  font-weight: 700;
}

.notes-list {
  display: grid;
  gap: 10px;
}

.note-item {
  display: grid;
  grid-template-columns: 12px 1fr;
  gap: 8px;
  align-items: start;
}

.note-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: #22c55e;
  margin-top: 4px;
}

.note-copy p {
  margin: 0;
  color: #334155;
  font-size: 13px;
}

.note-copy small {
  color: #94a3b8;
  font-size: 11px;
}

.actions-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.primary-btn,
.ghost-btn {
  border-radius: 12px;
  padding: 10px 14px;
  font-weight: 700;
  cursor: pointer;
}

.primary-btn {
  border: 0;
  background: linear-gradient(135deg, #15803d, #22c55e);
  color: #fff;
}

.ghost-btn {
  border: 1px solid #dbe4ee;
  background: #fff;
  color: #334155;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity .2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.52);
  display: grid;
  place-items: center;
  z-index: 70;
  padding: 16px;
}

.modal-card {
  width: min(520px, 100%);
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  box-shadow: 0 20px 50px rgba(15, 23, 42, 0.28);
}

.modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 14px 16px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-head h4 {
  margin: 0;
  font-size: 16px;
  color: #0f172a;
}

.modal-close {
  border: 1px solid #d1d5db;
  background: #fff;
  border-radius: 8px;
  width: 30px;
  height: 30px;
  cursor: pointer;
}

.modal-body {
  padding: 14px 16px;
  display: grid;
  gap: 10px;
}

.modal-caption {
  margin: 0;
  font-size: 13px;
  color: #475569;
}

.modal-textarea {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 14px;
  font-family: inherit;
  resize: vertical;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 0 16px 16px;
}

.modal-btn {
  border: none;
  border-radius: 10px;
  padding: 10px 14px;
  cursor: pointer;
  font-weight: 700;
  font-size: 13px;
}

.modal-btn.primary {
  background: #15803d;
  color: #fff;
}

.modal-btn.ghost {
  background: #f1f5f9;
  color: #334155;
}

.modal-btn:disabled {
  opacity: .65;
  cursor: not-allowed;
}

@media (max-width: 1100px) {
  .interviews-page {
    grid-template-columns: 1fr;
    min-height: auto;
  }

  .schedule-panel {
    max-height: 330px;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>

