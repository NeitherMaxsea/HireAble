<template>
  <section class="company-page">
    <header class="page-header">
      <h2>Company Management</h2>
      <p>Companies and departments.</p>
    </header>

    <div v-if="loading && !companies.length" class="state-card">
      <i class="bi bi-hourglass-split"></i>
      <p>Loading companies</p>
    </div>

    <div v-else-if="loadError && !companies.length" class="state-card error">
      <i class="bi bi-exclamation-triangle"></i>
      <p>{{ loadError }}</p>
    </div>

    <div v-else-if="!companies.length" class="state-card">
      <i class="bi bi-building"></i>
      <p>No company records found.</p>
    </div>

    <div v-else class="layout-grid">
      <aside class="company-list">
        <div class="list-search">
          <i class="bi bi-search"></i>
          <input v-model.trim="companySearch" type="text" placeholder="Search company or ID" />
        </div>

        <p v-if="companySearch && !filteredCompanies.length" class="mini-empty">No matching company</p>

        <button
          v-for="company in filteredCompanies"
          :key="company.companyId"
          class="company-item"
          :class="{ active: selectedCompanyId === company.companyId }"
          @click="selectedCompanyId = company.companyId"
        >
          <div class="name-row">
            <strong>{{ company.companyName }}</strong>
            <small>{{ company.companyId }}</small>
          </div>
          <p class="email">{{ company.contactEmail }}</p>
        </button>
      </aside>

      <section class="company-detail" v-if="selectedCompany">
        <div class="detail-head">
          <h3>{{ selectedCompany.companyName }}</h3>
          <p>{{ selectedCompany.companyId }}</p>
          <p>{{ selectedCompany.contactEmail }}</p>
          <div class="company-action-row">
            <span class="company-status" :class="selectedCompany.isSuspended ? 'suspended' : 'active'">
              {{ selectedCompany.isSuspended ? "Suspended" : "Active" }}
            </span>
            <button
              type="button"
              class="company-action-btn suspend"
              :disabled="companyActionLoading"
              @click="requestToggleCompanySuspended(selectedCompany, !selectedCompany.isSuspended)"
            >
              {{ companyActionLoading ? "Processing..." : (selectedCompany.isSuspended ? "Activate Company" : "Suspend Company") }}
            </button>
            <button
              type="button"
              class="company-action-btn delete"
              :disabled="companyActionLoading"
              @click="requestDeleteCompany(selectedCompany)"
            >
              {{ companyActionLoading ? "Processing..." : "Delete Company" }}
            </button>
          </div>
        </div>

        <div class="count-grid">
          <article class="count-card admin">
            <span>Company Admin</span>
            <strong>{{ selectedCompany.companyAdminCount }}</strong>
          </article>
          <article class="count-card hr">
            <span>HR</span>
            <strong>{{ selectedCompany.hrCount }}</strong>
          </article>
          <article class="count-card operation">
            <span>Operation</span>
            <strong>{{ selectedCompany.operationCount }}</strong>
          </article>
          <article class="count-card finance">
            <span>Finance</span>
            <strong>{{ selectedCompany.financeCount }}</strong>
          </article>
        </div>

        <div class="department-section">
          <div class="section-top">
            <h4>Department Members</h4>
            <div class="member-search">
              <i class="bi bi-search"></i>
              <input
                v-model.trim="departmentSearch"
                type="text"
                placeholder="Search user"
              />
            </div>
          </div>

          <div class="department-tabs">
            <button
              v-for="tab in departmentTabs"
              :key="tab.key"
              type="button"
              class="tab-btn"
              :class="[tab.key, { active: selectedDepartment === tab.key }]"
              @click="selectDepartment(tab.key)"
            >
              <span>{{ tab.label }}</span>
              <strong>{{ getDepartmentCount(tab.key) }}</strong>
            </button>
          </div>

          <div v-if="!visibleDepartmentMembers.length" class="mini-empty large">No users in this view</div>

          <ul v-else class="member-list">
            <li v-for="member in visibleDepartmentMembers" :key="member.id" class="member-item">
              <div>
                <strong>{{ member.name }}</strong>
                <p>{{ member.email || "-" }}</p>
              </div>
              <span class="role-badge" :class="member.role">{{ formatRoleLabel(member.role) }}</span>
            </li>
          </ul>

          <button
            v-if="canLoadMoreDepartmentMembers"
            type="button"
            class="load-more"
            @click="departmentVisibleCount += DEPARTMENT_PAGE_SIZE"
          >
            More
          </button>
        </div>
      </section>
    </div>

    <div v-if="showCompanyConfirmModal && pendingCompanyAction" class="modal-overlay" @click.self="closeCompanyConfirmModal">
      <div class="modal-card">
        <div class="modal-head">
          <h3>{{ pendingCompanyAction.type === "delete" ? "Delete Company" : (pendingCompanyAction.suspend ? "Suspend Company" : "Activate Company") }}</h3>
          <button class="close-btn" type="button" @click="closeCompanyConfirmModal">x</button>
        </div>
        <div class="modal-body">
          <p v-if="pendingCompanyAction.type === 'delete'">
            Delete company <strong>{{ pendingCompanyAction.company.companyName }}</strong> and all related users? This cannot be undone.
          </p>
          <p v-else>
            Are you sure you want to {{ pendingCompanyAction.suspend ? "suspend" : "activate" }}
            company <strong>{{ pendingCompanyAction.company.companyName }}</strong>?
          </p>
        </div>
        <div class="modal-actions">
          <button class="cancel-btn" type="button" :disabled="companyActionLoading" @click="closeCompanyConfirmModal">Cancel</button>
          <button
            class="confirm-btn"
            :class="pendingCompanyAction.type === 'delete' ? 'delete' : 'suspend'"
            type="button"
            :disabled="companyActionLoading"
            @click="confirmCompanyAction"
          >
            {{ companyActionLoading ? "Processing..." : "Confirm" }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { collection, deleteDoc, doc, getDocs, onSnapshot, query, serverTimestamp, updateDoc } from "@/lib/laravel-data"
import { db } from "@/lib/client-platform"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"
import api from "@/services/api"

const companies = ref([])
const selectedCompanyId = ref("")
const loading = ref(true)
const loadError = ref("")
const unsubscribers = []
const companySearch = ref("")
const selectedDepartment = ref("company_admin")
const departmentSearch = ref("")
const DEPARTMENT_PAGE_SIZE = 8
const departmentVisibleCount = ref(DEPARTMENT_PAGE_SIZE)
const companyActionLoading = ref(false)
const showCompanyConfirmModal = ref(false)
const pendingCompanyAction = ref(null)

const departmentTabs = [
  { key: "company_admin", label: "Company Admin" },
  { key: "hr", label: "HR" },
  { key: "operation", label: "Operation" },
  { key: "finance", label: "Finance" },
]

const filteredCompanies = computed(() => {
  const term = companySearch.value.toLowerCase()
  if (!term) return companies.value
  return companies.value.filter((company) => {
    return (
      company.companyName.toLowerCase().includes(term) ||
      company.companyId.toLowerCase().includes(term)
    )
  })
})

const selectedCompany = computed(() => {
  return companies.value.find((company) => company.companyId === selectedCompanyId.value) || null
})

const departmentMembers = computed(() => {
  if (!selectedCompany.value) return []
  const members = selectedCompany.value.departmentMembers[selectedDepartment.value] || []
  const term = departmentSearch.value.toLowerCase()
  if (!term) return members
  return members.filter((member) => {
    return member.name.toLowerCase().includes(term) || member.email.toLowerCase().includes(term)
  })
})

const visibleDepartmentMembers = computed(() => {
  return departmentMembers.value.slice(0, departmentVisibleCount.value)
})

const canLoadMoreDepartmentMembers = computed(() => {
  return visibleDepartmentMembers.value.length < departmentMembers.value.length
})

onMounted(() => {
  void initCompanyData()
})

onBeforeUnmount(() => {
  unsubscribers.forEach((fn) => fn && fn())
})

async function initCompanyData() {
  loading.value = true
  loadError.value = ""

  const first = await fetchUsersOnce()
  setCompanies(first.list)
  loading.value = false

  if (!first.list.length && first.error) {
    loadError.value = "Cannot read companies. Check admin access and Laravel Rules."
  }

  subscribeUsersRealtime(first.source)
}

async function fetchUsersOnce() {
  const candidates = ["users", "Users"]
  let lastError = null

  for (const collectionName of candidates) {
    try {
      const snap = await getDocs(query(collection(db, collectionName)))
      const list = normalizeUsers(
        snap.docs.map((docSnap) => ({
          id: docSnap.id,
          collectionName,
          data: docSnap.data(),
        }))
      )
      if (list.length) {
        return { list, source: collectionName, error: null }
      }
    } catch (err) {
      lastError = err
    }
  }

  return { list: [], source: "users", error: lastError }
}

function subscribeUsersRealtime(source) {
  const targets = source === "Users" ? ["Users", "users"] : ["users", "Users"]

  targets.forEach((collectionName) => {
    const unsub = onSnapshot(
      query(collection(db, collectionName)),
      (snapshot) => {
        const list = normalizeUsers(
          snapshot.docs.map((docSnap) => ({
            id: docSnap.id,
            collectionName,
            data: docSnap.data(),
          }))
        )
        if (list.length || !companies.value.length) {
          setCompanies(list)
          loadError.value = ""
        }
      },
      () => {
        if (!companies.value.length) {
          loadError.value = "Realtime sync blocked. Showing latest available data."
        }
      }
    )

    unsubscribers.push(unsub)
  })
}

function normalizeUsers(entries) {
  return entries
    .map(({ id, collectionName, data }, index) => ({
      id: String(id || data?.uid || data?.id || `${data?.email || "user"}-${index}`),
      collectionName: String(collectionName || "users"),
      companyId: String(data?.companyId || "").trim(),
      companyName: String(data?.companyName || "").trim(),
      email: String(data?.email || "").trim().toLowerCase(),
      name: String(data?.username || data?.name || data?.email || "Unknown User").trim(),
      role: normalizeRole(data?.role),
      status: normalizeStatus(data?.status),
    }))
    .filter((user) => user.companyId && ["company_admin", "hr", "operation", "finance"].includes(user.role))
}

function normalizeRole(role) {
  const value = String(role || "").trim().toLowerCase().replace(/[\s_]+/g, " ")
  if (value === "company admin") return "company_admin"
  if (value === "hr" || value === "hr department") return "hr"
  if (value === "operation" || value === "operation department") return "operation"
  if (value === "finance" || value === "financial" || value === "financial department") return "finance"
  return value.replace(/\s+/g, "_")
}

function setCompanies(users) {
  const companyMap = new Map()

  users.forEach((user) => {
    if (!companyMap.has(user.companyId)) {
      companyMap.set(user.companyId, {
        companyId: user.companyId,
        companyName: user.companyName || `Company ${user.companyId}`,
        contactEmail: user.email || "-",
        isSuspended: false,
        companyAdminCount: 0,
        hrCount: 0,
        operationCount: 0,
        financeCount: 0,
        allMembers: [],
        departmentMembers: {
          company_admin: [],
          hr: [],
          operation: [],
          finance: [],
        },
      })
    }

    const record = companyMap.get(user.companyId)
    if (!record) return

    if (!record.contactEmail || record.contactEmail === "-") {
      record.contactEmail = user.email || "-"
    }
    record.allMembers.push(user)

    if (record.departmentMembers[user.role]) {
      record.departmentMembers[user.role].push(user)
    }

    if (user.role === "company_admin" && user.email) {
      record.companyAdminCount += 1
      record.contactEmail = user.email
    } else if (user.role === "hr") {
      record.hrCount += 1
    } else if (user.role === "operation") {
      record.operationCount += 1
    } else if (user.role === "finance") {
      record.financeCount += 1
    }
  })

  companyMap.forEach((record) => {
    const totalMembers = record.allMembers.length
    const suspendedCount = record.allMembers.filter((member) => member.status === "suspended").length
    record.isSuspended = totalMembers > 0 && suspendedCount === totalMembers
  })

  const next = Array.from(companyMap.values()).sort((a, b) =>
    a.companyName.localeCompare(b.companyName)
  )

  companies.value = next

  if (!next.length) {
    selectedCompanyId.value = ""
    return
  }

  const stillExists = next.some((company) => company.companyId === selectedCompanyId.value)
  if (!stillExists) {
    selectedCompanyId.value = next[0].companyId
  }
}

function getDepartmentCount(role) {
  if (!selectedCompany.value) return 0
  return (selectedCompany.value.departmentMembers[role] || []).length
}

function selectDepartment(role) {
  selectedDepartment.value = role
  departmentVisibleCount.value = DEPARTMENT_PAGE_SIZE
}

function formatRoleLabel(role) {
  if (role === "company_admin") return "Company Admin"
  if (role === "hr") return "HR"
  if (role === "operation") return "Operation"
  if (role === "finance") return "Finance"
  return "User"
}

function normalizeStatus(value) {
  const status = String(value || "").trim().toLowerCase()
  return status || "inactive"
}

function notify(text, color = "#16a34a", duration = 2000) {
  Toastify({
    text,
    backgroundColor: color,
    duration,
    className: "toast-no-timer",
    gravity: "top",
    position: "right",
    close: false,
    style: { "--toast-duration": `${duration}ms` },
  }).showToast()
}

async function toggleCompanySuspended(company, suspend) {
  if (!company || !Array.isArray(company.allMembers) || !company.allMembers.length) return

  companyActionLoading.value = true
  const nextStatus = suspend ? "suspended" : "active"
  const failed = []

  try {
    for (const member of company.allMembers) {
      try {
        await updateDoc(doc(db, member.collectionName || "users", member.id), {
          status: nextStatus,
          isActive: !suspend,
          updatedAt: serverTimestamp(),
        })
      } catch {
        const altCollection = (member.collectionName || "users") === "users" ? "Users" : "users"
        try {
          await updateDoc(doc(db, altCollection, member.id), {
            status: nextStatus,
            isActive: !suspend,
            updatedAt: serverTimestamp(),
          })
        } catch {
          failed.push(member.id)
        }
      }
    }

    if (failed.length > 0) {
      notify(`Some accounts failed to update (${failed.length}).`, "#dc2626", 2000)
    } else {
      notify(suspend ? "Company suspended successfully." : "Company activated successfully.", "#16a34a", 2000)
    }
  } finally {
    companyActionLoading.value = false
  }
}

async function deleteCompany(company) {
  if (!company || !Array.isArray(company.allMembers) || !company.allMembers.length) return

  companyActionLoading.value = true
  const failed = []

  try {
    const backendDeleteFailures = await purgeCompanyBackendData(company)

    for (const member of company.allMembers) {
      try {
        await deleteDoc(doc(db, member.collectionName || "users", member.id))
      } catch {
        const altCollection = (member.collectionName || "users") === "users" ? "Users" : "users"
        try {
          await deleteDoc(doc(db, altCollection, member.id))
        } catch {
          failed.push(member.id)
        }
      }
    }

    const totalFailures = failed.length + backendDeleteFailures
    if (totalFailures > 0) {
      notify(`Some related records failed to delete (${totalFailures}).`, "#dc2626")
    } else {
      notify("Company deleted successfully.", "#dc2626")
      selectedCompanyId.value = ""
    }
  } finally {
    companyActionLoading.value = false
  }
}

async function purgeCompanyBackendData(company) {
  let failures = 0
  const safeCompanyId = String(company?.companyId || "").trim()
  if (!safeCompanyId) return failures

  const companyMembers = Array.isArray(company?.allMembers) ? company.allMembers : []
  const memberUidSet = new Set(
    companyMembers
      .map((member) => String(member?.id || "").trim())
      .filter(Boolean)
  )
  const memberEmailSet = new Set(
    companyMembers
      .map((member) => String(member?.email || "").trim().toLowerCase())
      .filter(Boolean)
  )

  async function fetchList(path, params = {}) {
    try {
      const res = await api.get(path, { params })
      return Array.isArray(res?.data) ? res.data : []
    } catch {
      return []
    }
  }

  async function deleteById(path, rawId) {
    const id = String(rawId || "").trim()
    if (!id) return
    try {
      await api.delete(`${path}/${id}`)
    } catch (error) {
      const status = Number(error?.response?.status || 0)
      if (status !== 404) {
        failures += 1
      }
    }
  }

  const users = await fetchList("/users", { companyId: safeCompanyId })

  let jobs = await fetchList("/jobs", { companyId: safeCompanyId })
  if (!jobs.length) {
    const allJobs = await fetchList("/jobs")
    jobs = allJobs.filter((row) => String(row?.companyId || "").trim() === safeCompanyId)
  }
  const jobIdSet = new Set(jobs.map((row) => String(row?.id || "").trim()).filter(Boolean))

  let applications = await fetchList("/applications", { companyId: safeCompanyId })
  if (!applications.length && jobIdSet.size > 0) {
    const allApplications = await fetchList("/applications")
    applications = allApplications.filter((row) => jobIdSet.has(String(row?.jobId || "").trim()))
  }
  const applicationIdSet = new Set(applications.map((row) => String(row?.id || "").trim()).filter(Boolean))

  let schedules = await fetchList("/interview-schedules", { companyId: safeCompanyId })
  if (!schedules.length) {
    const allSchedules = await fetchList("/interview-schedules")
    schedules = allSchedules.filter((row) => {
      const rowCompanyId = String(row?.companyId || "").trim()
      const rowCreator = String(row?.createdByUid || "").trim()
      const rowApplicantEmail = String(row?.applicantEmail || "").trim().toLowerCase()
      const rowApplicationId = String(row?.applicationId || "").trim()
      return (
        rowCompanyId === safeCompanyId ||
        memberUidSet.has(rowCreator) ||
        memberEmailSet.has(rowApplicantEmail) ||
        applicationIdSet.has(rowApplicationId)
      )
    })
  }

  for (const row of schedules) {
    await deleteById("/interview-schedules", row?.id)
  }

  for (const row of applications) {
    await deleteById("/applications", row?.id)
  }

  for (const row of jobs) {
    await deleteById("/jobs", row?.id)
  }

  for (const row of users) {
    await deleteById("/users", row?.id)
  }

  return failures
}

function requestToggleCompanySuspended(company, suspend) {
  if (!company) return
  pendingCompanyAction.value = { type: "suspend", suspend, company }
  showCompanyConfirmModal.value = true
}

function requestDeleteCompany(company) {
  if (!company) return
  pendingCompanyAction.value = { type: "delete", company }
  showCompanyConfirmModal.value = true
}

function closeCompanyConfirmModal() {
  if (companyActionLoading.value) return
  showCompanyConfirmModal.value = false
  pendingCompanyAction.value = null
}

async function confirmCompanyAction() {
  const action = pendingCompanyAction.value
  if (!action?.company) return

  if (action.type === "delete") {
    await deleteCompany(action.company)
  } else {
    await toggleCompanySuspended(action.company, Boolean(action.suspend))
  }

  closeCompanyConfirmModal()
}
</script>

<style scoped>
.company-page {
  padding: 10px;
}

.page-header h2 {
  margin: 0;
}

.page-header p {
  margin: 8px 0 16px;
  color: #0f766e;
}

.layout-grid {
  display: grid;
  grid-template-columns: 320px minmax(0, 1fr);
  gap: 14px;
}

.company-list {
  background: #ffffff;
  border: 1px solid #d8ece9;
  border-radius: 12px;
  padding: 8px;
  display: grid;
  gap: 8px;
  align-content: start;
  max-height: 72vh;
  overflow: auto;
}

.list-search {
  position: sticky;
  top: 0;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #c8e8e4;
  border-radius: 10px;
  background: #f3fbfa;
  padding: 8px 10px;
}

.list-search i {
  color: #0d9488;
}

.list-search input {
  width: 100%;
  border: none;
  outline: none;
  background: transparent;
  color: #0f172a;
  font-size: 13px;
}

.company-item {
  border: 1px solid #d8ece9;
  border-radius: 10px;
  background: linear-gradient(150deg, #f1fbf9 0%, #f8fcfb 70%);
  text-align: left;
  padding: 12px;
  cursor: pointer;
}

.company-item.active {
  border-color: #86d1ca;
  background: linear-gradient(130deg, #ddf6f2 0%, #effbf9 100%);
}

.name-row {
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

.name-row strong {
  color: #134e4a;
}

.name-row small {
  color: #0f766e;
}

.email {
  margin: 8px 0 0;
  color: #334155;
  font-size: 13px;
}

.company-detail {
  background: #ffffff;
  border: 1px solid #d8ece9;
  border-radius: 12px;
  padding: 16px;
}

.detail-head h3 {
  margin: 0 0 10px;
}

.detail-head p {
  margin: 4px 0;
  color: #0f766e;
  font-size: 13px;
}

.company-action-row {
  margin-top: 10px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  align-items: center;
}

.company-status {
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 700;
}

.company-status.active {
  background: #dcfce7;
  color: #166534;
}

.company-status.suspended {
  background: #ffedd5;
  color: #9a3412;
}

.company-action-btn {
  border: none;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.company-action-btn.suspend {
  background: #fef3c7;
  color: #92400e;
}

.company-action-btn.delete {
  background: #fee2e2;
  color: #b91c1c;
}

.company-action-btn:disabled {
  opacity: .6;
  cursor: not-allowed;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 120;
  padding: 16px;
}

.modal-card {
  width: min(460px, 100%);
  background: #fff;
  border: 1px solid #d8ece9;
  border-radius: 12px;
  box-shadow: 0 20px 38px rgba(15, 23, 42, 0.25);
}

.modal-head {
  padding: 14px 16px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-head h3 {
  margin: 0;
  font-size: 16px;
  color: #0f172a;
}

.close-btn {
  width: 30px;
  height: 30px;
  border: none;
  border-radius: 999px;
  background: #f1f5f9;
  cursor: pointer;
}

.modal-body {
  padding: 14px 16px;
  color: #334155;
  font-size: 14px;
}

.modal-actions {
  padding: 0 16px 16px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.cancel-btn,
.confirm-btn {
  border: none;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.cancel-btn {
  background: #e2e8f0;
  color: #1f2937;
}

.confirm-btn.suspend {
  background: #fef3c7;
  color: #92400e;
}

.confirm-btn.delete {
  background: #fee2e2;
  color: #b91c1c;
}

.cancel-btn:disabled,
.confirm-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.count-grid {
  margin-top: 14px;
  display: grid;
  grid-template-columns: repeat(4, minmax(150px, 1fr));
  gap: 10px;
}

.count-card {
  border: 1px solid #d6ebe8;
  border-radius: 10px;
  background: #f8fcfb;
  padding: 12px;
}

.count-card span {
  display: block;
  color: #0f766e;
  font-size: 12px;
}

.count-card strong {
  display: block;
  margin-top: 6px;
  font-size: 24px;
  color: #0f3b39;
}

.count-card.admin {
  border-left: 4px solid #0d9488;
}

.count-card.hr {
  border-left: 4px solid #22c55e;
}

.count-card.operation {
  border-left: 4px solid #f59e0b;
}

.count-card.finance {
  border-left: 4px solid #f97316;
}

.department-section {
  margin-top: 14px;
  border: 1px solid #d8ece9;
  border-radius: 12px;
  padding: 12px;
  background: #fbfefe;
}

.section-top {
  display: flex;
  gap: 10px;
  justify-content: space-between;
  align-items: center;
}

.section-top h4 {
  margin: 0;
  font-size: 15px;
  color: #134e4a;
}

.member-search {
  display: flex;
  align-items: center;
  gap: 7px;
  border: 1px solid #cae9e5;
  border-radius: 9px;
  background: #f4fbfa;
  padding: 7px 9px;
  min-width: 210px;
}

.member-search i {
  color: #0d9488;
}

.member-search input {
  width: 100%;
  border: none;
  outline: none;
  background: transparent;
  font-size: 13px;
}

.department-tabs {
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(4, minmax(110px, 1fr));
  gap: 8px;
}

.tab-btn {
  border: 1px solid #d5ebe8;
  border-radius: 10px;
  background: #f7fcfb;
  color: #23413f;
  padding: 8px 10px;
  text-align: left;
  cursor: pointer;
}

.tab-btn span {
  display: block;
  font-size: 12px;
}

.tab-btn strong {
  font-size: 18px;
}

.tab-btn.active {
  border-color: #77cfc6;
  background: #ddf6f2;
}

.member-list {
  list-style: none;
  padding: 0;
  margin: 12px 0 0;
  display: grid;
  gap: 8px;
}

.member-item {
  border: 1px solid #d9ece9;
  border-radius: 10px;
  padding: 10px;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.member-item strong {
  display: block;
  color: #0f172a;
}

.member-item p {
  margin: 2px 0 0;
  color: #64748b;
  font-size: 12px;
}

.role-badge {
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

.role-badge.company_admin {
  background: #ccfbf1;
  color: #115e59;
}

.role-badge.hr {
  background: #dcfce7;
  color: #166534;
}

.role-badge.operation {
  background: #fef3c7;
  color: #92400e;
}

.role-badge.finance {
  background: #ffedd5;
  color: #9a3412;
}

.load-more {
  margin-top: 10px;
  border: 1px solid #7ecfc6;
  background: #ecf9f7;
  color: #0f766e;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.mini-empty {
  margin: 2px 0;
  color: #0f766e;
  font-size: 12px;
}

.mini-empty.large {
  margin-top: 12px;
  padding: 14px;
  text-align: center;
  border: 1px dashed #b8ddd9;
  border-radius: 10px;
  background: #f7fcfb;
}

.state-card {
  background: #ffffff;
  border: 1px solid #d8ece9;
  border-radius: 12px;
  min-height: 220px;
  display: grid;
  place-items: center;
  text-align: center;
  color: #64748b;
}

.state-card i {
  font-size: 2rem;
  margin-bottom: 8px;
}

.state-card.error {
  color: #b91c1c;
}

@media (max-width: 1024px) {
  .layout-grid {
    grid-template-columns: 1fr;
  }

  .company-list {
    max-height: 40vh;
  }

  .count-grid {
    grid-template-columns: repeat(2, minmax(130px, 1fr));
  }

  .department-tabs {
    grid-template-columns: repeat(2, minmax(120px, 1fr));
  }

  .section-top {
    flex-direction: column;
    align-items: stretch;
  }

  .member-search {
    min-width: 0;
  }
}

@media (max-width: 600px) {
  .count-grid {
    grid-template-columns: 1fr;
  }
}
</style>
