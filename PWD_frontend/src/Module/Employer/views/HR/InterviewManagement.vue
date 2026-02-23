<template>
  <section class="content">
    <div class="page-header">
      <div>
        <h2>Interview Management</h2>
        <p class="subtitle">
          Create and manage Initial and Final interview schedules for applicants.
        </p>
      </div>
      <button class="refresh-btn" :disabled="loadingApplicants" @click="loadApplicants">
        {{ loadingApplicants ? "Refreshing..." : "Refresh Applicants" }}
      </button>
    </div>

    <div class="card form-card">
      <h3>Create Interview Schedule</h3>

      <p v-if="formError" class="error">{{ formError }}</p>

      <form class="form-grid" @submit.prevent="createSchedule">
        <label class="field">
          <span>Applicant</span>
          <select v-model="form.applicationId" required>
            <option value="" disabled>Select applicant</option>
            <option
              v-for="entry in applicants"
              :key="entry.id"
              :value="entry.id"
            >
              {{ entry.applicantName }} - {{ entry.jobTitle }}
            </option>
          </select>
        </label>

        <label class="field">
          <span>Interview Type</span>
          <select v-model="form.interviewType" required>
            <option value="initial">Initial Interview</option>
            <option value="final">Final Interview</option>
          </select>
        </label>

        <label class="field">
          <span>Schedule Date & Time</span>
          <input v-model="form.scheduledAt" type="datetime-local" required />
        </label>

        <label class="field">
          <span>Mode</span>
          <select v-model="form.mode" required>
            <option value="in-person">In-person</option>
            <option value="online">Online</option>
          </select>
        </label>

        <label class="field">
          <span>{{ form.mode === "online" ? "Meeting Link" : "Location" }}</span>
          <input
            v-model.trim="form.locationOrLink"
            type="text"
            :placeholder="form.mode === 'online' ? 'https://meet...' : 'Office / room'"
            required
          />
        </label>

        <label class="field">
          <span>Interviewer</span>
          <input v-model.trim="form.interviewer" type="text" placeholder="Enter interviewer name" required />
        </label>

        <label class="field full">
          <span>Notes (Optional)</span>
          <textarea v-model.trim="form.notes" rows="3" placeholder="Add interview instructions"></textarea>
        </label>

        <div class="actions full">
          <button type="submit" class="btn primary">Create Schedule</button>
          <button type="button" class="btn ghost" @click="resetForm">Reset</button>
        </div>
      </form>
    </div>

    <div class="filters">
      <input
        v-model.trim="search"
        type="text"
        class="search"
        placeholder="Search by applicant, interviewer, or position"
      />
      <select v-model="typeFilter" class="status-filter">
        <option value="all">All interview types</option>
        <option value="initial">Initial</option>
        <option value="final">Final</option>
      </select>
      <select v-model="scheduleStatusFilter" class="status-filter">
        <option value="all">All schedule statuses</option>
        <option value="scheduled">Scheduled</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div class="card">
      <table v-if="filteredSchedules.length > 0">
        <thead>
          <tr>
            <th>Applicant</th>
            <th>Interview</th>
            <th>Schedule</th>
            <th>Interviewer</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in filteredSchedules" :key="item.id">
            <td>
              <strong>{{ item.applicantName }}</strong><br />
              <small>{{ item.jobTitle }}</small>
            </td>
            <td>
              <span class="status-pill interview-type">{{ toTitle(item.interviewType) }}</span><br />
              <small>{{ toTitle(item.mode) }}</small>
            </td>
            <td>
              {{ formatDateTime(item.scheduledAt) }}<br />
              <small>{{ item.locationOrLink }}</small>
            </td>
            <td>{{ item.interviewer }}</td>
            <td>
              <span class="status-pill" :class="`status-${item.scheduleStatus}`">
                {{ toTitle(item.scheduleStatus) }}
              </span>
            </td>
            <td class="actions-cell">
              <button
                class="btn small complete"
                :disabled="item.scheduleStatus !== 'scheduled'"
                @click="updateScheduleStatus(item.id, 'completed')"
              >
                Complete
              </button>
              <button
                class="btn small cancel"
                :disabled="item.scheduleStatus === 'cancelled'"
                @click="updateScheduleStatus(item.id, 'cancelled')"
              >
                Cancel
              </button>
              <button class="btn small danger" @click="deleteSchedule(item.id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty">
        No interview schedules yet.
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import api from "@/services/api"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

const STORAGE_KEY = "hrInterviewSchedules"

const applicants = ref([])
const schedules = ref([])
const loadingApplicants = ref(false)
const formError = ref("")
const search = ref("")
const typeFilter = ref("all")
const scheduleStatusFilter = ref("all")

const form = reactive({
  applicationId: "",
  interviewType: "initial",
  scheduledAt: "",
  mode: "in-person",
  locationOrLink: "",
  interviewer: "",
  notes: "",
})

const filteredSchedules = computed(() => {
  const needle = search.value.toLowerCase()

  return schedules.value
    .filter((item) => typeFilter.value === "all" || item.interviewType === typeFilter.value)
    .filter((item) => scheduleStatusFilter.value === "all" || item.scheduleStatus === scheduleStatusFilter.value)
    .filter((item) => {
      if (!needle) return true
      return (
        item.applicantName.toLowerCase().includes(needle) ||
        item.jobTitle.toLowerCase().includes(needle) ||
        item.interviewer.toLowerCase().includes(needle)
      )
    })
    .sort((a, b) => new Date(a.scheduledAt).getTime() - new Date(b.scheduledAt).getTime())
})

