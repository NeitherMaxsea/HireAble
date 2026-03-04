<template>
  <section class="content">
    <div class="page-header">
      <div>
        <h2>Applicant Lists</h2>
        <p class="subtitle">Review applications from your job posts and decide who moves forward.</p>
      </div>
      <button class="refresh-btn" :disabled="loading" @click="loadApplications">
        {{ loading ? "Refreshing..." : "Refresh" }}
      </button>
    </div>

    <div class="filters">
      <input
        v-model.trim="search"
        type="text"
        class="search"
        placeholder="Search by applicant, email, or position"
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
            <th>Actions</th>
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
            <td class="actions-cell">
              <button
                class="btn details"
                :disabled="busyId === entry.id"
                @click="openDetails(entry)"
              >
                Details
              </button>
              <button
                class="btn accept"
                :disabled="isStatusLocked(entry.status) || busyId === entry.id"
                @click="updateStatus(entry, 'accepted')"
              >
                Accept
              </button>
              <button
                class="btn reject"
                :disabled="isStatusLocked(entry.status) || busyId === entry.id"
                @click="openRejectModal(entry)"
              >
                Reject
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty">
        {{ loading ? "Loading applicants..." : "No applicants found." }}
      </div>
    </div>

    <transition name="fade">
      <div v-if="showDetailsModal && selectedApplicant" class="modal-overlay" @click.self="closeDetails">
        <div class="modal-card">
          <div class="modal-head">
            <h3>Applicant Details</h3>
            <button class="icon-btn" type="button" @click="closeDetails">x</button>
          </div>

          <div class="modal-body">
            <p v-if="detailsLoading" class="details-loading">Loading applicant profile...</p>
            <div class="details-grid">
              <div class="detail-item">
                <span class="label">Full Name</span>
                <strong>{{ selectedApplicant.applicantName }}</strong>
              </div>
              <div class="detail-item">
                <span class="label">Email</span>
                <strong>{{ selectedApplicant.applicantEmail }}</strong>
              </div>
              <div class="detail-item">
                <span class="label">Applicant ID</span>
                <strong>{{ selectedApplicant.applicantId }}</strong>
              </div>
              <div class="detail-item">
                <span class="label">Job Title</span>
                <strong>{{ selectedApplicant.jobTitle }}</strong>
              </div>
              <div class="detail-item">
                <span class="label">Applied At</span>
                <strong>{{ selectedApplicant.appliedAtLabel }}</strong>
              </div>
              <div class="detail-item">
                <span class="label">Status</span>
                <strong>{{ toTitle(selectedApplicant.status) }}</strong>
              </div>
              <div class="detail-item">
                <span class="label">Phone</span>
                <strong>{{ selectedApplicant.phone || "-" }}</strong>
              </div>
              <div class="detail-item">
                <span class="label">Address</span>
                <strong>{{ selectedApplicant.address || "-" }}</strong>
              </div>
              <div class="detail-item full">
                <span class="label">Education</span>
                <strong>{{ selectedApplicant.education || "-" }}</strong>
              </div>
              <div class="detail-item full">
                <span class="label">PWD Category</span>
                <strong>{{ selectedApplicant.pwdCategory || "-" }}</strong>
              </div>
              <div v-if="selectedApplicant.status === 'rejected'" class="detail-item full rejection-detail">
                <span class="label">Rejection Reason</span>
                <strong>{{ selectedApplicant.rejectionReason || "No rejection reason provided." }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="showRejectModal && rejectTargetApplicant" class="modal-overlay" @click.self="closeRejectModal">
        <div class="modal-card reject-modal-card">
          <div class="modal-head">
            <h3>Reject Application</h3>
            <button class="icon-btn" type="button" @click="closeRejectModal">x</button>
          </div>

          <div class="modal-body">
            <p class="reject-copy">
              Please state the reason for rejecting <strong>{{ rejectTargetApplicant.applicantName }}</strong>.
              The applicant will see this feedback.
            </p>
            <textarea
              v-model.trim="rejectReasonDraft"
              class="reject-textarea"
              rows="4"
              placeholder="Enter rejection reason"
            ></textarea>
          </div>

          <div class="reject-actions">
            <button class="btn details" type="button" :disabled="busyId === rejectTargetApplicant.id" @click="closeRejectModal">
              Cancel
            </button>
            <button class="btn reject" type="button" :disabled="busyId === rejectTargetApplicant.id" @click="confirmRejectStatus">
              {{ busyId === rejectTargetApplicant.id ? "Saving..." : "Submit Rejection" }}
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

const applicants = ref([])
const search = ref("")
const statusFilter = ref("all")
const loading = ref(false)
const busyId = ref("")
const errorMessage = ref("")
const showDetailsModal = ref(false)
const selectedApplicant = ref(null)
const detailsLoading = ref(false)
const showRejectModal = ref(false)
const rejectTargetId = ref("")
const rejectReasonDraft = ref("")

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
  const status = String(value || "").trim().toLowerCase()
  return status || "pending"
}

