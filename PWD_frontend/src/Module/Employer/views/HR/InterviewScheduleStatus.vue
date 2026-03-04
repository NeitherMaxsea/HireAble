<template>
  <section class="content">
    <div class="page-header">
      <div>
        <h2>Schedule Status</h2>
        <p class="subtitle">Review applicant availability and decide on reschedule requests.</p>
      </div>
      <button class="refresh-btn" :disabled="loading" @click="loadSchedules">
        {{ loading ? "Refreshing..." : "Refresh" }}
      </button>
    </div>

    <div class="filters">
      <input v-model.trim="search" class="search" type="text" placeholder="Search applicant or job title" />
      <select v-model="responseFilter" class="status-filter">
        <option value="all">All responses</option>
        <option value="agreed">Agreed</option>
        <option value="reschedule_requested">Reschedule Requested</option>
        <option value="reschedule_rejected">Reschedule Rejected</option>
        <option value="pending">Pending Response</option>
      </select>
      <select v-model="scheduleStatusFilter" class="status-filter">
        <option value="all">All schedule statuses</option>
        <option value="scheduled">Scheduled</option>
        <option value="reschedule_requested">Reschedule Requested</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div class="card">
      <table v-if="filteredSchedules.length > 0">
        <thead>
          <tr>
            <th>Applicant</th>
            <th>Job Title</th>
            <th>Interview</th>
            <th>Schedule</th>
            <th>Applicant Response</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in filteredSchedules" :key="item.id">
            <td>{{ item.applicantName || "Applicant" }}</td>
            <td>{{ item.jobTitle || "Untitled Job" }}</td>
            <td>
              <span class="status-pill interview-type">{{ toTitle(item.interviewType) }}</span><br />
              <small>{{ toTitle(item.mode) }}</small>
            </td>
            <td>{{ formatDateTime(item.scheduledAt) }}</td>
            <td>
              <span class="status-pill" :class="`resp-${toResponseKey(item.applicantResponse)}`">
                {{ responseLabel(item.applicantResponse) }}
              </span>
              <br />
              <small v-if="item.applicantResponseAt">{{ formatDateTime(item.applicantResponseAt) }}</small>
            </td>
            <td class="actions-cell">
              <button
                v-if="toResponseKey(item.applicantResponse) === 'reschedule_requested'"
                class="btn small warn"
                @click="openReview(item)"
              >
                Review Request
              </button>
              <span v-else class="muted-action">-</span>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty">No interview schedules found.</div>
    </div>

    <transition name="fade">
      <div v-if="showReviewModal && activeSchedule" class="modal-overlay" @click.self="closeReview">
        <div class="modal">
          <div class="modal-head">
            <h3>Reschedule Request</h3>
            <button class="icon-btn" type="button" @click="closeReview">x</button>
          </div>

          <div class="modal-body">
            <p><strong>Applicant:</strong> {{ activeSchedule.applicantName || "Applicant" }}</p>
            <p><strong>Job Title:</strong> {{ activeSchedule.jobTitle || "Untitled Job" }}</p>
            <p><strong>Current Schedule:</strong> {{ formatDateTime(activeSchedule.scheduledAt) }}</p>
            <p><strong>Reason:</strong></p>
            <div class="reason-box">{{ activeSchedule.applicantResponseNote || "No reason provided." }}</div>

            <label class="field">
              <span>New Date & Time (for approval)</span>
              <input v-model="approvedDateTime" type="datetime-local" />
            </label>

            <label class="field">
              <span>Rejection Reason (required when rejecting)</span>
              <textarea
                v-model.trim="rejectReason"
                rows="3"
                class="reject-reason"
                placeholder="State why the reschedule request is rejected"
              ></textarea>
            </label>
          </div>

          <div class="modal-actions">
            <button class="btn neutral" type="button" :disabled="decisionBusy" @click="closeReview">Cancel</button>
            <button class="btn reject" type="button" :disabled="decisionBusy" @click="rejectRequest">
              {{ decisionBusy ? "Saving..." : "Reject Request" }}
            </button>
            <button class="btn approve" type="button" :disabled="decisionBusy" @click="approveRequest">
              {{ decisionBusy ? "Saving..." : "Approve & Update Schedule" }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import api from "@/services/api"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

const schedules = ref([])
const loading = ref(false)
const search = ref("")
const responseFilter = ref("all")
const scheduleStatusFilter = ref("all")
const showReviewModal = ref(false)
const activeSchedule = ref(null)
const approvedDateTime = ref("")
const decisionBusy = ref(false)
const rejectReason = ref("")

const filteredSchedules = computed(() => {
  const needle = search.value.toLowerCase()
  return schedules.value
    .filter((item) => scheduleStatusFilter.value === "all" || item.scheduleStatus === scheduleStatusFilter.value)
    .filter((item) => {
      const key = toResponseKey(item.applicantResponse)
      if (responseFilter.value === "pending") return key === "pending"
      if (responseFilter.value === "all") return true
      return key === responseFilter.value
    })
    .filter((item) => {
      if (!needle) return true
      return (
        String(item.applicantName || "").toLowerCase().includes(needle) ||
        String(item.jobTitle || "").toLowerCase().includes(needle)
      )
    })
    .sort((a, b) => new Date(a.scheduledAt).getTime() - new Date(b.scheduledAt).getTime())
})

function notify(text, color = "#2563eb") {
  Toastify({
    text,
    backgroundColor: color,
    duration: 2600,
    gravity: "top",
    position: "right",
    close: true,
  }).showToast()
}

function toTitle(value) {
  const raw = String(value || "").trim().toLowerCase()
  if (!raw) return "Unknown"
  return `${raw.charAt(0).toUpperCase()}${raw.slice(1)}`
}

function toResponseKey(value) {
  const raw = String(value || "").trim().toLowerCase()
  if (raw === "agreed") return "agreed"
  if (raw === "reschedule_requested") return "reschedule_requested"
  if (raw === "reschedule_rejected") return "reschedule_rejected"
  return "pending"
}

function responseLabel(value) {
  const key = toResponseKey(value)
  if (key === "agreed") return "Agreed"
  if (key === "reschedule_requested") return "Reschedule Requested"
  if (key === "reschedule_rejected") return "Reschedule Rejected"
  return "Pending Response"
}

function formatDateTime(value) {
  const date = new Date(String(value || ""))
  if (Number.isNaN(date.getTime())) return "-"
  return date.toLocaleString()
}

function toLocalDateTime(value) {
  const date = new Date(String(value || ""))
  if (Number.isNaN(date.getTime())) return ""
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, "0")
  const d = String(date.getDate()).padStart(2, "0")
  const hh = String(date.getHours()).padStart(2, "0")
  const mm = String(date.getMinutes()).padStart(2, "0")
  return `${y}-${m}-${d}T${hh}:${mm}`
}

