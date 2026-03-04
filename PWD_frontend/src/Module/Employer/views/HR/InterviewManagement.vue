<template>
  <section class="content">
    <div class="page-header">
      <div>
        <h2>Interview Scheduling</h2>
        <p class="subtitle">
          Create and manage Initial and Final interview schedules for applicants.
        </p>
      </div>
      <button class="refresh-btn" :disabled="loadingApplicants" @click="loadApplicants">
        {{ loadingApplicants ? "Refreshing..." : "Refresh Applicants" }}
      </button>
    </div>

    <div class="top-layout">
      <div class="main-column">
        <div class="card form-card">
          <h3>Create Interview Schedule</h3>

          <p v-if="formError" class="error">{{ formError }}</p>

          <form class="form-grid" @submit.prevent="createSchedule">
            <label class="field">
              <span>Job Title</span>
              <select v-model="form.selectedJobKey" required>
                <option value="" disabled>Select job title</option>
                <option
                  v-for="job in acceptedJobOptions"
                  :key="job.key"
                  :value="job.key"
                >
                  {{ job.title }}
                </option>
              </select>
            </label>

            <label class="field">
              <span>Accepted Applicant</span>
              <select v-model="form.applicationId" required :disabled="!form.selectedJobKey">
                <option value="" disabled>
                  {{ form.selectedJobKey ? "Select accepted applicant" : "Select job title first" }}
                </option>
                <option
                  v-for="entry in filteredEligibleApplicants"
                  :key="entry.id"
                  :value="entry.id"
                >
                  {{ entry.applicantName }}
                </option>
              </select>
            </label>

            <label class="field">
              <span>Interview Type</span>
              <select v-model="form.interviewType" required :disabled="!form.applicationId">
                <option
                  v-for="option in availableInterviewTypeOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </label>

            <label class="field">
              <span>Schedule Date & Time</span>
              <input v-model="form.scheduledAt" type="datetime-local" :min="minScheduleDateTime" required />
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
              <button type="submit" class="btn primary" :disabled="creatingSchedule">
                {{ creatingSchedule ? "Creating..." : "Create Schedule" }}
              </button>
              <button type="button" class="btn ghost" :disabled="creatingSchedule" @click="resetForm">Reset</button>
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
                <th>Job Title</th>
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
                  <strong>{{ item.applicantName }}</strong>
                </td>
                <td>{{ item.jobTitle }}</td>
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
                    :disabled="normalizeScheduleStatus(item.scheduleStatus) !== 'scheduled'"
                    @click="updateScheduleStatus(item.id, 'completed')"
                  >
                    Complete
                  </button>
                  <button
                    class="btn small cancel"
                    :disabled="normalizeScheduleStatus(item.scheduleStatus) === 'cancelled'"
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
      </div>

      <aside class="card calendar-card">
        <div class="calendar-head">
          <h3>Schedule Calendar</h3>
          <div class="calendar-nav">
            <button class="icon-btn" type="button" @click="shiftCalendarMonth(-1)">&#8249;</button>
            <strong>{{ calendarMonthLabel }}</strong>
            <button class="icon-btn" type="button" @click="shiftCalendarMonth(1)">&#8250;</button>
          </div>
        </div>

        <div class="calendar-grid week-grid">
          <span v-for="day in weekdayLabels" :key="day">{{ day }}</span>
        </div>

        <div class="calendar-grid days-grid">
          <button
            v-for="day in calendarDays"
            :key="day.key"
            type="button"
            class="day-cell"
            :disabled="day.isPastDate"
            :class="{
              muted: !day.isCurrentMonth,
              today: day.isToday,
              selected: day.isSelected,
              busy: day.scheduleCount > 0,
              past: day.isPastDate
            }"
            @click="selectCalendarDay(day)"
          >
            <span class="day-number">{{ day.label }}</span>
            <small v-if="day.scheduleCount > 0">{{ day.scheduleCount }}</small>
          </button>
        </div>

        <p class="calendar-caption">
          Scheduled interviews are marked with a blue badge. Click a date to set it in the schedule field.
        </p>

        <div class="day-list">
          <h4>{{ selectedCalendarLabel }}</h4>
          <ul v-if="selectedDaySchedules.length > 0">
            <li v-for="item in selectedDaySchedules" :key="item.id">
              <strong>{{ formatTime(item.scheduledAt) }}</strong> - {{ item.applicantName }}
            </li>
          </ul>
          <p v-else class="calendar-empty">No scheduled interviews for this day.</p>
        </div>
      </aside>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import api from "@/services/api"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