const isStatusLocked = (status) => {
  const normalized = normalizeStatus(status)
  return normalized === "accepted" || normalized === "rejected"
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

const rejectTargetApplicant = computed(() =>
  applicants.value.find((item) => item.id === rejectTargetId.value) || null
)

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
          jobId: String(row?.jobId || ""),
          jobTitle: String(row?.jobTitle || "Untitled Job"),
          applicantId: String(row?.applicantId || "N/A"),
          applicantName: String(row?.applicantName || "Applicant"),
          applicantEmail: String(row?.applicantEmail || "No email"),
          status,
          rejectionReason: String(row?.rejectionReason || ""),
          appliedAt: row?.appliedAt || row?.createdAt || null,
          appliedAtLabel: formatDateTime(row?.appliedAt || row?.createdAt || null),
          phone: "",
          address: "",
          education: "",
          pwdCategory: "",
        }
      })
      .sort((a, b) => new Date(b.appliedAt || 0) - new Date(a.appliedAt || 0))
  } catch (err) {
    console.error(err)
    errorMessage.value = err?.response?.data?.message || "Failed to load applicants."
  } finally {
    loading.value = false
  }
}

async function updateStatus(entry, status, rejectionReason = "") {
  if (!entry?.id) return false
  if (isStatusLocked(entry.status)) return false

  busyId.value = entry.id
  try {
    const payload = { status }
    if (status === "rejected") payload.rejectionReason = String(rejectionReason || "").trim()
    await api.put(`/applications/${entry.id}`, payload)
    await loadApplications()
    notify(`Application ${status}.`, status === "accepted" ? "#16a34a" : "#dc2626")
    return true
  } catch (err) {
    console.error(err)
    notify(err?.response?.data?.message || "Failed to update status.", "#dc2626")
    return false
  } finally {
    busyId.value = ""
  }
}

function openRejectModal(entry) {
  if (!entry?.id) return
  if (isStatusLocked(entry.status) || busyId.value === entry.id) return
  rejectTargetId.value = entry.id
  rejectReasonDraft.value = String(entry.rejectionReason || "").trim()
  showRejectModal.value = true
}

function closeRejectModal() {
  if (busyId.value && busyId.value === rejectTargetId.value) return
  showRejectModal.value = false
  rejectTargetId.value = ""
  rejectReasonDraft.value = ""
}

async function confirmRejectStatus() {
  const target = rejectTargetApplicant.value
  if (!target?.id) return

  const reason = String(rejectReasonDraft.value || "").trim()
  if (!reason) {
    notify("Please provide a rejection reason.", "#dc2626")
    return
  }

  const updated = await updateStatus(target, "rejected", reason)
  if (updated) {
    closeRejectModal()
  }
}

