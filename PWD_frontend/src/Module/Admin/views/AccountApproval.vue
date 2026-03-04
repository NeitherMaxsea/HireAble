<template>
  <div class="approval-page">
    <div class="header">
      <div>
        <h2>Account Approval</h2>
        <p>Review applicant and employer registrations before activating access.</p>
      </div>
      <button class="refresh-btn" :disabled="loading" @click="loadItems">
        {{ loading ? "Refreshing..." : "Refresh" }}
      </button>
    </div>

    <div class="toolbar">
      <input v-model.trim="search" type="text" placeholder="Search name/email/company..." />
      <select v-model="roleFilter">
        <option value="all">All Roles</option>
        <option value="applicant">Applicant</option>
        <option value="company_admin">Employer</option>
      </select>
      <select v-model="statusFilter">
        <option value="pending_review">Pending Review</option>
        <option value="rejected">Rejected</option>
        <option value="active">Approved</option>
        <option value="all">All Statuses</option>
      </select>
    </div>

    <div class="table-wrap">
      <table v-if="filteredItems.length">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Profile / Company</th>
            <th>Submitted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in filteredItems" :key="item.id">
            <td>
              <strong>{{ item.name || item.username || "User" }}</strong>
              <div class="muted">{{ item.disability || item.companyIndustry || "-" }}</div>
            </td>
            <td>{{ item.email || "-" }}</td>
            <td><span class="pill role">{{ item.roleLabel }}</span></td>
            <td><span class="pill" :class="item.statusClass">{{ item.statusLabel }}</span></td>
            <td>
              <template v-if="item.role === 'applicant'">
                {{
                  item.pendingOtpVerification
                    ? "Waiting for OTP verification"
                    : item.profileCompleted
                    ? "Applicant profile submitted"
                    : "Onboarding not finished"
                }}
              </template>
              <template v-else>
                {{ item.pendingOtpVerification ? "Waiting for OTP verification" : (item.companyName || "-") }}
              </template>
            </td>
            <td>{{ formatDate(item.createdAt || item.updatedAt) }}</td>
            <td class="actions">
              <button class="btn view" @click="openDetails(item)">View</button>
              <template v-if="!isApprovedStatus(item.status)">
                <button class="btn accept" :disabled="busyId===item.id || item.canDecide === false" @click="decide(item,'accept')">Accept</button>
                <button class="btn reject" :disabled="busyId===item.id || item.canDecide === false" @click="openReject(item)">Reject</button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-else class="empty">{{ loading ? "Loading..." : errorMessage || "No accounts found." }}</div>
    </div>

    <div v-if="detailItem" class="modal-overlay" @click.self="detailItem = null">
      <div class="modal-card detail-card">
        <div class="modal-head">
          <h3>Registration Details</h3>
          <button class="close-btn" @click="detailItem = null">x</button>
        </div>
        <div class="modal-body">
          <div class="detail-grid">
            <p><strong>Name:</strong> {{ detailItem.name || detailItem.username || "-" }}</p>
            <p><strong>Email:</strong> {{ detailItem.email || "-" }}</p>
            <p><strong>Role:</strong> {{ detailItem.roleLabel }}</p>
            <p><strong>Status:</strong> {{ detailItem.statusLabel }}</p>
            <p><strong>OTP Verification:</strong> {{ detailItem.pendingOtpVerification ? "Pending" : "Verified" }}</p>
            <p><strong>Reviewed By:</strong> {{ detailItem.reviewedByEmail || "-" }}</p>
            <p><strong>Reviewed At:</strong> {{ formatDate(detailItem.reviewedAt) }}</p>
          </div>

          <template v-if="detailItem.role === 'applicant'">
            <div class="detail-section">
              <h4>Applicant Information</h4>
              <div class="detail-grid">
                <p><strong>Disability:</strong> {{ detailItem.disability || detailItem.onboardingData?.pwdCategory || "-" }}</p>
                <p><strong>PWD ID Number:</strong> {{ detailItem.onboardingData?.pwdIdNumber || "-" }}</p>
                <p><strong>Preferred Role:</strong> {{ detailItem.onboardingData?.preferredRole || "-" }}</p>
                <p><strong>Work Mode:</strong> {{ detailItem.onboardingData?.workMode || "-" }}</p>
              </div>
              <div class="image-grid">
                <div class="image-box">
                  <h5>Profile Picture</h5>
                  <img v-if="applicantProfilePhoto(detailItem)" :src="applicantProfilePhoto(detailItem)" alt="Profile" />
                  <p v-else class="muted">No profile picture uploaded.</p>
                </div>
                <div class="image-box">
                  <h5>PWD ID Picture</h5>
                  <img v-if="detailItem.onboardingData?.pwdIdImageUrl" :src="detailItem.onboardingData.pwdIdImageUrl" alt="PWD ID" />
                  <p v-else class="muted">No PWD ID image uploaded.</p>
                </div>
              </div>
            </div>
          </template>

          <template v-else>
            <div class="detail-section">
              <h4>Employer Information</h4>
              <div class="detail-grid">
                <p><strong>Company Name:</strong> {{ detailItem.companyName || "-" }}</p>
                <p><strong>Company ID:</strong> {{ detailItem.companyId || "-" }}</p>
                <p><strong>Address:</strong> {{ detailItem.companyAddress || "-" }}</p>
                <p><strong>Industry:</strong> {{ detailItem.companyIndustry || "-" }}</p>
              </div>
            </div>
          </template>

          <div v-if="detailItem.reviewRejectionReason" class="detail-section">
            <h4>Rejection Reason</h4>
            <p>{{ detailItem.reviewRejectionReason }}</p>
          </div>
        </div>
      </div>
    </div>

    <div v-if="rejectModalItem" class="modal-overlay" @click.self="closeReject">
      <div class="modal-card">
        <div class="modal-head">
          <h3>Reject Registration</h3>
          <button class="close-btn" @click="closeReject">x</button>
        </div>
        <div class="modal-body">
          <p>Provide the reason for rejecting <strong>{{ rejectModalItem.email }}</strong>.</p>
          <textarea v-model.trim="rejectReason" rows="4" placeholder="Type rejection reason..."></textarea>
        </div>
        <div class="modal-actions">
          <button class="btn cancel" @click="closeReject">Cancel</button>
          <button class="btn reject" :disabled="busyId===rejectModalItem.id || !rejectReason" @click="decide(rejectModalItem,'reject',rejectReason)">
            {{ busyId===rejectModalItem.id ? "Submitting..." : "Reject" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import api from "@/services/api"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"

const items = ref([])
const loading = ref(false)
const errorMessage = ref("")
const search = ref("")
const roleFilter = ref("all")
const statusFilter = ref("pending_review")
const busyId = ref("")
const detailItem = ref(null)
const rejectModalItem = ref(null)
const rejectReason = ref("")

const toast = (text, bg = "#0f172a", noTimer = false) => {
  Toastify({
    text,
    backgroundColor: bg,
    duration: 2800,
    gravity: "top",
    position: "right",
    close: true,
    className: noTimer ? "toast-no-timer" : "",
  }).showToast()
}

const filteredItems = computed(() => {
  const q = search.value.toLowerCase()
  return items.value.filter((row) => {
    const roleOk = roleFilter.value === "all" || row.role === roleFilter.value
    const statusOk =
      statusFilter.value === "all" ||
      row.status === statusFilter.value ||
      (statusFilter.value === "active" && row.status === "inactive")
    const textOk = !q ||
      String(row.name || "").toLowerCase().includes(q) ||
      String(row.email || "").toLowerCase().includes(q) ||
      String(row.companyName || "").toLowerCase().includes(q)
    return roleOk && statusOk && textOk
  })
})

function normalizeStatusLabel(value) {
  const s = String(value || "").trim().toLowerCase()
  if (s === "pending_review") return "Pending Review"
  if (s === "active") return "Approved"
  if (s === "inactive") return "Approved"
  if (s === "rejected") return "Rejected"
  return s || "Unknown"
}

function normalizeRoleLabel(value) {
  const r = String(value || "").trim().toLowerCase()
  if (r === "company_admin") return "Employer"
  if (r === "applicant") return "Applicant"
  return r || "User"
}

function toStatusClass(value) {
  const s = String(value || "").toLowerCase()
  if (s === "active") return "ok"
  if (s === "inactive") return "ok"
  if (s === "rejected") return "bad"
  return "warn"
}

function isApprovedStatus(value) {
  const s = String(value || "").toLowerCase()
  return s === "active" || s === "inactive"
}

function formatDate(value) {
  if (!value) return "-"
  const d = new Date(String(value))
  if (Number.isNaN(d.getTime())) return String(value)
  return d.toLocaleString()
}

function applicantProfilePhoto(row) {
  return row.photoURL || row.onboardingData?.profilePictureUrl || ""
}

async function loadItems() {
  loading.value = true
  errorMessage.value = ""
  try {
    const response = await api.get("/admin/account-approvals", { params: { status: statusFilter.value } })
    const rows = Array.isArray(response?.data) ? response.data : []
    items.value = rows.map((row) => ({
      ...row,
      roleLabel: normalizeRoleLabel(row.role),
      statusLabel: normalizeStatusLabel(row.status),
      statusClass: toStatusClass(row.status),
    }))
  } catch (err) {
    console.error(err)
    errorMessage.value = err?.response?.data?.message || "Failed to load account approvals."
  } finally {
    loading.value = false
  }
}

function openDetails(item) {
  detailItem.value = item
}

function openReject(item) {
  rejectModalItem.value = item
  rejectReason.value = ""
}

function closeReject() {
  rejectModalItem.value = null
  rejectReason.value = ""
}

async function decide(item, action, reason = "") {
  if (!item?.id) return
  if (item.canDecide === false) {
    toast("This registration is still waiting for OTP verification.", "#f59e0b")
    return
  }
  busyId.value = item.id
  try {
    const reviewerEmail = String(localStorage.getItem("userEmail") || localStorage.getItem("userName") || "system-admin").trim()
    const response = await api.put(`/admin/account-approvals/${item.id}`, {
      action,
      reason,
      reviewedByEmail: reviewerEmail,
    })
    const updated = response?.data || {}
    const normalized = {
      ...updated,
      roleLabel: normalizeRoleLabel(updated.role),
      statusLabel: normalizeStatusLabel(updated.status),
      statusClass: toStatusClass(updated.status),
    }
    items.value = items.value.map((row) => (row.id === item.id ? normalized : row))
    if (detailItem.value?.id === item.id) detailItem.value = normalized
    if (rejectModalItem.value?.id === item.id) closeReject()
    toast(
      action === "accept" ? "Account approved and email sent." : "Account rejected and email sent.",
      action === "accept" ? "#16a34a" : "#dc2626",
      action === "accept"
    )
  } catch (err) {
    console.error(err)
    toast(err?.response?.data?.message || "Failed to update account approval.", "#dc2626")
  } finally {
    busyId.value = ""
  }
}

onMounted(() => {
  void loadItems()
})

watch(statusFilter, () => {
  void loadItems()
})
</script>

<style scoped>
.approval-page{display:flex;flex-direction:column;gap:14px}
.header{display:flex;justify-content:space-between;align-items:flex-end;gap:12px}
.header h2{margin:0;color:#134e4a}
.header p{margin:4px 0 0;color:#0f766e;font-size:13px}
.refresh-btn{border:0;border-radius:10px;padding:9px 14px;background:#0d9488;color:#fff;font-weight:600;cursor:pointer}
.toolbar{display:flex;gap:10px;flex-wrap:wrap}
.toolbar input,.toolbar select{border:1px solid #cde8e5;border-radius:10px;padding:9px 12px;background:#fff}
.toolbar input{flex:1 1 260px;min-width:220px}
.table-wrap{background:#fff;border:1px solid #cde8e5;border-radius:12px;padding:12px;overflow:auto}
table{width:100%;border-collapse:collapse}
th,td{padding:10px 8px;border-bottom:1px solid #e7f2f1;text-align:left;vertical-align:top;font-size:13px}
th{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.4px}
.muted{color:#64748b;font-size:12px}
.pill{display:inline-flex;align-items:center;border-radius:999px;padding:4px 10px;font-size:11px;font-weight:700;border:1px solid #d1d5db}
.pill.role{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.pill.ok{background:#ecfdf5;border-color:#bbf7d0;color:#166534}
.pill.bad{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.pill.warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
.actions{display:flex;gap:6px;flex-wrap:wrap}
.btn{border:0;border-radius:8px;padding:6px 10px;font-size:12px;font-weight:700;cursor:pointer}
.btn.view{background:#e2e8f0;color:#0f172a}
.btn.accept{background:#16a34a;color:#fff}
.btn.reject{background:#dc2626;color:#fff}
.btn.cancel{background:#e5e7eb;color:#111827}
.empty{padding:14px;color:#64748b}
.modal-overlay{position:fixed;inset:0;background:rgba(2,6,23,.45);display:grid;place-items:center;padding:16px;z-index:2000}
.modal-card{width:min(760px,100%);background:#fff;border-radius:14px;border:1px solid #dbe7e6;box-shadow:0 24px 48px rgba(2,6,23,.18);display:flex;flex-direction:column}
.detail-card{width:min(920px,100%)}
.modal-head{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid #e5efef}
.modal-head h3{margin:0;font-size:16px;color:#134e4a}
.close-btn{border:0;background:#f1f5f9;border-radius:8px;width:30px;height:30px;cursor:pointer}
.modal-body{padding:14px;display:grid;gap:14px;max-height:70vh;overflow:auto}
.modal-actions{display:flex;justify-content:flex-end;gap:8px;padding:12px 14px;border-top:1px solid #e5efef}
.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 14px}
.detail-grid p{margin:0;font-size:13px}
.detail-section{border:1px solid #e5efef;border-radius:12px;padding:12px}
.detail-section h4,.detail-section h5{margin:0 0 8px;color:#134e4a}
.image-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.image-box{border:1px dashed #cbd5e1;border-radius:10px;padding:10px}
.image-box img{width:100%;max-height:260px;object-fit:contain;border-radius:8px;background:#f8fafc}
textarea{width:100%;border:1px solid #cde8e5;border-radius:10px;padding:10px;resize:vertical}
@media (max-width: 860px){.detail-grid,.image-grid{grid-template-columns:1fr}.header{flex-direction:column;align-items:flex-start}}
</style>