const applicants = ref([])
const schedules = ref([])
const loadingApplicants = ref(false)
const creatingSchedule = ref(false)
const formError = ref("")
const search = ref("")
const typeFilter = ref("all")
const scheduleStatusFilter = ref("all")

const form = reactive({
  selectedJobKey: "",
  applicationId: "",
  interviewType: "initial",
  scheduledAt: "",
  mode: "in-person",
  locationOrLink: "",
  interviewer: "",
  notes: "",
})
const weekdayLabels = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]
const calendarBaseDate = ref(new Date())
const selectedCalendarDateKey = ref("")

function normalizeScheduleStatus(value) {
  return String(value || "scheduled").trim().toLowerCase()
}

const filteredSchedules = computed(() => {
  const needle = search.value.toLowerCase()

  return schedules.value
    .filter((item) => typeFilter.value === "all" || item.interviewType === typeFilter.value)
    .filter((item) => scheduleStatusFilter.value === "all" || normalizeScheduleStatus(item.scheduleStatus) === scheduleStatusFilter.value)
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
const eligibleApplicants = computed(() => {
  return applicants.value.filter((entry) => isApplicantAccepted(entry.status))
})
const acceptedJobOptions = computed(() => {
  const map = new Map()
  eligibleApplicants.value.forEach((entry) => {
    const jobKey = String(entry.jobId || entry.jobTitle || "").trim()
    if (!jobKey) return
    if (!map.has(jobKey)) {
      map.set(jobKey, {
        key: jobKey,
        title: String(entry.jobTitle || "Untitled Job").trim(),
      })
    }
  })
  return Array.from(map.values()).sort((a, b) => a.title.localeCompare(b.title))
})
const filteredEligibleApplicants = computed(() => {
  const jobKey = String(form.selectedJobKey || "").trim()
  if (!jobKey) return []
  return eligibleApplicants.value
    .filter((entry) => String(entry.jobId || entry.jobTitle || "").trim() === jobKey)
    .sort((a, b) => a.applicantName.localeCompare(b.applicantName))
})
const selectedApplicationSchedules = computed(() => {
  const appId = String(form.applicationId || "").trim()
  if (!appId) return []
  return schedules.value.filter((item) => String(item.applicationId || "").trim() === appId)
})
const hasCompletedInitialInterview = computed(() => {
  return selectedApplicationSchedules.value.some(
    (item) => item.interviewType === "initial" && normalizeScheduleStatus(item.scheduleStatus) === "completed"
  )
})
const availableInterviewTypeOptions = computed(() => {
  if (hasCompletedInitialInterview.value) {
    return [{ value: "final", label: "Final Interview" }]
  }
  return [{ value: "initial", label: "Initial Interview" }]
})
const scheduledEntries = computed(() => {
  return schedules.value.filter((item) => normalizeScheduleStatus(item.scheduleStatus) === "scheduled")
})
const scheduledDateCounts = computed(() => {
  return scheduledEntries.value.reduce((acc, item) => {
    const key = toDateKey(item.scheduledAt)
    if (!key) return acc
    acc[key] = (acc[key] || 0) + 1
    return acc
  }, {})
})
const calendarMonthLabel = computed(() => {
  const date = calendarBaseDate.value
  return new Intl.DateTimeFormat(undefined, { month: "long", year: "numeric" }).format(date)
})
const selectedCalendarLabel = computed(() => {
  if (!selectedCalendarDateKey.value) return "Select a date"
  const date = new Date(`${selectedCalendarDateKey.value}T00:00:00`)
  if (Number.isNaN(date.getTime())) return selectedCalendarDateKey.value
  return new Intl.DateTimeFormat(undefined, { weekday: "long", month: "long", day: "numeric", year: "numeric" }).format(date)
})
const selectedDaySchedules = computed(() => {
  const key = selectedCalendarDateKey.value
  if (!key) return []
  return scheduledEntries.value
    .filter((item) => toDateKey(item.scheduledAt) === key)
    .sort((a, b) => new Date(a.scheduledAt).getTime() - new Date(b.scheduledAt).getTime())
})
const todayDateKey = computed(() => toDateKey(new Date()))
const minScheduleDateTime = computed(() => {
  const now = new Date()
  now.setSeconds(0, 0)
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, "0")
  const day = String(now.getDate()).padStart(2, "0")
  const hours = String(now.getHours()).padStart(2, "0")
  const minutes = String(now.getMinutes()).padStart(2, "0")
  return `${year}-${month}-${day}T${hours}:${minutes}`
})
const calendarDays = computed(() => {
  const base = calendarBaseDate.value
  const year = base.getFullYear()
  const month = base.getMonth()
  const firstOfMonth = new Date(year, month, 1)
  const startOffset = firstOfMonth.getDay()
  const gridStart = new Date(year, month, 1 - startOffset)
  const todayKey = toDateKey(new Date())

  return Array.from({ length: 42 }, (_, index) => {
    const day = new Date(gridStart)
    day.setDate(gridStart.getDate() + index)
    const dateKey = toDateKey(day)
    return {
      key: `${dateKey}-${index}`,
      dateKey,
      label: day.getDate(),
      isCurrentMonth: day.getMonth() === month,
      isToday: dateKey === todayKey,
      isSelected: dateKey === selectedCalendarDateKey.value,
      isPastDate: dateKey < todayKey,
      scheduleCount: scheduledDateCounts.value[dateKey] || 0,
    }
  })
})

function toDateKey(value) {
  if (!value) return ""
  const date = value instanceof Date ? value : new Date(String(value))
  if (Number.isNaN(date.getTime())) return ""
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, "0")
  const day = String(date.getDate()).padStart(2, "0")
  return `${year}-${month}-${day}`
}