function toTitle(value) {
  const raw = String(value || "").trim().toLowerCase()
  if (!raw) return "Unknown"
  return `${raw.charAt(0).toUpperCase()}${raw.slice(1)}`
}

function formatDateTime(value) {
  if (!value) return "-"
  const date = new Date(String(value))
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleString()
}

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

async function loadApplicants() {
  loadingApplicants.value = true
  try {
    const response = await api.get("/applications", { params: resolveListParams() })
    const rows = Array.isArray(response?.data) ? response.data : []
    applicants.value = rows.map((row) => ({
      id: String(row?.id || ""),
      applicantName: String(row?.applicantName || "Applicant"),
      applicantEmail: String(row?.applicantEmail || ""),
      applicantId: String(row?.applicantId || ""),
      jobTitle: String(row?.jobTitle || "Untitled Job"),
      status: String(row?.status || "pending").toLowerCase(),
    }))
  } catch (err) {
    console.error(err)
    notify(err?.response?.data?.message || "Failed to load applicants.", "#dc2626")
  } finally {
    loadingApplicants.value = false
  }
}

function loadSchedules() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    const parsed = JSON.parse(raw || "[]")
    schedules.value = Array.isArray(parsed) ? parsed : []
  } catch {
    schedules.value = []
  }
}

function persistSchedules() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(schedules.value))
}

function resetForm() {
  form.applicationId = ""
  form.interviewType = "initial"
  form.scheduledAt = ""
  form.mode = "in-person"
  form.locationOrLink = ""
  form.interviewer = ""
  form.notes = ""
  formError.value = ""
}

async function createSchedule() {
  formError.value = ""
  const selected = applicants.value.find((entry) => entry.id === form.applicationId)
  if (!selected) {
    formError.value = "Please select a valid applicant."
    return
  }

  const timestamp = Date.parse(form.scheduledAt)
  if (Number.isNaN(timestamp) || timestamp < Date.now()) {
    formError.value = "Please select a valid future date and time."
    return
  }

  const duplicate = schedules.value.find(
    (entry) =>
      entry.applicationId === selected.id &&
      entry.interviewType === form.interviewType &&
      entry.scheduleStatus === "scheduled"
  )

  if (duplicate) {
    formError.value = `A scheduled ${form.interviewType} interview already exists for this applicant.`
    return
  }

  const record = {
    id: `iv_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
    applicationId: selected.id,
    applicantName: selected.applicantName,
    applicantEmail: selected.applicantEmail,
    applicantId: selected.applicantId,
    jobTitle: selected.jobTitle,
    interviewType: form.interviewType,
    scheduledAt: new Date(timestamp).toISOString(),
    mode: form.mode,
    locationOrLink: form.locationOrLink,
    interviewer: form.interviewer,
    notes: form.notes,
    scheduleStatus: "scheduled",
    createdAt: new Date().toISOString(),
  }

  schedules.value = [...schedules.value, record]
  persistSchedules()
  resetForm()
  notify("Interview schedule created.", "#16a34a")

  // Keep application state aligned with interview flow.
  try {
    await api.put(`/applications/${selected.id}`, { status: "interviewed" })
  } catch (err) {
    console.error(err)
  }
}

function updateScheduleStatus(id, nextStatus) {
  schedules.value = schedules.value.map((entry) =>
    entry.id === id ? { ...entry, scheduleStatus: nextStatus } : entry
  )
  persistSchedules()
  notify(`Interview marked as ${toTitle(nextStatus)}.`, nextStatus === "completed" ? "#16a34a" : "#f59e0b")
}

function deleteSchedule(id) {
  schedules.value = schedules.value.filter((entry) => entry.id !== id)
  persistSchedules()
  notify("Interview schedule deleted.", "#dc2626")
}

onMounted(async () => {
  loadSchedules()
  await loadApplicants()
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

.card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;
  overflow-x: auto;
}

.form-card h3 {
  margin: 0 0 12px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field span {
  font-size: 12px;
  color: #6b7280;
}

.field input,
.field select,
.field textarea {
  border: 1px solid #d1d5db;
  border-radius: 10px;
  padding: 9px 12px;
  font-size: 14px;
  font-family: inherit;
}

.field.full,
.actions.full {
  grid-column: 1 / -1;
}

.actions {
  display: flex;
  gap: 8px;
}

.btn {
  border: none;
  border-radius: 8px;
  padding: 8px 12px;
  cursor: pointer;
  color: #ffffff;
  font-size: 13px;
  font-weight: 600;
}

.btn.primary {
  background: #2563eb;
}

.btn.ghost {
  background: #64748b;
}

.btn.small {
  padding: 7px 10px;
  font-size: 12px;
}

.btn.complete {
  background: #16a34a;
}

.btn.cancel {
  background: #f59e0b;
}

.btn.danger {
  background: #dc2626;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.filters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.search,
.status-filter {
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
  min-width: 180px;
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

.actions-cell {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.status-pill {
  display: inline-flex;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
}

.interview-type {
  background: #ede9fe;
  color: #5b21b6;
}

.status-scheduled {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-completed {
  background: #dcfce7;
  color: #166534;
}

.status-cancelled {
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