function toDetailsFromUser(baseEntry, user) {
  const profile = user?.applicantProfile || {}
  const city = String(profile?.addressCity || "").trim()
  const province = String(profile?.addressProvince || "").trim()
  const address = [city, province].filter(Boolean).join(", ")
  return {
    ...baseEntry,
    applicantName: String(user?.name || baseEntry.applicantName || "Applicant"),
    applicantEmail: String(user?.email || baseEntry.applicantEmail || "No email"),
    phone: String(profile?.mobileNumber || user?.contact || "").trim(),
    address,
    education: String(profile?.academicLevel || "").trim(),
    pwdCategory: String(profile?.pwdCategory || user?.disability || "").trim(),
    rejectionReason: String(baseEntry?.rejectionReason || "").trim(),
  }
}

async function openDetails(entry) {
  selectedApplicant.value = {
    ...entry,
    phone: "",
    address: "",
    education: "",
    pwdCategory: "",
  }
  showDetailsModal.value = true
  detailsLoading.value = true

  try {
    const usersRes = await api.get("/users")
    const users = Array.isArray(usersRes?.data) ? usersRes.data : []
    const applicantId = String(entry?.applicantId || "").trim()
    const applicantEmail = String(entry?.applicantEmail || "").trim().toLowerCase()

    const matched = users.find((user) => {
      const id = String(user?.id || "").trim()
      const email = String(user?.email || "").trim().toLowerCase()
      if (applicantId && id === applicantId) return true
      if (applicantEmail && email === applicantEmail) return true
      return false
    })

    if (matched) {
      selectedApplicant.value = toDetailsFromUser(entry, matched)
    }
  } catch (err) {
    console.error(err)
  } finally {
    detailsLoading.value = false
  }
}

function closeDetails() {
  showDetailsModal.value = false
  selectedApplicant.value = null
  detailsLoading.value = false
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
  min-width: 160px;
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

.status-accepted {
  background: #dcfce7;
  color: #166534;
}

.status-interviewed {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.actions-cell {
  display: flex;
  gap: 8px;
}

.btn {
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  padding: 7px 10px;
  color: #ffffff;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.accept {
  background: #16a34a;
}

.reject {
  background: #dc2626;
}

.details {
  background: #2563eb;
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
  background: rgba(15, 23, 42, 0.5);
  display: grid;
  place-items: center;
  padding: 16px;
  z-index: 60;
}

.modal-card {
  width: min(760px, 100%);
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  box-shadow: 0 22px 40px rgba(15, 23, 42, 0.25);
}

.modal-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  padding: 14px 16px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-head h3 {
  margin: 0;
  font-size: 16px;
  color: #0f172a;
}

.icon-btn {
  border: none;
  background: #f1f5f9;
  border-radius: 8px;
  width: 30px;
  height: 30px;
  cursor: pointer;
}

.modal-body {
  padding: 16px;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.detail-item {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-item.full {
  grid-column: 1 / -1;
}

.detail-item .label {
  font-size: 12px;
  color: #64748b;
}

.detail-item strong {
  color: #0f172a;
  font-size: 14px;
  word-break: break-word;
}

.detail-item.rejection-detail {
  border-color: #fca5a5;
  background: #fff1f2;
}

.detail-item.rejection-detail strong {
  color: #991b1b;
  line-height: 1.5;
}

.details-loading {
  margin: 0 0 10px;
  color: #64748b;
  font-size: 13px;
}

.reject-modal-card {
  width: min(620px, 100%);
}

.reject-copy {
  margin: 0 0 10px;
  color: #334155;
  font-size: 14px;
  line-height: 1.5;
}

.reject-textarea {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  padding: 10px 12px;
  font: inherit;
  resize: vertical;
  min-height: 112px;
}

.reject-textarea:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.reject-actions {
  padding: 0 16px 16px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .refresh-btn {
    width: 100%;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }
}
</style>