function toTitle(value) {
  const raw = String(value || "").trim().toLowerCase()
  if (!raw) return "Unknown"
  return `${raw.charAt(0).toUpperCase()}${raw.slice(1)}`
}

function isApplicantAccepted(status) {
  const normalized = String(status || "").trim().toLowerCase()
  // Keep applicants visible in scheduler after initial interview flow
  // so HR can proceed to final interview scheduling.
  return ["accepted", "hired", "approved", "interviewed", "interview", "for interview", "interview_scheduled"].includes(normalized)
}

function formatDateTime(value) {
  if (!value) return "-"
  const date = new Date(String(value))
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleString()
}

function formatTime(value) {
  if (!value) return "-"
  const date = new Date(String(value))
  if (Number.isNaN(date.getTime())) return "-"
  return date.toLocaleTimeString([], { hour: "numeric", minute: "2-digit" })
}

function shiftCalendarMonth(offset) {
  const current = calendarBaseDate.value
  calendarBaseDate.value = new Date(current.getFullYear(), current.getMonth() + offset, 1)
}

function selectCalendarDay(day) {
  if (!day?.dateKey || day.isPastDate) return
  selectedCalendarDateKey.value = day.dateKey

  const currentTime = form.scheduledAt.includes("T")
    ? form.scheduledAt.slice(11, 16)
    : "09:00"
  form.scheduledAt = `${day.dateKey}T${currentTime}`
}

