<template>
  <section class="page">
    <div class="hero">
      <div>
        <h2>Team Account Provisioning</h2>
        <p>Create secure role-based accounts for your internal staff.</p>
      </div>
      <div class="chips">
        <span class="chip">{{ companyName || "Company" }}</span>
        <span class="chip subtle">{{ companyId || "No Company ID" }}</span>
      </div>
    </div>

    <div v-if="loadError" class="error">{{ loadError }}</div>

    <div class="summary-grid">
      <div class="summary-card">
        <span>Total Employees</span>
        <strong>{{ employees.length }}</strong>
      </div>
      <div class="summary-card">
        <span>HR Department</span>
        <strong>{{ hrCount }}</strong>
      </div>
      <div class="summary-card">
        <span>Financial Department</span>
        <strong>{{ financeCount }}</strong>
      </div>
      <div class="summary-card">
        <span>Operation Department</span>
        <strong>{{ operationCount }}</strong>
      </div>
    </div>

    <div class="form-card">
      <h3>Create Employee Account</h3>
      <div class="grid">
        <div class="field">
          <label>Username</label>
          <input v-model="form.username" type="text" placeholder="Enter username" />
        </div>
        <div class="field">
          <label>Email</label>
          <input v-model="form.email" type="email" placeholder="Enter work email" />
        </div>
        <div class="field">
          <label>Role</label>
          <select v-model="form.role">
            <option value="hr">HR DEPARTMENT</option>
            <option value="operation">OPERATION DEPARTMENT</option>
            <option value="finance">FINANCIAL DEPARTMENT</option>
          </select>
        </div>
        <div class="field">
          <label>Password</label>
          <input v-model="form.tempPassword" type="password" placeholder="At least 8 characters" />
        </div>
      </div>
      <button class="create-btn" :disabled="saving || !canCreate" @click="createEmployee">
        {{ saving ? "Creating..." : "Create Employee Account" }}
      </button>

      <div v-if="lastCreated" class="created-card">
        <h4>Account Created</h4>
        <div class="created-grid">
          <div class="created-item">
            <span>Username</span>
            <strong>{{ lastCreated.username }}</strong>
          </div>
          <div class="created-item">
            <span>Email</span>
            <strong>{{ lastCreated.email }}</strong>
          </div>
          <div class="created-item">
            <span>Role</span>
            <strong>{{ lastCreated.role }}</strong>
          </div>
          <div class="created-item">
            <span>Password</span>
            <strong>{{ lastCreated.password }}</strong>
          </div>
        </div>
      </div>
    </div>

    <div class="table-card">
      <div class="table-head">
        <h3>Company Employees</h3>
        <div class="table-tools">
          <input v-model="search" type="text" placeholder="Search name or email" />
          <select v-model="roleFilter">
            <option value="all">All Roles</option>
            <option value="hr">HR DEPARTMENT</option>
            <option value="operation">OPERATION DEPARTMENT</option>
            <option value="finance">FINANCIAL DEPARTMENT</option>
          </select>
        </div>
      </div>

      <table v-if="filteredEmployees.length">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Current Password</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="employee in filteredEmployees" :key="employee.id">
            <td class="mono-id">{{ employee.id || "-" }}</td>
            <td>{{ employee.username || employee.name || "-" }}</td>
            <td>{{ employee.email || "-" }}</td>
            <td>
              <span class="role-badge" :class="String(employee.role || '').toLowerCase()">
                {{ formatRoleLabel(employee.role) }}
              </span>
            </td>
            <td class="password-cell">{{ resolvePasswordPreview(employee) }}</td>
            <td>
              <span class="status-badge" :class="normalizeStatus(employee.status)">
                {{ formatStatusLabel(employee.status) }}
              </span>
            </td>
            <td class="actions-cell">
              <button class="row-btn edit" :disabled="isRowBusy(employee.id)" @click="openEditModal(employee)">
                Edit
              </button>
              <button
                class="row-btn suspend"
                :disabled="isRowBusy(employee.id) || normalizeStatus(employee.status) === 'suspended'"
                @click="openSuspendModal(employee)"
              >
                {{ normalizeStatus(employee.status) === "suspended" ? "Suspended" : "Suspend" }}
              </button>
              <button class="row-btn delete" :disabled="isRowBusy(employee.id)" @click="openDeleteModal(employee)">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-else class="empty">No employees found in this company.</div>
    </div>

    <div v-if="showEditModal" class="modal-backdrop" @click="closeEditModal">
      <div class="modal-card" @click.stop>
        <h4>Edit Employee</h4>
        <div class="modal-grid">
          <div class="field">
            <label>Username</label>
            <input v-model="editForm.username" type="text" />
          </div>
          <div class="field">
            <label>Email</label>
            <input v-model="editForm.email" type="email" />
          </div>
          <div class="field">
            <label>Role</label>
            <select v-model="editForm.role">
              <option value="hr">HR DEPARTMENT</option>
              <option value="operation">OPERATION DEPARTMENT</option>
              <option value="finance">FINANCIAL DEPARTMENT</option>
            </select>
          </div>
          <div class="field">
            <label>Status</label>
            <select v-model="editForm.status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
        </div>
        <div class="modal-actions">
          <button class="modal-btn ghost" @click="closeEditModal">Cancel</button>
          <button class="modal-btn primary" :disabled="modalSaving" @click="saveEditModal">
            {{ modalSaving ? "Saving..." : "Save Changes" }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showSuspendModal" class="modal-backdrop" @click="closeSuspendModal">
      <div class="modal-card modal-danger" @click.stop>
        <h4>Suspend Employee</h4>
        <p>Suspend account <strong>{{ selectedEmployee?.username || selectedEmployee?.email || "-" }}</strong>?</p>
        <div class="modal-actions">
          <button class="modal-btn ghost" @click="closeSuspendModal">Cancel</button>
          <button class="modal-btn warn" :disabled="modalSaving" @click="confirmSuspendModal">
            {{ modalSaving ? "Suspending..." : "Suspend" }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showDeleteModal" class="modal-backdrop" @click="closeDeleteModal">
      <div class="modal-card modal-danger" @click.stop>
        <h4>Delete Employee</h4>
        <p>Delete account <strong>{{ selectedEmployee?.username || selectedEmployee?.email || "-" }}</strong>? This action cannot be undone.</p>
        <div class="modal-actions">
          <button class="modal-btn ghost" @click="closeDeleteModal">Cancel</button>
          <button class="modal-btn danger" :disabled="modalSaving" @click="confirmDeleteModal">
            {{ modalSaving ? "Deleting..." : "Delete" }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import Toastify from "toastify-js"
import "toastify-js/src/toastify.css"
import api from "@/services/api"
import { auth, db } from "@/lib/client-platform"
import { addDoc, collection, deleteDoc, doc, getDoc, onSnapshot, query, serverTimestamp, updateDoc, where } from "@/lib/laravel-data"

const companyId = ref("")
const companyName = ref("")
const usersCollectionName = ref("users")
const saving = ref(false)
const loadError = ref("")
const employees = ref([])
const stopSync = ref(null)
const rowBusyId = ref("")
const modalSaving = ref(false)
const showEditModal = ref(false)
const showSuspendModal = ref(false)
const showDeleteModal = ref(false)
const selectedEmployee = ref(null)
const editForm = ref({
  username: "",
  email: "",
  role: "hr",
  status: "active"
})

const form = ref({
  username: "",
  email: "",
  role: "hr",
  tempPassword: ""
})
const lastCreated = ref(null)
const passwordCache = ref(readPasswordCache())

const canCreate = computed(() => {
  return (
    form.value.username.trim() &&
    form.value.email.trim() &&
    form.value.role &&
    form.value.tempPassword.length >= 8
  )
})

const search = ref("")
const roleFilter = ref("all")

const filteredEmployees = computed(() => {
  const term = search.value.trim().toLowerCase()

  return employees.value.filter((employee) => {
    const role = normalizeRole(employee.role)
    const roleOk = roleFilter.value === "all" || role === roleFilter.value
    const textOk =
      !term ||
      String(employee.username || "").toLowerCase().includes(term) ||
      String(employee.email || "").toLowerCase().includes(term)
    return roleOk && textOk
  })
})

const hrCount = computed(() => employees.value.filter((x) => normalizeRole(x.role) === "hr").length)
const financeCount = computed(() => employees.value.filter((x) => normalizeRole(x.role) === "finance").length)
const operationCount = computed(() => employees.value.filter((x) => normalizeRole(x.role) === "operation").length)

onMounted(async () => {
  await bootstrapCompanyScope()
})

onBeforeUnmount(() => {
  if (typeof stopSync.value === "function") {
    stopSync.value()
  }
})

async function bootstrapCompanyScope() {
  const uid = auth.currentUser?.uid
  if (!uid) {
    loadError.value = "Session missing. Please login again."
    return
  }

  let profileSnap = null
  let sourceCollection = "users"

  const lowerSnap = await getDoc(doc(db, "users", uid))
  if (lowerSnap.exists()) {
    profileSnap = lowerSnap
    sourceCollection = "users"
  } else {
    const upperSnap = await getDoc(doc(db, "Users", uid))
    if (upperSnap.exists()) {
      profileSnap = upperSnap
      sourceCollection = "Users"
    }
  }

  if (!profileSnap) {
    loadError.value = "Company admin profile not found."
    return
  }

  usersCollectionName.value = sourceCollection
  const profile = profileSnap.data() || {}
  if (String(profile.role || "").toLowerCase() !== "company_admin") {
    loadError.value = "Access denied. This page is for company admin only."
    return
  }

  companyId.value = String(profile.companyId || "")
  companyName.value = String(profile.companyName || "")

  if (!companyId.value) {
    loadError.value = "Your account has no company assignment."
    return
  }

  localStorage.setItem("companyId", companyId.value)
  localStorage.setItem("companyName", companyName.value)

  const q = query(collection(db, usersCollectionName.value), where("companyId", "==", companyId.value))
  stopSync.value = onSnapshot(q, (snapshot) => {
    employees.value = snapshot.docs
      .map((docSnap) => ({ id: docSnap.id, ...docSnap.data() }))
      .map((user) => ({
        ...user,
        role: normalizeRole(user.role),
      }))
      .filter((user) => ["hr", "finance", "operation"].includes(user.role))
      .sort((a, b) => formatRoleLabel(a.role).localeCompare(formatRoleLabel(b.role)))
  })
}

async function createEmployee() {
  if (!canCreate.value || saving.value) return
  if (!companyId.value) {
    toast("Company context is missing.", "#e74c3c")
    return
  }

  const username = form.value.username.trim()
  const usernameLower = username.toLowerCase()
  const email = form.value.email.trim().toLowerCase()
  const role = form.value.role
  const tempPassword = form.value.tempPassword

  saving.value = true
  try {
    const usernameTaken = employees.value.some(
      (item) => String(item.username || "").trim().toLowerCase() === usernameLower
    )
    if (usernameTaken) {
      toast("Username already used.", "#e74c3c")
      return
    }

    const createRes = await api.post("/users", {
      username,
      name: username,
      email,
      password: tempPassword,
      role,
      status: "active",
      isActive: true,
      companyId: companyId.value,
      companyName: companyName.value,
      department: formatRoleLabel(role),
      position: formatRoleLabel(role)
    })

    const created = createRes?.data || {}
    const createdEmployee = {
      ...created,
      id: String(created.id || `tmp-${Date.now()}`),
      username: String(created.username || created.name || username),
      email: String(created.email || email).toLowerCase(),
      role: normalizeRole(created.role || role),
      status: normalizeStatus(created.status || "active"),
      currentPassword: tempPassword
    }
    const existingIndex = employees.value.findIndex(
      (item) =>
        String(item.id || "") === createdEmployee.id ||
        String(item.email || "").toLowerCase() === createdEmployee.email
    )
    if (existingIndex >= 0) {
      employees.value.splice(existingIndex, 1, createdEmployee)
    } else {
      employees.value = [createdEmployee, ...employees.value]
    }

    await logAdminActivity(
      "Employee Created",
      { uid: createdEmployee.id, email: createdEmployee.email, role: createdEmployee.role },
      `${createdEmployee.username} (${formatRoleLabel(createdEmployee.role)}) account created by company admin`
    )

    lastCreated.value = {
      username: createdEmployee.username,
      email: createdEmployee.email,
      role: formatRoleLabel(createdEmployee.role),
      password: tempPassword
    }
    passwordCache.value = {
      ...passwordCache.value,
      [createdEmployee.email]: tempPassword
    }
    writePasswordCache(passwordCache.value)

    toast(`${formatRoleLabel(role)} account created successfully.`, "#2ecc71")
    form.value = {
      username: "",
      email: "",
      role: "hr",
      tempPassword: ""
    }
  } catch (err) {
    if (Number(err?.response?.status) === 405) {
      toast("Create endpoint is not allowed. Check backend /users POST route.", "#e74c3c")
      return
    }
    if (String(err?.code || "").includes("permission-denied") || Number(err?.response?.status) === 403) {
      toast("Permission denied by Laravel rules. Allow company admin employee creation.", "#e74c3c")
      return
    }
    if (Number(err?.response?.status) === 422) {
      const message = String(err?.response?.data?.message || "").trim()
      toast(message || "Invalid employee details.", "#e74c3c")
      return
    }
    if (String(err?.message || "").toLowerCase().includes("email")) {
      toast("Email already registered or invalid.", "#e74c3c")
      return
    }
    toast(err?.message || "Failed to create employee account.", "#e74c3c")
  } finally {
    saving.value = false
  }
}

function normalizeRole(role) {
  const value = String(role || "").trim().toLowerCase().replace(/[\s_]+/g, " ")
  if (value === "hr" || value === "hr department") return "hr"
  if (value === "operation" || value === "operation department") return "operation"
  if (value === "finance" || value === "financial" || value === "financial department") return "finance"
  if (value === "company admin") return "company_admin"
  if (value === "admin") return "admin"
  return ""
}

function formatRoleLabel(role) {
  const normalized = normalizeRole(role)
  if (normalized === "hr") return "HR DEPARTMENT"
  if (normalized === "operation") return "OPERATION DEPARTMENT"
  if (normalized === "finance") return "FINANCIAL DEPARTMENT"
  if (normalized === "company_admin") return "COMPANY ADMIN"
  if (normalized === "admin") return "ADMIN"
  const fallback = String(role || "").trim()
  return fallback ? fallback.toUpperCase() : "UNKNOWN ROLE"
}

function normalizeStatus(status) {
  const value = String(status || "").toLowerCase()
  if (value === "inactive") return "inactive"
  if (value === "suspended") return "suspended"
  return "active"
}

function formatStatusLabel(status) {
  const normalized = normalizeStatus(status)
  if (normalized === "inactive") return "Inactive"
  if (normalized === "suspended") return "Suspended"
  return "Active"
}

function isRowBusy(employeeId) {
  return rowBusyId.value === employeeId
}

function openEditModal(employee) {
  if (!employee || isRowBusy(employee.id)) return
  selectedEmployee.value = employee
  editForm.value = {
    username: String(employee.username || ""),
    email: String(employee.email || ""),
    role: normalizeRole(employee.role),
    status: normalizeStatus(employee.status)
  }
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  selectedEmployee.value = null
  editForm.value = {
    username: "",
    email: "",
    role: "hr",
    status: "active"
  }
}

async function saveEditModal() {
  const employee = selectedEmployee.value
  if (!employee || isRowBusy(employee.id) || modalSaving.value) return

  const username = editForm.value.username.trim()
  const email = editForm.value.email.trim().toLowerCase()
  const previousEmail = String(employee.email || "").toLowerCase().trim()
  const role = normalizeRole(editForm.value.role)
  const status = normalizeStatus(editForm.value.status)

  if (!username || !email) {
    toast("Username and email are required.", "#e74c3c")
    return
  }

  modalSaving.value = true
  rowBusyId.value = employee.id
  try {
    const nextUsernameLower = username.toLowerCase()
    const duplicated = employees.value.some(
      (item) =>
        item.id !== employee.id &&
        String(item.username || "").trim().toLowerCase() === nextUsernameLower
    )
    if (duplicated) {
      toast("Username already used.", "#e74c3c")
      return
    }

    await updateDoc(doc(usersCollectionName.value, employee.id), {
      username,
      usernameLower: nextUsernameLower,
      email,
      emailLower: email,
      role,
      status,
      isActive: status === "active",
      updatedAt: serverTimestamp(),
      syncedAt: serverTimestamp()
    })
    await logAdminActivity(
      "Employee Updated",
      { uid: employee.id, email, role },
      `${username} (${formatRoleLabel(role)}) updated. Status: ${status}`
    )
    if (previousEmail && previousEmail !== email && passwordCache.value[previousEmail]) {
      const nextCache = { ...passwordCache.value, [email]: passwordCache.value[previousEmail] }
      delete nextCache[previousEmail]
      passwordCache.value = nextCache
      writePasswordCache(nextCache)
    }
    employees.value = employees.value.map((item) => {
      if (String(item.id || "") !== String(employee.id || "")) return item
      return {
        ...item,
        username,
        name: username,
        email,
        role,
        status,
        isActive: status === "active"
      }
    })
    toast("Employee updated.", "#2ecc71")
    closeEditModal()
  } catch (err) {
    if (String(err?.code || "").includes("permission-denied")) {
      toast("Permission denied by Laravel rules for update.", "#e74c3c")
      return
    }
    toast(err?.message || "Failed to update employee.", "#e74c3c")
  } finally {
    modalSaving.value = false
    rowBusyId.value = ""
  }
}

function openSuspendModal(employee) {
  if (!employee || isRowBusy(employee.id) || normalizeStatus(employee.status) === "suspended") return
  selectedEmployee.value = employee
  showSuspendModal.value = true
}

function closeSuspendModal() {
  showSuspendModal.value = false
  selectedEmployee.value = null
}

async function confirmSuspendModal() {
  const employee = selectedEmployee.value
  if (!employee || isRowBusy(employee.id) || modalSaving.value) return

  modalSaving.value = true
  rowBusyId.value = employee.id
  try {
    await updateDoc(doc(usersCollectionName.value, employee.id), {
      status: "suspended",
      isActive: false,
      updatedAt: serverTimestamp(),
      syncedAt: serverTimestamp()
    })
    await logAdminActivity(
      "Employee Suspended",
      { uid: employee.id, email: employee.email, role: employee.role },
      `${employee.username || employee.email || employee.id} account suspended`
    )
    employees.value = employees.value.map((item) => {
      if (String(item.id || "") !== String(employee.id || "")) return item
      return {
        ...item,
        status: "suspended",
        isActive: false
      }
    })
    toast("Employee suspended.", "#f59e0b")
    closeSuspendModal()
  } catch (err) {
    if (String(err?.code || "").includes("permission-denied")) {
      toast("Permission denied by Laravel rules for suspend.", "#e74c3c")
      return
    }
    toast(err?.message || "Failed to suspend employee.", "#e74c3c")
  } finally {
    modalSaving.value = false
    rowBusyId.value = ""
  }
}

function openDeleteModal(employee) {
  if (!employee || isRowBusy(employee.id)) return
  selectedEmployee.value = employee
  showDeleteModal.value = true
}

function closeDeleteModal() {
  showDeleteModal.value = false
  selectedEmployee.value = null
}

async function confirmDeleteModal() {
  const employee = selectedEmployee.value
  if (!employee || isRowBusy(employee.id) || modalSaving.value) return

  modalSaving.value = true
  rowBusyId.value = employee.id
  try {
    const emailKey = String(employee.email || "").toLowerCase().trim()
    await deleteDoc(doc(usersCollectionName.value, employee.id))
    await logAdminActivity(
      "Employee Deleted",
      { uid: employee.id, email: employee.email, role: employee.role },
      `${employee.username || employee.email || employee.id} account deleted`
    )
    if (emailKey && passwordCache.value[emailKey]) {
      const nextCache = { ...passwordCache.value }
      delete nextCache[emailKey]
      passwordCache.value = nextCache
      writePasswordCache(nextCache)
    }
    employees.value = employees.value.filter((item) => String(item.id || "") !== String(employee.id || ""))
    toast("Employee deleted.", "#2ecc71")
    closeDeleteModal()
  } catch (err) {
    if (String(err?.code || "").includes("permission-denied")) {
      toast("Permission denied by Laravel rules for delete.", "#e74c3c")
      return
    }
    toast(err?.message || "Failed to delete employee.", "#e74c3c")
  } finally {
    modalSaving.value = false
    rowBusyId.value = ""
  }
}

function resolvePasswordPreview(employee) {
  const emailKey = String(employee?.email || "").toLowerCase().trim()
  const cached = emailKey ? passwordCache.value[emailKey] : ""
  if (cached) return cached
  const raw = String(employee?.currentPassword || employee?.tempPassword || "").trim()
  if (raw) return raw
  return "Hidden"
}

function readPasswordCache() {
  try {
    const raw = localStorage.getItem("companyEmployeePasswordCache")
    const parsed = raw ? JSON.parse(raw) : {}
    return parsed && typeof parsed === "object" ? parsed : {}
  } catch {
    return {}
  }
}

function writePasswordCache(cache) {
  try {
    localStorage.setItem("companyEmployeePasswordCache", JSON.stringify(cache))
  } catch {
    // no-op
  }
}

function toast(text, backgroundColor) {
  Toastify({
    text,
    backgroundColor
  }).showToast()
}

async function logAdminActivity(event, target = {}, details = "") {
  const actor = auth.currentUser
  if (!actor?.uid || !companyId.value) return

  try {
    await addDoc(collection(db, "activity_logs"), {
      event,
      details,
      actorUid: actor.uid,
      actorEmail: String(actor.email || localStorage.getItem("userEmail") || ""),
      companyId: companyId.value,
      companyName: companyName.value || "",
      targetUid: String(target.uid || ""),
      targetEmail: String(target.email || ""),
      targetRole: String(target.role || ""),
      createdAt: serverTimestamp()
    })
  } catch (err) {
    console.warn("activity_logs write failed:", err)
  }
}
</script>

<style scoped>
.page {
  display: grid;
  gap: 12px;
}

.hero {
  background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 48%, #1d4ed8 100%);
  color: #fff;
  border-radius: 16px;
  padding: 18px;
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
}

.hero h2 {
  margin: 0;
  font-size: 24px;
}

.hero p {
  margin: 6px 0 0;
  color: #dbeafe;
}

.chips {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.chip {
  display: inline-flex;
  align-items: center;
  height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  background: #fff;
  color: #1e3a8a;
}

.chip.subtle {
  background: rgba(255, 255, 255, 0.16);
  color: #dbeafe;
}

.error {
  background: #fee2e2;
  color: #991b1b;
  padding: 10px 12px;
  border-radius: 8px;
  margin-bottom: 12px;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 10px;
}

.summary-card {
  background: #fff;
  border: 1px solid #dbe3f4;
  border-radius: 12px;
  padding: 12px;
}

.summary-card span {
  color: #64748b;
  font-size: 12px;
}

.summary-card strong {
  display: block;
  margin-top: 5px;
  color: #0f172a;
  font-size: 20px;
}

.form-card,
.table-card {
  background: #ffffff;
  border: 1px solid #dbe3f4;
  border-radius: 14px;
  padding: 14px;
}

.table-card {
  margin-top: 2px;
}

.form-card h3,
.table-card h3 {
  margin: 0 0 12px;
  color: #0f172a;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
}

.field {
  display: grid;
  gap: 4px;
}

label {
  display: block;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 5px;
}

input,
select {
  width: 100%;
  height: 38px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 0 10px;
  background: #fff;
}

.create-btn {
  margin-top: 12px;
  border: none;
  border-radius: 8px;
  background: #2563eb;
  color: #fff;
  padding: 10px 14px;
  cursor: pointer;
  font-weight: 700;
}

.created-card {
  margin-top: 14px;
  border: 1px solid #bbf7d0;
  background: #f0fdf4;
  border-radius: 12px;
  padding: 12px;
}

.created-card h4 {
  margin: 0 0 10px;
  color: #166534;
  font-size: 14px;
}

.created-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 8px;
}

.created-item {
  background: #ffffff;
  border: 1px solid #dcfce7;
  border-radius: 10px;
  padding: 8px 10px;
}

.created-item span {
  display: block;
  color: #64748b;
  font-size: 11px;
}

.created-item strong {
  color: #0f172a;
  font-size: 13px;
  word-break: break-word;
}

.table-head {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.table-tools {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.table-tools input,
.table-tools select {
  height: 36px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  padding: 0 10px;
}

.create-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  text-align: left;
  padding: 10px 8px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
}

th {
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.mono-id {
  font-family: Consolas, "Courier New", monospace;
  font-size: 12px;
  color: #334155;
}

.password-cell {
  font-family: Consolas, "Courier New", monospace;
  font-size: 12px;
  color: #0f172a;
}

.role-badge,
.status-badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 700;
}

.role-badge.hr {
  background: #dbeafe;
  color: #1e40af;
}

.role-badge.finance {
  background: #ede9fe;
  color: #5b21b6;
}

.role-badge.operation {
  background: #dcfce7;
  color: #166534;
}

.status-badge.active {
  background: #dcfce7;
  color: #166534;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.suspended {
  background: #ffedd5;
  color: #9a3412;
}

.empty {
  color: #64748b;
  padding: 10px 0;
}

.cell-input,
.cell-select {
  width: 100%;
  height: 34px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 0 10px;
}

.actions-cell {
  white-space: nowrap;
}

.row-btn {
  border: none;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  margin-right: 6px;
}

.row-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.row-btn.edit {
  background: #dbeafe;
  color: #1e40af;
}

.row-btn.save {
  background: #dcfce7;
  color: #166534;
}

.row-btn.cancel {
  background: #e2e8f0;
  color: #334155;
}

.row-btn.delete {
  background: #fee2e2;
  color: #991b1b;
}

.row-btn.suspend {
  background: #ffedd5;
  color: #9a3412;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1200;
  padding: 16px;
}

.modal-card {
  width: min(560px, 100%);
  background: #ffffff;
  border: 1px solid #dbe3f4;
  border-radius: 14px;
  padding: 16px;
  box-shadow: 0 24px 50px rgba(2, 6, 23, 0.28);
}

.modal-card h4 {
  margin: 0 0 12px;
  color: #0f172a;
}

.modal-card p {
  margin: 0 0 14px;
  color: #334155;
}

.modal-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
}

.modal-actions {
  margin-top: 14px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.modal-btn {
  border: none;
  border-radius: 8px;
  padding: 9px 12px;
  font-weight: 700;
  cursor: pointer;
}

.modal-btn.ghost {
  background: #e2e8f0;
  color: #334155;
}

.modal-btn.primary {
  background: #2563eb;
  color: #fff;
}

.modal-btn.warn {
  background: #f59e0b;
  color: #fff;
}

.modal-btn.danger {
  background: #dc2626;
  color: #fff;
}

.modal-btn:disabled {
  opacity: 0.72;
  cursor: not-allowed;
}

@media (max-width: 900px) {
  .hero {
    flex-direction: column;
    align-items: flex-start;
  }

  .table-tools {
    width: 100%;
  }

  .table-tools input,
  .table-tools select {
    width: 100%;
  }
}
</style>