function resolveParams() {
  const companyId = String(localStorage.getItem("companyId") || "").trim()
  const createdByUid = String(localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim()
  if (companyId) return { companyId }
  if (createdByUid) return { createdByUid }
  return {}
}

function resolveApplicationParams() {
  const companyId = String(localStorage.getItem("companyId") || "").trim()
  const postedByUid = String(localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim()
  if (companyId) return { companyId }
  if (postedByUid) return { postedByUid }
  return {}
}

async function loadVisibleJobIds() {
  try {
    const res = await api.get("/jobs")
    const rows = Array.isArray(res?.data) ? res.data : []
    return new Set(rows.map((row) => String(row?.id || row?.jobId || "").trim()).filter(Boolean))
  } catch {
    return null
  }
}

async function loadApplicationJobIdMap() {
  try {
    const res = await api.get("/applications", { params: resolveApplicationParams() })
    const rows = Array.isArray(res?.data) ? res.data : []
    return new Map(
      rows
        .map((row) => [String(row?.id || "").trim(), String(row?.jobId || "").trim()])
        .filter(([applicationId, jobId]) => Boolean(applicationId) && Boolean(jobId))
    )
  } catch {
    return new Map()
  }
}

function hasExistingJobForSchedule(item, applicationJobIdMap, visibleJobIds) {
  if (!(visibleJobIds instanceof Set)) return true
  const applicationId = String(item?.applicationId || "").trim()
  const inferredJobId = applicationId ? String(applicationJobIdMap.get(applicationId) || "").trim() : ""
  // Hide schedule status rows that cannot resolve to an existing job to avoid ghost entries.
  if (!inferredJobId) return false
  return visibleJobIds.has(inferredJobId)
}

async function loadSchedules() {
  loading.value = true
  try {
    const [scheduleRes, applicationJobIdMap, visibleJobIds] = await Promise.all([
      api.get("/interview-schedules", { params: resolveParams() }),
      loadApplicationJobIdMap(),
      loadVisibleJobIds(),
    ])
    const rows = Array.isArray(scheduleRes?.data) ? scheduleRes.data : []
    schedules.value = rows.filter((item) => hasExistingJobForSchedule(item, applicationJobIdMap, visibleJobIds))
  } catch (err) {
    console.error(err)
    schedules.value = []
    notify(err?.response?.data?.message || "Failed to load schedule status.", "#dc2626")
  } finally {
    loading.value = false
  }
}

function openReview(item) {
  activeSchedule.value = item
  approvedDateTime.value = toLocalDateTime(item?.scheduledAt)
  rejectReason.value = ""
  showReviewModal.value = true
}

function closeReview() {
  showReviewModal.value = false
  activeSchedule.value = null
  approvedDateTime.value = ""
  rejectReason.value = ""
}

async function rejectRequest() {
  if (!activeSchedule.value) return
  const reason = String(rejectReason.value || "").trim()
  if (!reason) {
    notify("Please provide a rejection reason.", "#dc2626")
    return
  }
  decisionBusy.value = true
  try {
    await api.put(`/interview-schedules/${activeSchedule.value.id}`, {
      applicantResponse: "reschedule_rejected",
      applicantResponseAt: new Date().toISOString(),
      applicantResponseNote: reason,
      scheduleStatus: "scheduled",
      notes: `HR rejected reschedule request on ${new Date().toLocaleString()}. Reason: ${reason}. ${String(activeSchedule.value.notes || "")}`.trim(),
    })
    notify("Reschedule request rejected.", "#dc2626")
    closeReview()
    await loadSchedules()
  } catch (err) {
    console.error(err)
    notify(err?.response?.data?.message || "Failed to reject request.", "#dc2626")
  } finally {
    decisionBusy.value = false
  }
}

async function approveRequest() {
  if (!activeSchedule.value) return
  const ts = Date.parse(approvedDateTime.value)
  if (Number.isNaN(ts)) {
    notify("Please set a valid new date and time.", "#dc2626")
    return
  }

  decisionBusy.value = true
  try {
    await api.put(`/interview-schedules/${activeSchedule.value.id}`, {
      scheduledAt: new Date(ts).toISOString(),
      applicantResponse: "agreed",
      applicantResponseAt: new Date().toISOString(),
      scheduleStatus: "scheduled",
      notes: `HR approved reschedule on ${new Date().toLocaleString()}. ${String(activeSchedule.value.notes || "")}`.trim(),
    })
    notify("Reschedule request approved and schedule updated.", "#16a34a")
    closeReview()
    await loadSchedules()
  } catch (err) {
    console.error(err)
    notify(err?.response?.data?.message || "Failed to approve request.", "#dc2626")
  } finally {
    decisionBusy.value = false
  }
}

onMounted(async () => {
  await loadSchedules()
})
</script>

<style scoped>
.content { display: flex; flex-direction: column; gap: 14px; }
.page-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; }
.subtitle { font-size: 13px; color: #64748b; }
.refresh-btn { border: none; border-radius: 10px; padding: 9px 14px; background: #2563eb; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; }
.refresh-btn:disabled { opacity: 0.7; cursor: not-allowed; }
.filters { display: flex; gap: 10px; flex-wrap: wrap; }
.search,.status-filter { border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; background: #fff; font-size: 14px; }
.search { width: min(420px, 100%); flex: 1 1 320px; }
.status-filter { min-width: 180px; }
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th,td { border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: middle; padding: 11px 10px; font-size: 14px; }
th { color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.4px; }
small { color: #64748b; font-size: 12px; }
.actions-cell { display: flex; gap: 8px; align-items: center; }
.muted-action { color: #94a3b8; }
.btn { border: none; border-radius: 8px; padding: 8px 12px; cursor: pointer; color: #fff; font-size: 13px; font-weight: 600; }
.btn.small { padding: 7px 10px; font-size: 12px; }
.btn.warn { background: #f59e0b; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.status-pill { display: inline-flex; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 600; }
.interview-type { background: #ede9fe; color: #5b21b6; }
.resp-pending { background: #f1f5f9; color: #475569; }
.resp-agreed { background: #dcfce7; color: #166534; }
.resp-reschedule_requested { background: #fef3c7; color: #92400e; }
.resp-reschedule_rejected { background: #fee2e2; color: #991b1b; }
.empty { min-height: 120px; display: flex; align-items: center; justify-content: center; color: #64748b; font-style: italic; }

.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); display: grid; place-items: center; z-index: 60; padding: 16px; }
.modal { width: min(560px, 100%); background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; box-shadow: 0 22px 40px rgba(15, 23, 42, 0.25); }
.modal-head { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border-bottom: 1px solid #e5e7eb; }
.modal-head h3 { margin: 0; font-size: 16px; }
.icon-btn { border: none; background: #f1f5f9; border-radius: 8px; width: 30px; height: 30px; cursor: pointer; }
.modal-body { padding: 14px 16px; display: grid; gap: 10px; }
.reason-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; color: #334155; min-height: 64px; }
.field { display: flex; flex-direction: column; gap: 6px; }
.field span { font-size: 12px; color: #6b7280; }
.field input { border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; }
.reject-reason { border: 1px solid #d1d5db; border-radius: 10px; padding: 9px 12px; font-size: 14px; font-family: inherit; resize: vertical; }
.modal-actions { display: flex; justify-content: flex-end; gap: 8px; padding: 12px 16px 16px; }
.btn.neutral { background: #64748b; }
.btn.reject { background: #dc2626; }
.btn.approve { background: #16a34a; }

@media (max-width: 768px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .refresh-btn { width: 100%; }
}
</style>