function notify(text, color = "#2563eb", noTimer = false) {
  Toastify({
    text,
    backgroundColor: color,
    duration: 2600,
    gravity: "top",
    position: "right",
    close: true,
    className: noTimer ? "toast-no-timer" : "",
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

function resolveScheduleListParams() {
  const companyId = String(localStorage.getItem("companyId") || "").trim()
  const createdByUid = String(
    localStorage.getItem("userUid") ||
      localStorage.getItem("uid") ||
      ""
  ).trim()

  if (companyId) return { companyId }
  if (createdByUid) return { createdByUid }
  return {}
}

function resolveJobListParams() {
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

async function loadVisibleJobIds() {
  try {
    const response = await api.get("/jobs", { params: resolveJobListParams() })
    const rows = Array.isArray(response?.data) ? response.data : []
    return new Set(rows.map((row) => String(row?.id || row?.jobId || "").trim()).filter(Boolean))
  } catch {
    return new Set()
  }
}

async function loadApplicationJobIdMap() {
  try {
    const response = await api.get("/applications", { params: resolveListParams() })
    const rows = Array.isArray(response?.data) ? response.data : []
    return new Map(
      rows
        .map((row) => [String(row?.id || "").trim(), String(row?.jobId || "").trim()])
        .filter(([applicationId, jobId]) => Boolean(applicationId) && Boolean(jobId))
    )
  } catch {
    return new Map()
  }
}

function isScheduleLinkedToExistingJob(row, applicationJobIdMap, visibleJobIds) {
  if (!(visibleJobIds instanceof Set)) return false
  const applicationId = String(row?.applicationId || "").trim()
  const inferredJobId = applicationId ? String(applicationJobIdMap.get(applicationId) || "").trim() : ""
  // Hide ghost schedules with missing/deleted job linkage.
  if (!inferredJobId) return false
  return visibleJobIds.has(inferredJobId)
}

async function loadApplicants() {
  loadingApplicants.value = true
  try {
    const response = await api.get("/applications", { params: resolveListParams() })
    const rows = Array.isArray(response?.data) ? response.data : []
    applicants.value = rows.map((row) => ({
      id: String(row?.id || ""),
      jobId: String(row?.jobId || ""),
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

async function loadSchedules() {
  try {
    const [response, applicationJobIdMap, visibleJobIds] = await Promise.all([
      api.get("/interview-schedules", { params: resolveScheduleListParams() }),
      loadApplicationJobIdMap(),
      loadVisibleJobIds(),
    ])
    const rows = Array.isArray(response?.data) ? response.data : []
    schedules.value = rows.map((row) => ({
      id: String(row?.id || ""),
      applicationId: String(row?.applicationId || ""),
      applicantName: String(row?.applicantName || "Applicant"),
      applicantEmail: String(row?.applicantEmail || ""),
      applicantId: String(row?.applicantId || ""),
      jobTitle: String(row?.jobTitle || "Untitled Job"),
      interviewType: String(row?.interviewType || "initial"),
      scheduledAt: String(row?.scheduledAt || ""),
      mode: String(row?.mode || "in-person"),
      locationOrLink: String(row?.locationOrLink || ""),
      interviewer: String(row?.interviewer || ""),
      notes: String(row?.notes || ""),
      scheduleStatus: normalizeScheduleStatus(row?.scheduleStatus),
      companyId: String(row?.companyId || ""),
      companyName: String(row?.companyName || ""),
      createdByUid: String(row?.createdByUid || ""),
      createdAt: String(row?.createdAt || ""),
      updatedAt: String(row?.updatedAt || ""),
    }))
    .filter((row) => isScheduleLinkedToExistingJob(row, applicationJobIdMap, visibleJobIds))
  } catch (err) {
    console.error(err)
    notify(err?.response?.data?.message || "Failed to load interview schedules.", "#dc2626")
    schedules.value = []
  }
}

function resetForm() {
  form.selectedJobKey = ""
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
  if (creatingSchedule.value) return
  formError.value = ""
  const selected = filteredEligibleApplicants.value.find((entry) => entry.id === form.applicationId)
  if (!selected) {
    formError.value = "Please select an accepted applicant for the selected job."
    return
  }
  if (!availableInterviewTypeOptions.value.some((opt) => opt.value === form.interviewType)) {
    formError.value = "Invalid interview type for this applicant stage."
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
      normalizeScheduleStatus(entry.scheduleStatus) !== "cancelled"
  )

  if (duplicate) {
    formError.value = `A scheduled ${form.interviewType} interview already exists for this applicant.`
    return
  }

  const record = {
    applicationId: selected.id,
    applicantName: selected.applicantName,
    applicantEmail: selected.applicantEmail,
    applicantId: selected.applicantId,
    jobTitle: selected.jobTitle,
    companyId: String(localStorage.getItem("companyId") || "").trim(),
    companyName: String(localStorage.getItem("companyName") || "").trim(),
    createdByUid: String(localStorage.getItem("userUid") || localStorage.getItem("uid") || "").trim(),
    interviewType: form.interviewType,
    scheduledAt: new Date(timestamp).toISOString(),
    mode: form.mode,
    locationOrLink: form.locationOrLink,
    interviewer: form.interviewer,
    notes: form.notes,
    scheduleStatus: "scheduled",
  }

  creatingSchedule.value = true
  try {
    const response = await api.post("/interview-schedules", record)
    const created = response?.data || record
    await loadSchedules()
    selectedCalendarDateKey.value = toDateKey(created.scheduledAt || record.scheduledAt)
    calendarBaseDate.value = new Date(created.scheduledAt || record.scheduledAt)
    resetForm()
    notify("Interview schedule created.", "#16a34a", true)
  } catch (err) {
    console.error(err)
    formError.value = err?.response?.data?.message || "Failed to create schedule."
    return
  } finally {
    creatingSchedule.value = false
  }

  // Keep application state aligned with interview flow.
  try {
    await api.put(`/applications/${selected.id}`, { status: "interviewed" })
  } catch (err) {
    console.error(err)
  }
}

async function updateScheduleStatus(id, nextStatus) {
  try {
    await api.put(`/interview-schedules/${id}`, { scheduleStatus: nextStatus })
    await loadSchedules()
    notify(`Interview marked as ${toTitle(nextStatus)}.`, nextStatus === "completed" ? "#16a34a" : "#f59e0b")
  } catch (err) {
    console.error(err)
    notify(err?.response?.data?.message || "Failed to update schedule.", "#dc2626")
  }
}

async function deleteSchedule(id) {
  try {
    await api.delete(`/interview-schedules/${id}`)
    await loadSchedules()
    notify("Interview schedule deleted.", "#dc2626")
  } catch (err) {
    console.error(err)
    notify(err?.response?.data?.message || "Failed to delete schedule.", "#dc2626")
  }
}

onMounted(async () => {
  await loadSchedules()
  const upcomingScheduled = [...scheduledEntries.value]
    .sort((a, b) => new Date(a.scheduledAt).getTime() - new Date(b.scheduledAt).getTime())[0]

  if (upcomingScheduled?.scheduledAt) {
    const focusDate = new Date(String(upcomingScheduled.scheduledAt))
    calendarBaseDate.value = new Date(focusDate.getFullYear(), focusDate.getMonth(), 1)
    selectedCalendarDateKey.value = toDateKey(focusDate)
  } else {
    const now = new Date()
    calendarBaseDate.value = new Date(now.getFullYear(), now.getMonth(), 1)
    selectedCalendarDateKey.value = toDateKey(now)
  }
  await loadApplicants()
})

watch(
  () => form.selectedJobKey,
  () => {
    form.applicationId = ""
  }
)

watch(
  () => form.applicationId,
  () => {
    const firstAllowed = availableInterviewTypeOptions.value[0]?.value || "initial"
    form.interviewType = firstAllowed
  }
)
</script>

<style scoped>
.content {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.top-layout {
  display: grid;
  grid-template-columns: minmax(0, 2.35fr) minmax(240px, 0.75fr);
  gap: 14px;
  align-items: start;
}

.main-column {
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

.calendar-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.calendar-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.calendar-head h3 {
  margin: 0;
}

.calendar-nav {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.icon-btn {
  width: 30px;
  height: 30px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #ffffff;
  cursor: pointer;
  color: #0f172a;
  font-size: 18px;
  line-height: 1;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
  gap: 6px;
}

.week-grid span {
  text-align: center;
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
}

.day-cell {
  min-height: 42px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #f8fafc;
  cursor: pointer;
  padding: 4px 6px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 4px;
}

.day-cell.muted {
  opacity: 0.45;
}

.day-cell.today {
  border-color: #2563eb;
}

.day-cell.selected {
  background: #eff6ff;
  border-color: #3b82f6;
}

.day-cell.busy small {
  background: #2563eb;
  color: #ffffff;
  border-radius: 999px;
  font-size: 10px;
  min-width: 18px;
  line-height: 18px;
  text-align: center;
  padding: 0 4px;
}

.day-cell.past {
  opacity: 0.35;
  cursor: not-allowed;
}

.day-number {
  font-size: 12px;
  font-weight: 600;
  color: #0f172a;
}

.calendar-caption {
  margin: 0;
  font-size: 12px;
  color: #64748b;
}

.day-list {
  border-top: 1px solid #e5e7eb;
  padding-top: 10px;
}

.day-list h4 {
  margin: 0 0 8px;
  font-size: 13px;
}

.day-list ul {
  margin: 0;
  padding-left: 16px;
  display: grid;
  gap: 6px;
}

.day-list li {
  font-size: 12px;
  color: #334155;
}

.calendar-empty {
  margin: 0;
  font-size: 12px;
  color: #64748b;
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
  justify-content: center;
  align-items: center;
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
  background: #16a34a;
}

.btn.ghost {
  background: #16a34a;
}

.actions .btn {
  padding: 6px 10px;
  font-size: 12px;
  width: 140px;
  min-width: 140px;
  flex: 0 0 140px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
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
  .top-layout {
    grid-template-columns: 1fr;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .refresh-btn {
    width: 100%;
  }
}
</style>
